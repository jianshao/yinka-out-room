<?php


namespace app\domain\activity\sweet;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\utils\CommonUtil;
use Co\Redis;
use think\facade\Log;
use Exception;

class SweetJourneyService
{
    protected static $instance;
    protected $config_key = 'sweet_journey_activity_config';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SweetJourneyService();
        }
        return self::$instance;
    }

    /**
     * 获取页面内容
     * @param $userId
     */
    public function getPageInfo($userId)
    {
        try {
            $config_key = $this->config_key;
            $redis = RedisCommon::getInstance()->getRedis();
            $config = $redis->get($config_key);
            $config = json_decode($config, true);
            if (time() < $config['start_time']) {
                throw new FQException('活动未开始', 500);
            }
//            if (time() > $config['end_time']) {
//                throw new FQException('活动已结束', 500);
//            }
            $res['wishReward'] = $this->wishReward($userId, $config, $redis);
            $res['rankList'] = $this->rankList($userId, $config, $redis);
            return $res;
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('SweetJourneyService getPageInfo userId=%d ex=%d:%s trace=%s',
                    $userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 用户心愿奖励详情
     * @param $userId
     * @param $config
     */
    public function wishReward($userId, $config, $redis)
    {
        $wish_config = $config['wish_reward_config'];
        $userBoxStatusKey = $config['journey_user_get_box_key'];
        foreach ($wish_config as &$value) {
            $value['user_num'] = $this->getUserReceiveNum($userId, $value['receiveGiftId'], $redis, $config);
            $status = $redis->hget($userBoxStatusKey, sprintf("%d_%d", $userId, $value['receiveGiftId']));
            $value['get_status'] = $status === false ? 0 : $status;
        }
        return $wish_config;
    }

    private function getUserReceiveNum($userId, $giftId, $redis, $config) {
        $num = $redis->zScore($config['journey_user_receive_list_key'], sprintf("%d_%d", $userId, $giftId));
        return $num == false ? 0 : $num;
    }

    /**
     * 榜单内容
     */
    public function rankList($userId, $config, $redis)
    {
        $data = [];
        $journey_guard_list_key = $config['journey_guard_list_key'];
        $journey_sweet_list_key = $config['journey_sweet_list_key'];
        $guardList = $redis->zRevRange($journey_guard_list_key, 0, 9, true);
        $sweetList = $redis->zRevRange($journey_sweet_list_key, 0, 9, true);
        $data['guard'] = $this->dealCurrentRanking($guardList, $redis, $userId, $journey_guard_list_key);
        $data['sweet'] = $this->dealCurrentRanking($sweetList, $redis, $userId, $journey_sweet_list_key); //当日实时排名数据
        return $data;
    }

    public function dealCurrentRanking($listUser, $redis, $userId, $key)
    {
        $data = [];
        if (!empty($listUser)) {
            $randId = 1;
            foreach ($listUser as $user_id => $value) {
                $userModel = UserModelDao::getInstance()->loadUserModel($user_id);
                $data['list'][] = [
                    'index' => $randId++,
                    'uid' => $user_id,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'nickname' => $userModel->nickname,
                    'totalcoin' => $value,
                ];
            }
        } else {
            $data['list'] = [];
            $data['user'] = [];
        }
        $data['user'] = $this->getUserInfo($userId, $key, $redis);
        return $data;
    }

    /**
     * 获取用户自己排名
     */
    public function getUserInfo($userId, $key, $redis)
    {
        $userinfo = [];
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        $userinfo['uid'] = $userModel->userId;
        $userinfo['nickname'] = $userModel->nickname;
        $userinfo['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);
        $num = $redis->ZREVRANK($key, $userId);
        $userinfo['index'] = $num === false ? 0 : $num + 1;
        $totalcoin = $redis->zScore($key, $userId);
        $userinfo['totalcoin'] = $totalcoin === false ? 0 : (int)$totalcoin;
        return $userinfo;
    }


    public function getActivityBox($userId, $giftId)
    {
        try {
            $config_key = $this->config_key;
            $redis = RedisCommon::getInstance()->getRedis();
            $config = $redis->get($config_key);
            $config = json_decode($config, true);
            if (time() < $config['start_time']) {
                throw new FQException('活动未开始', 500);
            }
            if (time() > $config['end_time']) {
                throw new FQException('活动已结束', 500);
            }
            $userBoxStatusKey = $config['journey_user_get_box_key'];
            $status = $redis->hget($userBoxStatusKey, sprintf("%d_%d", $userId, $giftId));
            if ($status) {
                throw new FQException('同一宝箱只能领取一次～', 500);
            }
            $giftIdArr = array_column($config['wish_reward_config'], 'receiveGiftId');
            if (!in_array($giftId, $giftIdArr)) {
                throw new FQException('参数错误', 500);
            }
            //获取用户累积值
            $num = $this->getUserReceiveNum($userId, $giftId, $redis, $config);
            //判断领取条件
            $WishRewardInfo = $this->getWishRewardInfo($giftId, $config);
            $maxNum = $WishRewardInfo['maxNum'];
            if ($num < $maxNum) {
                throw new FQException('不满足领取条件～', 500);
            }
            //领取实现
            return $this->getRewardImpl($userId, $giftId, $WishRewardInfo, $redis, $userBoxStatusKey);
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('SweetJourneyService getActivityBox userId=%d giftId=%d ex=%d:%s trace=%s',
                    $userId, $giftId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 奖励领取实现
     */
    private function getRewardImpl($userId, $giftId, $WishRewardInfo, $redis, $userBoxStatusKey) {
        $maxValue = count($WishRewardInfo['randGift']) -1;
        if ($maxValue < 0) {
            throw new FQException('活动配置错误1', 500);
        }
        $rewardIndex = rand(0, $maxValue);
        $rewardIndexInfo = $WishRewardInfo['randGift'][$rewardIndex];
        $assetId = $rewardIndexInfo['assetId'];
        $count = $rewardIndexInfo['count'];
        $assetKind = AssetSystem::getInstance()->findAssetKind($assetId);
        if ($assetKind == null || empty($count)) {
            throw new FQException('活动配置错误2', 500);
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetId, $count, $giftId) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                // 获取用户资产
                $timestamp = time();
                $userAsset = $user->getAssets();
                if ($count < 0) {
                    throw new FQException('活动配置错误3', 500);
                }

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'sweetJourney',$giftId, $count);

                $userAsset->add($assetId, $count, $timestamp, $biEvent);
            });

            Log::info(sprintf('SweetJourneyService::getRewardImpl ok userId=%d assetId=%s change=%d',
                $userId, $assetId, $count));

            $redis->hSet($userBoxStatusKey, sprintf("%d_%d", $userId, $giftId), 1);
            return $rewardIndexInfo;
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('SweetJourneyService::getRewardImpl exception userId=%d assetId=%s change=%d ex=%d:%s trace=%s',
                    $userId, $assetId, $count, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 获取宝箱奖励详情
     */
    private function getWishRewardInfo($giftId, $config) {
        $wish_config = $config['wish_reward_config'];
        foreach ($wish_config as $value) {
            if ($value['receiveGiftId'] == $giftId) {
                return $value;
            }
        }

    }

    public function generateRankList($event, $config) {
        $giftArr = $config['activity_gift'];
        if (in_array($event->giftKind->kindId, $giftArr)) {
            //守护榜
            $guardValue = 0;
            if ($event->giftKind->price && $event->giftKind->price->assetId == AssetKindIds::$BEAN) {
                $guardValue = intval($event->giftKind->price->count * $event->count * count($event->receiveUsers));
            }
            if ($guardValue > 0) {
                $this->onRankListChange($event->fromUserId, $guardValue, $config['journey_guard_list_key']);
            }

            //甜蜜榜
            foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                $sweetValue = 0;
                foreach ($giftDetails as $giftDetail) {
                    $sweetValue += abs($giftDetail->deliveryGiftKind->deliveryCharm * $giftDetail->count);
                }
                $this->onRankListChange($receiveUser->userId, $sweetValue, $config['journey_sweet_list_key']);
                $this->onReceiveNumChange($receiveUser->userId, $event->giftKind->kindId, $config['journey_user_receive_list_key'], $event->count);
            }
        }
    }

    /**
     * 榜单值变化
     * @param $userId
     * @param $value
     */
    private function onRankListChange($userId, $value, $key) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->zIncrBy($key, $value, $userId);
    }

    /**
     * 用户收到礼物数量变化
     */
    private function onReceiveNumChange($userId, $giftId, $key, $value) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->zIncrBy($key, $value, sprintf('%d_%d', $userId, $giftId));
    }

}