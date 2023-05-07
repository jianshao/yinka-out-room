<?php
namespace app\domain\activity\gameVote;

use app\core\mysql\Sharding;
use app\domain\activity\common\service\ActivityService;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetSystem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\UserRepository;
use app\service\LockService;
use app\utils\TimeUtil;
use think\facade\Log;

class GameVoteService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GameVoteService();
        }
        return self::$instance;
    }

    private function getListData($config)
    {
        if (empty($config)) {
            throw new FQException("fatal error config empty", 500);
        }
        return $config['list'] ?? [];
    }

    /**
     * @info 初始化列表数据
     * @param $userId
     * @param $timestamp
     * @return array
     * @throws FQException
     */
    public function init($userId, $timestamp)
    {
        $config = Config::loadConf();
        $originList = $this->getListData($config);
        $usermodel = GameVoteUserDao::getInstance()->loadUser($userId, $timestamp);
        if ($usermodel === null) {
            $userListData = [];
        } else {
            $userListData = $usermodel->rewardData->data;
        }
        $listData = [];
        $gameVoteSortData = GameVoteDao::getInstance()->loadData();
        foreach ($originList as $item) {
            $id = $item['id'] ?? 0;
            $resultItem['id'] = $id;
            $resultItem['videoSrc'] = $item['url'] ?? "";
            $resultItem['mp4Src'] = $item['mp4_url'] ?? "";
            $resultItem['name'] = $item['name'] ?? "";
            $resultItem['poster'] = $item['poster'] ?? "";
            $resultItem['imgSrc'] = $item['imgUrl'] ?? "";
            $resultItem['status'] = $userListData[$item['id']] ?? 2;
            $resultItem['number'] = isset($gameVoteSortData[$id]) ? (int)$gameVoteSortData[$id] : 0;
            $listData[] = $resultItem;
        }

        usort($listData, function ($item1, $itme2) {
            return $item1['number'] > $itme2['number'] ? false : true;
        });

        $userVoteNumber = $usermodel->getVoteNumber();
        return [$listData, $userVoteNumber];
    }


    /**
     * @Info 活动是否开启
     * @param null $timestamp
     * @return bool true 活动中， fasle 未开始或结束
     */
    public function isAction($userId, $timestamp = null)
    {
        if (config('config.appDev') === "dev") {
            return true;
        }

        $config = Config::loadConf();
        $timestamp = $timestamp == null ? time() : $timestamp;
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime && !ActivityService::getInstance()->checkUserEnable($userId)) {
            throw new FQException("活动没有开始", 513);
        }
        if ($timestamp > $stopTime && !ActivityService::getInstance()->checkUserEnable($userId)) {
            throw new FQException("活动已经结束了", 513);
        }
        return true;
    }

    public function buildLockKey($userId): string
    {
        return "GameVoteService:lock:user:" . $userId;
    }

    private function getRewardForConf($config)
    {
        if (empty($config)) {
            throw new FQException("fatal error config empty", 500);
        }
        return $config['senderAssets'] ?? [];
    }

    /**
     * @param $config
     * @return mixed
     * @throws FQException
     */
    private function getOneAssetModel($config)
    {
        $rewardData = $this->getRewardForConf($config);
        $senderAssetsModel = AssetItem::decodeList($rewardData);
        return current($senderAssetsModel);
    }


    /**
     * @info  投票
     * @param $userId
     * @param $consumeId
     * @param $number
     * @param $timestamp
     * @return bool
     * @throws FQException
     */
    public function fire($userId, $consumeId, $number, $timestamp)
    {
        $lockKey = $this->buildLockKey($userId);
        LockService::getInstance()->lock($lockKey);
        $sendGiftStatus = false;
        try {
            $user = GameVoteUserDao::getInstance()->loadUser($userId, $timestamp);
            if ($user === null) {
                throw new FQException("fatal error user empty", 500);
            }
            $this->exchangeConsume($user, $consumeId);
//            如果用户没有领过礼物，领奖
            if ($consumeId > 0 && $user->rewardStatus === 0) {
                $config = Config::loadConf();
                $itemAssetModel = $this->getOneAssetModel($config);
                $this->adjustAsset($user, $itemAssetModel, $timestamp);
//                发送小秘书？
                $sendGiftStatus = true;
            }
        } catch (\Exception $e) {
            Log::error(sprintf('GameVoteService::fire exchange Exception userId=%d ex=%d:%s trace=%s',
                $userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
        //增加游戏的票数
        GameVoteDao::getInstance()->incrData($consumeId, $number);
        if ($sendGiftStatus) {
//            发小秘书消息
            $msg = ["msg" => sprintf("非常感谢您参与投票，赠送您 %s，快去背包查看吧！", $itemAssetModel->name)];
            $pushResult = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
            Log::info(sprintf('GameVoteService::fire YunXinMsg sendMsg success userId=%d rewards=%s yunxinResult=%s', $userId, $itemAssetModel->assetId, $pushResult));
        }
        Log::info(sprintf('GameVoteService::fire success userId=%d consumeId=%d count=%d', $userId, $consumeId, $number));
        return true;
    }


    /**
     * @param GameVoteUser $gameVoteUser
     * @param AssetItem $itemAssetModel
     * @param $timestamp
     * @return bool
     * @throws FQException
     */
    public function adjustAsset(GameVoteUser $gameVoteUser, AssetItem $itemAssetModel, $timestamp)
    {
        $userId = $gameVoteUser->userId;
        $assetKind = AssetSystem::getInstance()->findAssetKind($itemAssetModel->assetId);
        if (empty($assetKind)) {
            throw new FQException("fatal error assetKind error", 500);
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $itemAssetModel, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user === null) {
                    throw new FQException("用户不存在", 500);
                }
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'gamevote', 'fire');
                $user->getAssets()->add($itemAssetModel->assetId, $itemAssetModel->count, $timestamp, $biEvent);
            });
        } catch (\Exception $e) {
            Log::error(sprintf('HalloweenService.adjustAssets error userId=%d assetItem=%s error msg:%s:strace:%s', $userId, $itemAssetModel->assetId, $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
//        标记用户已经领过奖了
        $gameVoteUser->rewardStatus = 1;
        GameVoteUserDao::getInstance()->saveUser($gameVoteUser, $timestamp);
        return true;
    }


    /**
     * @throws \app\domain\exceptions\FQException
     */
    public function exchangeConsume($user, $consumeId)
    {
        $voteNumber = $user->voteNumber;
        if ($voteNumber >= 3) {
            throw new FQException('投票次数不足', 500);
        }
        if ($user->rewardData->data[$consumeId] === 2) {
            throw new FQException("已经投票过了", 500);
        }
        return $this->updateUserBank($user, [$consumeId], 1, '兑换消耗');
    }


    public function updateUserBank($user, $consumeIds, $count, $reason)
    {
        foreach ($consumeIds as $consumeId) {
            $user->voteNumber += $count;
            $user->rewardData->data[$consumeId] = 2;
            Log::info(sprintf('SpringFestivalService updateUserBank userId:%s giftId:%s count:%s reason:%s', $user->userId, $consumeId, $count, $reason));
        }
        GameVoteUserDao::getInstance()->saveUser($user, time());
        return true;
    }

}