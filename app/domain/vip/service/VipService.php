<?php


namespace app\domain\vip\service;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\domain\vip\constant\VipConstant;
use app\domain\vip\dao\VipModelDao;
use app\domain\vip\model\VipModel;
use app\event\VipExpiresEvent;
use app\event\VipWillExpiresEvent;
use app\utils\TimeUtil;
use Exception;

class VipService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new VipService();
        }
        return self::$instance;
    }

    public function processVipWhenUserLogin($userId, $lastLoginTime, $timestamp) {
        $newVipModel = $this->processVipCharge($userId, $timestamp);

        if (!TimeUtil::isSameDay($timestamp, $lastLoginTime) && in_array($newVipModel->level, [1, 2])) {
            // 即将过期
            if ($newVipModel->level == 2) {
                $vipExpiresTime = $newVipModel->svipExpiresTime;
            } else {
                $vipExpiresTime = $newVipModel->vipExpiresTime;
            }
            $diffDay = TimeUtil::calcDiffDays($timestamp, $vipExpiresTime);
            if ($diffDay >= 0) {
                event(new VipWillExpiresEvent($userId, $newVipModel->level, $vipExpiresTime, $diffDay, $timestamp));
            }
        }
    }

    public function processVipCharge($userId, $timestamp) {
        try {
            list($oldVipModel, $newVipModel) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $vip = $user->getVip($timestamp);
                $oldVipModel = $vip->getModel()->copyTo(new VipModel());
                $vip->processVip($timestamp);
                $newVipModel = $vip->getModel();
                return [$oldVipModel, $newVipModel];
            });

        } catch (Exception $e) {
            throw $e;
        }

        if ($oldVipModel->level == 2 && $newVipModel->level < 2) {
            // svip过期了
            event(new VipExpiresEvent($userId, 2, $timestamp));
        }

        if ($oldVipModel->level == 1 && $newVipModel->level < 1) {
            // vip过期了
            event(new VipExpiresEvent($userId, 1, $timestamp));
        }

        return $newVipModel;
    }

    /**
     * @desc 将会员最新的过期时间记录到Redis中，已经过期的不记录
     * @param $userId
     * @param $timestamp
     * @return bool
     */
    public function recordVipExpire($userId, $timestamp)
    {
        $vipModel = VipModelDao::getInstance()->loadVip($userId);
        $redis = RedisCommon::getInstance()->getRedis();
        // 当前为0，vip svip都过期
        if ($vipModel->level == 0) {
            $redis->zRem(VipConstant::USER_VIP_EXP_TIME, $userId);
            $redis->zRem(VipConstant::USER_SVIP_EXP_TIME, $userId);
        }
        // 当前为1，vip正常  svip过期
        if ($vipModel->level == 1) {
            $redis->zAdd(VipConstant::USER_VIP_EXP_TIME, $vipModel->vipExpiresTime, $userId);
            $redis->zRem(VipConstant::USER_SVIP_EXP_TIME, $userId);
        }
        // 当前为2，svip正常  vip过期/正常
        if ($vipModel->level == 2) {
            $redis->zAdd(VipConstant::USER_SVIP_EXP_TIME, $vipModel->svipExpiresTime, $userId);
            if ($vipModel->vipExpiresTime > $timestamp) {
                $redis->zAdd(VipConstant::USER_VIP_EXP_TIME, $vipModel->vipExpiresTime, $userId);
            } else {
                $redis->zRem(VipConstant::USER_VIP_EXP_TIME, $userId);
            }
        }
        return true;
    }


    /**
     * @desc 用户是否充值过vip
     * @param $userId
     * @return bool
     */
    public function isVipPayOpen($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->sIsMember(VipConstant::USER_VIP_PAY, $userId);
    }

    /**
     * @desc 缓存vip支付信息
     * @param $userId
     * @return bool
     */
    public function cacheVipPayInfo($userId): bool
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->sAdd(VipConstant::USER_VIP_PAY, $userId);
        return true;
    }

    /**
     * @desc 获取会员下次扣款时间
     * @param $time
     */
    public function getVipExecuteTime($time)
    {
        $startDate = date('Y-m-d', $time);   //2022-05-10

        $period = VipConstant::PERIOD;
        $advanceDayExecute = VipConstant::ADVANCE_DAY_EXECUTE;
        if (VipConstant::PERIOD_TYPE == 'MONTH'){
            $executeTime = date('Y-m-d', strtotime("$startDate +$period month"));
        } else {
            $executeTime = date('Y-m-d', strtotime("$startDate +$period day"));
        }
        if ($advanceDayExecute){
            $executeTime = date('Y-m-d', strtotime("$executeTime -$advanceDayExecute day"));
        }

        return $executeTime;
    }

    /**
     * @desc 是否存在vip
     * @param $userId
     * @param $level  1:vip   2:svip
     * @return bool
     */
    public function isOpenVip($userId, $level)
    {
        $isOpen = false;
        $user = UserRepository::getInstance()->loadUser($userId);
        if ($user == null) {
            return $isOpen;
        }
        $timestamp = time();
        $vip = $user->getVip($timestamp);
        if ($level == 1) {
            $isOpen = $vip->getVipExpiresTime() > $timestamp;
        } else if ($level == 2) {
            $isOpen = $vip->getSvipExpiresTime() > $timestamp;
        }
        return $isOpen;
    }

}