<?php


namespace app\domain\activity\recallUser;

use app\core\mysql\Sharding;
use app\domain\bi\BIConfig;
use app\domain\bi\BIReport;
use app\domain\bi\BIUserAssetModelDao;
use app\domain\dao\LoginDetailNewModelDao;
use app\domain\exceptions\FQException;
use app\domain\pay\dao\OrderModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use think\facade\Log;
use Exception;

class RecallUserService
{
    protected static $instance;
    protected $return_user_activity_key = 'return_user_activity_config';
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RecallUserService();
        }
        return self::$instance;
    }

    /**
     * 领取回归之星
     */
    public function receiveReturnStar($userId, $redis) {
        $time = time();
        $activityConfig = $redis->hGetAll($this->return_user_activity_key);
        if ($time < $activityConfig['start_time']) {
            throw new FQException('活动未开始', 500);
        }
        if ($time >= $activityConfig['end_time']) {
            throw new FQException('活动已结束', 500);
        }
        //判断用户是否有资格领取
        $isHaveReturnAuth = $this->isHaveReturnAuth($userId, $activityConfig,1);
        if (!$isHaveReturnAuth) {
            throw new FQException('很抱歉，您没有达到领取条件哦～', 500);
        }
        $returnStarStatus = $redis->hget(sprintf('return_star_%s', $activityConfig['id']), $userId);
        if ($returnStarStatus == 1) {
            throw new FQException('每个用户可领取一次', 500);
        }
        $configAssetInfo = json_decode($activityConfig['return_star'],true);
        $assetId = $configAssetInfo['assetId'];
        $count = $configAssetInfo['count'];
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetId, $count) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                // 获取用户资产
                $timestamp = time();
                $userAsset = $user->getAssets();
                if ($count < 0) {
                    throw new FQException('资产数量错误', 500);
                }

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'return_user','return_star', $count);

                $userAsset->add($assetId, $count, $timestamp, $biEvent);
            });

            Log::info(sprintf('ReturnUserService::receiveReturnStar ok userId=%d assetId=%s change=%d',
                $userId, $assetId, $count));

            $redis->hset(sprintf('return_star_%s', $activityConfig['id']),$userId,1);
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('ReturnUserService::receiveReturnStar exception userId=%d assetId=%s change=%d ex=%d:%s trace=%s',
                    $userId, $assetId, $count, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 领取回归之星礼物
     * @param $userId
     * @param $redis
     * @param $type
     * @return mixed
     * @throws FQException
     */
    public function receiveReturnGift($userId, $redis, $type) {
        $time = time();
        $activityConfig = $redis->hGetAll($this->return_user_activity_key);
        if ($time < $activityConfig['start_time']) {
            throw new FQException('活动未开始', 500);
        }
        if ($time >= $activityConfig['end_time']) {
            throw new FQException('活动已结束', 500);
        }
        //判断用户是否有资格领取
        $isHaveReturnAuth = $this->isHaveReturnAuth($userId, $activityConfig,2);
        if (!$isHaveReturnAuth) {
            throw new FQException('很抱歉，您没有达到领取条件哦～', 500);
        }
        $returnStarStatus = $redis->hget(sprintf('return_gift_%s', $activityConfig['id']), $userId);
        if ($returnStarStatus == 1) {
            throw new FQException('每个用户可领取一次', 500);
        }
        $configAssetInfo = json_decode($activityConfig['return_gift'],true);
        $i = $type == 1 ? 0 : 1;
        $configAsset = $configAssetInfo[$i];
        $assetId = $configAsset['assetId'];
        $count = $configAsset['count'];
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetId, $count) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                // 获取用户资产
                $timestamp = time();
                $userAsset = $user->getAssets();
                if ($count < 0) {
                    throw new FQException('资产数量错误', 500);
                }
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'return_user','return_gift', $count);
                $balance = $userAsset->add($assetId, $count, $timestamp, $biEvent);
            });

            Log::info(sprintf('ReturnUserService::receiveReturnGift ok userId=%d assetId=%s change=%d',
                $userId, $assetId, $count));

            $redis->hset(sprintf('return_gift_%s', $activityConfig['id']),$userId,1);
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('ReturnUserService::receiveReturnGift exception userId=%d assetId=%s change=%d ex=%d:%s trace=%s',
                    $userId, $assetId, $count, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }


    /**
     * 充值活动
     * @param $userId
     * @param $redis
     * @return mixed
     */
    public function receiveReturnCharge($userId, $redis) {
        $time = time();
        $activityConfig = $redis->hGetAll($this->return_user_activity_key);
        if ($time < $activityConfig['start_time']) {
            throw new FQException('活动未开始', 500);
        }
        if ($time >= $activityConfig['end_time']) {
            throw new FQException('活动已结束', 500);
        }
        //判断用户是否有资格领取
        $isHaveReturnAuth = $this->isHaveReturnAuth($userId, $activityConfig,1);
        if (!$isHaveReturnAuth) {
            throw new FQException('很抱歉，您没有达到领取条件哦～', 500);
        }
        //判断用户是否在活动开始期间内有充值行为
        $chargeStatus = $this->isHaveChargeAction($userId, $activityConfig);
        if (!$chargeStatus) {
            throw new FQException('请充值任意金额后再来领取吧～', 500);
        }
        $returnChargeStatus = $redis->hget(sprintf('return_charge_%s', $activityConfig['id']), $userId);
        if ($returnChargeStatus == 1) {
            throw new FQException('每个用户可领取一次', 500);
        }
        $configAssetInfo = json_decode($activityConfig['return_charge'],true);
        if (!empty($configAssetInfo)) {
            try {
                Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $configAssetInfo) {
                    // loadUser会锁住用户
                    $user = UserRepository::getInstance()->loadUser($userId);
                    if ($user == null) {
                        throw new FQException('用户不存在', 500);
                    }

                    $timestamp = time();
                    $userAsset = $user->getAssets();
                    foreach ($configAssetInfo as $value) {
                        $assetId = $value['assetId'];
                        $count = $value['count'];

                        // 获取用户资产
                        if ($count < 0) {
                            throw new FQException('资产数量错误', 500);
                        }
                        $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'return_user', 'return_charge', $count);
                        $userAsset->add($assetId, $count, $timestamp, $biEvent);
                        Log::info(sprintf('ReturnUserService::receiveReturnGift ok userId=%d assetId=%s change=%d',
                            $userId, $assetId, $count));
                    }
                });

                $redis->hset(sprintf('return_charge_%s', $activityConfig['id']), $userId, 1);
            } catch (Exception $e) {
                if (!($e instanceof FQException)) {
                    Log::error(sprintf('ReturnUserService::receiveReturnGift exception userId=%d configAssetInfo=%s ex=%d:%s trace=%s',
                        $userId, json_encode($configAssetInfo), $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                }
                throw $e;
            }
        }
    }


    /**
     * todo 删除
     * 获取用户领取权限
     * @param $userId
     * @param $config
     * @param $type
     * @return bool
     */
    public function isHaveReturnAuth($userId, $config, $type) {
        $start_time = $config['start_time'];
        $select_start_time = date('Y-m-d H:i:s', $start_time - 3 * 86400);
        $select_end_time = date('Y-m-d H:i:s', $start_time);
        $loginCount = LoginDetailNewModelDao::getInstance()->getLoginNumber($userId, $select_start_time, $select_end_time);
        if(!empty($loginCount)) {
            return false;
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($type == 2) {
            //普通充值
            $where[] = ['success_time', '<', $config['start_time']];
            $where[] = ['uid', '=', $userId];
            $where[] = ['event_id', '=', BIConfig::$CHARGE_EVENTID];
//            $charge = BIUserAssetModelDao::getInstance()->where($where)->find();
            //vip充值
            $where1[] = ['addtime', '<', date('Y-m-d H:i:s', $config['start_time'])];
            $where1[] = ['uid', '=', $userId];
            $where1[] = ['status', '=', 1];
//            $vipCharge = ChargeDetailModel::getInstance()->where($where1)->find();
            //工会代充
            $where2[] = ['success_time', '<', $config['start_time']];
            $where2[] = ['uid', '=', $userId];
            $where2[] = ['event_id', '=', BIConfig::$REPLACE_CHARGE_EVENTID];
//            $unionCharge = BIUserAssetModelDao::getInstance()->where($where2)->find();
            if (!empty($charge) || !empty($vipCharge) || !empty($unionCharge)) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($userModel->registerTime < date('Y-m-d', $start_time)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 回归活动 如不使用建议删除
     * 检测用户在活动期间有充值行为
     * @param $userId
     */
    public function isHaveChargeAction($userId, $config) {
        //普通充值
        $where[] = ['success_time', '>=', $config['start_time']];
        $where[] = ['uid', '=', $userId];
        $where[] = ['event_id', '=', BIConfig::$CHARGE_EVENTID];
        $charge = BIUserAssetModelDao::getInstance()->getModel($userId)->where($where)->find();
        //vip充值
        $where1[] = ['addtime', '>=', date('Y-m-d H:i:s', $config['start_time'])];
        $where1[] = ['uid', '=', $userId];
        $where1[] = ['status', '=', 1];
        $vipCharge = OrderModelDao::getInstance()->getModel()->where($where1)->find();
        //工会代充
        $where2[] = ['success_time', '>=', $config['start_time']];
        $where2[] = ['uid', '=', $userId];
        $where2[] = ['event_id', '=', BIConfig::$REPLACE_CHARGE_EVENTID];
        $unionCharge = BIUserAssetModelDao::getInstance()->getModel($userId)->where($where2)->find();
        if (!empty($charge) || !empty($vipCharge) || !empty($unionCharge)) {
            return true;
        } else {
            return false;
        }
    }
}