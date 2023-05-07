<?php


namespace app\domain\activity\yearTicket;

use app\core\mysql\Sharding;
use app\domain\activity\common\service\ActivityService;
use app\domain\asset\AssetItem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\user\UserRepository;
use app\event\BreakBoxNewEvent;
use app\event\SendGiftEvent;
use app\event\TurntableEvent;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class YearTicketService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YearTicketService();
        }
        return self::$instance;
    }

    /**
     * @Info 获取活动时间线
     * @param null $timestamp
     * @return mixed|null
     */
    public function getLevelUpgrade()
    {
        $config = Config::loadConf();
        return ArrayUtil::safeGet($config, "levelUpgrade", []);
    }

    public function getLevelUpgradeDisplay()
    {
        $data=$this->getLevelUpgrade();
        if (empty($data)){
            return [];
        }
        $result=[];
        foreach ($data as $itemData){
            $itemData['displayStartTime']=date("m-d",strtotime($itemData['startTime']));
            $itemData['displayEndTime']=date("m-d",strtotime($itemData['endTime']));
            $result[]=$itemData;
        }
        return $result;
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


    public function isEnd($timestamp){
        $config = Config::loadConf();
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp > $stopTime) {
            throw new FQException("活动已经结束了", 515);
        }
    }

    /**
     * @info 是否是奖励礼物
     * @param $giftId
     * @return bool
     */
    private function isPrizeGifts($config, $giftId)
    {
        $prizeGifts = ArrayUtil::safeGet($config, 'prizeGifts', []);
        return in_array($giftId, $prizeGifts);
    }


    /**
     * @info 获取一个奖励
     * @param $config
     * @param $event
     * @return AssetItem|null
     */
    private function recallRewardImpl($config, $event)
    {
        $prizeGifts = ArrayUtil::safeGet($config, 'senderAssets', []);
        if (empty($prizeGifts)) {
            return null;
        }
        $giftModels = AssetItem::decodeList($prizeGifts);
        $rangeKey = mt_rand(0, (count($giftModels) - 1));
        return $giftModels[$rangeKey] ?? null;
    }

    /**
     * @info 砸蛋event
     * @param BreakBoxNewEvent $event
     * @throws FQException
     */
    public function onBreakBoxNewEvent(BreakBoxNewEvent $event)
    {
        try {
            $this->isAction($event->userId, $event->timestamp);
        } catch (FQException $e) {
            if ($e->getCode() === 513) {
                return;
            }
        }
        $config = Config::loadConf();
//        获取一个奖励
        $reward = $this->recallRewardImpl($config, $event);
        if ($reward === null) {
            throw new FQException("onBreakBoxNewEvent config error", 500);
        }
        $deliveryGiftMap = $event->deliveryGiftMap ?? [];
        $userId = $event->userId;
        $timestamp = $event->timestamp;
        $rewardCount = 0;
        foreach ($deliveryGiftMap as $giftId => $count) {
//            如果是奖励礼物，给用户背包发放奖励，小秘书通知？
            if ($this->isPrizeGifts($config, $giftId)) {
                $rewardCount += $count;
            }
        }
        if ($rewardCount === 0) {
            Log::info(sprintf('YearTicketService::onBreakBoxNewEvent userId=%d reward=%s count=%d', $event->userId, $reward->assetId, $rewardCount));
            return;
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $reward, $rewardCount, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $userAssets = $user->getAssets();
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'recallSms');
                $userAssets->add($reward->assetId, $rewardCount, $timestamp, $biEvent);
            });
        } catch (\Exception $e) {
            Log::error(sprintf('YearTicketService::onBreakBoxNewEvent userId=%d, ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        Log::info(sprintf('YearTicketService::onBreakBoxNewEvent userId=%d reward=%s count=%d', $event->userId, $reward->assetId, $rewardCount));
    }


    /**
     * @info 转盘event
     * @param TurntableEvent $event
     * @throws FQException
     */
    public function onTurntableEvent(TurntableEvent $event)
    {
        try {
            $this->isAction($event->userId, $event->timestamp);
        } catch (FQException $e) {
            if ($e->getCode() === 513) {
                return;
            }
        }

        $config = Config::loadConf();
//        获取一个奖励
        $reward = $this->recallRewardImpl($config, $event);
        if ($reward === null) {
            throw new FQException("onBreakBoxNewEvent config error", 500);
        }
        $deliveryGiftMap = $event->deliveryGiftMap ?? [];
        $userId = $event->userId;
        $timestamp = $event->timestamp;
        $rewardCount = 0;
        foreach ($deliveryGiftMap as $giftId => $count) {
//            如果是奖励礼物，给用户背包发放奖励，小秘书通知？
            if ($this->isPrizeGifts($config, $giftId)) {
                $rewardCount += $count;
            }
        }
        if ($rewardCount === 0) {
            Log::info(sprintf('YearTicketService::onTurntableEvent userId=%d reward=%s count=%d', $event->userId, $reward->assetId, $rewardCount));
            return;
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $reward, $rewardCount, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $userAssets = $user->getAssets();
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'recallSms');
                $userAssets->add($reward->assetId, $rewardCount, $timestamp, $biEvent);
            });
        } catch (\Exception $e) {
            Log::error(sprintf('YearTicketService::onTurntableEvent userId=%d, ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        Log::info(sprintf('YearTicketService::onTurntableEvent userId=%d reward=%s count=%d', $event->userId, $reward->assetId, $rewardCount));
    }

    /**
     * @param SendGiftEvent $event
     */
    public function onSendGiftEvent(SendGiftEvent $event)
    {
        try {
            $this->isAction($event->fromUserId, $event->timestamp);
        } catch (\Exception $e) {
            if ($e->getCode() === 513) {
                return;
            }
        }

        try {
            $this->sendGift($event->fromUserId, $event->sendDetails, $event->roomId, $event->timestamp, $event->fromBag, $event->receiveUsers, $event->giftKind, $event->count);
        } catch (\Exception $e) {
            Log::error(sprintf('ZhongQiuService onSendGiftEvent Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }



    /**
     * 初始化所有工会和积分数据 后根据赛段截取数量
     * @param $localUpgradeData
     * @param $allUpgradeData
     * @return array
     */
    public function getGuildRankList($localUpgradeData)
    {
        $timestamp = YearTicketService::getInstance()->getTimestamp();
        try {
            YearTicketService::getInstance()->isEnd($timestamp);
        } catch (\Exception $e) {
            if ($e->getCode() === 515) {
                $localUpgradeData = YearTicketService::getInstance()->getLevelUpgradeForAid(4);
            }
        }
        $aid = $localUpgradeData['id']??0;
        if ($aid <= 1) {
            $countNumber = 50;
        } else {
            $lastAid = $aid - 1;
            $lastUpgradeData = YearTicketService::getInstance()->getLevelUpgradeForAid($lastAid);
            $countNumber = $lastUpgradeData['rankNumber'] ?? 50;
        }
        return YearTicketGuildDao::getInstance()->getRankGuildList(0, $countNumber);
    }

    /**
     * @param $fromUserId
     * @param $sendDetails
     * @param $roomId
     * @param $timestamp
     * @param $fromBag
     * @return false
     */
    private function sendGift($fromUserId, $sendDetails, $roomId, $timestamp, $fromBag, $receiveUsers, $giftKind, $count)
    {
        $config = Config::loadConf();
        $ticketGiftIds = ArrayUtil::safeGet($config, 'ticketGifts', []);
        $ticketRatio = ArrayUtil::safeGet($config, 'ticketRatio', 0);
        # 神豪榜 <userId， 积分>
        $addUserMap = [];
        # 年度之星工会 <guildId， 积分>
        $addGuildMap = [];


        if (in_array($giftKind->kindId, $ticketGiftIds)) {
            $score = $count * $ticketRatio * count($receiveUsers);
            $addUserMap[$fromUserId] = $score;
        } else if (!$fromBag) {
            $addUserMap[$fromUserId] = intval($giftKind->price->count * $count * count($receiveUsers));
        }

        foreach ($receiveUsers as $receiveUser) {
            $guildId = MemberSocityModelDao::getInstance()->getGuidIdByUserId($receiveUser->userId);
            if (!isset($addGuildMap[$guildId])) {
                $addGuildMap[$guildId] = 0;
            }
            if ($guildId <= 0) {
                continue;
            }
            if (in_array($giftKind->kindId, $ticketGiftIds)) {
                $score = $count * $ticketRatio;
                $addGuildMap[$guildId] += $score;
            } else if (!$fromBag) {
                $addGuildMap[$guildId] += intval($giftKind->price->count * $count);
            }
        }

        Log::info(sprintf('YearTicketService.sendGift roomId=%d fromUserId=%d, addUserMap=%s addGuildMap=%s', $roomId, $fromUserId, json_encode($addUserMap), json_encode($addGuildMap)));

        $unixTime = YearTicketService::getInstance()->getTimestamp();
        $localUpgradeData = $this->getLevelUpgradeOne($config, $unixTime);
        if (empty($localUpgradeData)) {
            return false;
        }
        YearTicketUserDao::getInstance()->storeUserMap($addUserMap, $localUpgradeData);

        YearTicketGuildDao::getInstance()->storeGuildMap($addGuildMap, $localUpgradeData);
        return true;
    }

//    public function buildGuildScore() {
//        $detailsList = $this->buildSendGiftDetails($giftKind, $count, 0);
//
//        foreach ($detailsList as $details) {
//            $price = 0;
//            if (!is_null($details->deliveryGiftKind->price) && $details->deliveryGiftKind->price->assetId == AssetKindIds::$BEAN) {
//                $price = $details->deliveryGiftKind->price->count * $details->count;
//            }
//            $biEvent = BIReport::getInstance()->makeSendGiftBIEvent($roomId, $receiveUser->userId, $details->giftKind->kindId, $details->deliveryGiftKind->kindId, $details->count, false, $price);
//            if ($details->consumeAsset) {
//                $fromUser->getAssets()->consume($details->consumeAsset->assetId, $details->consumeAsset->count, $timestamp, $biEvent);
//            }
//            if ($details->senderAssets) {
//                foreach ($details->senderAssets as $sendAsset) {
//                    $fromUser->getAssets()->add($sendAsset->assetId, $sendAsset->count, $timestamp, $biEvent);
//                }
//            }
//        }
//    }

    public function getTimestamp()
    {
        if (config("config.appDev") === 'dev') {
//            return strtotime("2022-01-10 00:00:00");
//            return strtotime("2022-01-12 12:00:00");
//            return strtotime("2022-01-13 23:00:00");
//            return strtotime("2022-01-15 10:00:00");
//            return strtotime("2022-01-16 10:00:00");
//            return strtotime("2022-01-17 12:00:00");
            return strtotime("2022-01-18 12:00:00");
//            return strtotime("2022-01-19 07:00:00");
//            return strtotime("2022-02-13 23:00:00");
        }
        return time();
    }

    public function getLevelUpgradeOne($config, $timestamp)
    {
        $levelUpgrade = ArrayUtil::safeGet($config, 'levelUpgrade');
        $localUpgradeData = [
            'id' => 0,
            'startTime' => "",
            'endTime' => "",
            'displayName' => "",
            'rankNumber' => 0,
        ];

        try {
            YearTicketService::getInstance()->isEnd($timestamp);
        } catch (\Exception $e) {
            if ($e->getCode() === 515) {
                return $localUpgradeData;
            }
        }

        foreach ($levelUpgrade as $itemData) {
            if ($timestamp >= strtotime($itemData['startTime'])) {
                $localUpgradeData = $itemData;
            }
        }

        return $localUpgradeData;
    }


    /**
     * @info 通过id查找赛区
     * @param $lastAid
     * @return array|mixed
     */
    public function getLevelUpgradeForAid($lastAid,$config=null) {
        if ($config===null){
            $config = Config::loadConf();
        }
        $levelUpgrade = ArrayUtil::safeGet($config, 'levelUpgrade');
        $localUpgradeData = [];
        foreach ($levelUpgrade as $itemData) {
            if ($lastAid === $itemData['id']) {
                $localUpgradeData = $itemData;
            }
        }
        return $localUpgradeData;
    }


    public function getGuildScoreRankSecond($guildId)
    {
        $config = Config::loadConf();
        $timestamp = YearTicketService::getInstance()->getTimestamp();
        $localUpgradeData = YearTicketService::getInstance()->getLevelUpgradeOne($config, $timestamp);
        $hover = $localUpgradeData['id'] ?? 1;
        return YearTicketGuildDao::getInstance()->getGuildScoreRank($guildId, $hover);
    }


    /**
     * @param $userId
     * @return array
     */
    public function getUserScoreRankSecond($userId)
    {
        return YearTicketUserDao::getInstance()->getUserScoreRank($userId);
    }

}