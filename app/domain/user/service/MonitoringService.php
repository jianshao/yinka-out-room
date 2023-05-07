<?php


namespace app\domain\user\service;


use app\common\AliPayEasyCommon;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\dao\MonitoringModelDao;
use app\domain\dao\UserIdentityModelDao;
use app\domain\exceptions\FQException;
use app\domain\models\MonitoringModel;
use app\domain\user\model\TeenModel;
use app\domain\user\UserRepository;
use app\event\SwitchMonitorEvent;
use app\utils\CommonUtil;
use think\facade\Log;

class MonitoringService
{
    protected static $instance;
    protected $monitoringTime = 60 * 40;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MonitoringService();
        }
        return self::$instance;
    }

    public function runMonitorImpl($userId)
    {
        $model = MonitoringModelDao::getInstance()->findByUserId($userId);
        if (empty($model)) {
            throw new FQException('没有开启青少年模式', 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->getMonitorKey($userId);
        $have = $redis->get($key);
        if (empty($have)) {
            $time = SurplusTime();
            $endTime = time() + $this->getMonitoringTime();
            $redis->setex($key, $time, $endTime);
        }
        return true;
    }

    /**
     * @Info 查询用户是否开启青少年模式，获取青少年可玩的剩余时间戳
     * @param $userId
     * @return array|int[]  [$open 是否开启青少年模式，$expire 剩余的时间戳]
     * @throws FQException
     */
    public function getMonitor($userId)
    {
        $model = MonitoringModelDao::getInstance()->findByUserId($userId, true);
        if (empty($model)) {
            return [0, 0];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->getMonitorKey($userId);
        return [1, intval($redis->get($key))];
    }

    protected function getMonitoringTime()
    {
//        if (config("config.appDev") == 'dev') {
//            return 30;
//        }
        return $this->monitoringTime;
    }

    private function getMonitorKey($userId)
    {
        return sprintf("checkteen_%d", $userId);
    }

    private function filterRenewalTimeKey($userId)
    {
        return sprintf("filterRenewalTimeKey_%d", $userId);
    }


    /**
     * @info 续期到期时间
     * @param $userId
     * @param $password
     * @throws FQException
     */
    public function renewalTime($userId, $password)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use(
                $userId, $user, $password
            ) {
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $model = MonitoringModelDao::getInstance()->findByUserId($userId);
                if ($model == null) {
                    throw new FQException('你还没有开启青少年', 500);
                }
                if ($model->monitoringPassword != md5($password)) {
                    throw new FQException('密码错误', 202);
                }
                $redis = RedisCommon::getInstance()->getRedis();
                $expireTime = SurplusTime();
                $key = $this->getMonitorKey($userId);
                $model->monitoringEndTime = time() + $this->getMonitoringTime();
                MonitoringModelDao::getInstance()->updateMonitoringEndTime($userId, $model->monitoringEndTime);
                $redis->setex($key, $expireTime, $model->monitoringEndTime);
                Log::info(sprintf('MonitoringService::renewalTime userId=%d endTime=%d',
                    $userId, $model->monitoringEndTime));
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function monitorTime($userId)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        try {
            return Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function() use($user, $userId) {
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $model = MonitoringModelDao::getInstance()->findByUserId($userId);
                if (is_null($model)) {
                    throw new FQException('操作失败没有开启青少年', 500);
                }
                return max(0, $model->monitoringEndTime - time());
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function switchMonitor($userId, $password)
    {
        try {
            $user = UserRepository::getInstance()->loadUser($userId);
            $switchStatus = Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use(
                $user, $password, $userId
            ) {
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $md5Password = md5($password);
                $model = MonitoringModelDao::getInstance()->findByUserId($userId);
                if ($model == null) {
                    $model = new MonitoringModel();
                    $model->userId = $userId;
                    $model->password = $password;
                    $model->monitoringPassword = $md5Password;
                    $model->monitoringEndTime = time() + $this->getMonitoringTime();
                    MonitoringModelDao::getInstance()->insertModel($model);
                    return true;
                } else {
                    if ($user->getUserModel()->attestation == 1) {
                        // 实名认证通过，判断年龄
                        $userIdentityModel = UserIdentityModelDao::getInstance()->loadModelForUserId($user->getUserId());
                        if (!empty($userIdentityModel)) {
                            $userAge = CommonUtil::getAge($userIdentityModel->certno);
                            if ($userAge < 16) {
                                throw new FQException('未成年用户无法关闭青少年模式', 500);
                            }
                        }
                    }

                    if ($model->monitoringPassword != $md5Password) {
                        throw new FQException('密码错误', 500);
                    }
                    MonitoringModelDao::getInstance()->removeByUserId($userId);
                    return false;
                }
            });
            event(new SwitchMonitorEvent($userId,$switchStatus,time()));
            return $switchStatus;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setMonitorPassword($userId, $oldPassword, $newPassword)
    {
        try {
            $user = UserRepository::getInstance()->loadUser($userId);
            return Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() use($userId, $user, $oldPassword, $newPassword) {
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $model = MonitoringModelDao::getInstance()->findByUserId($userId);
                if ($model == null) {
                    throw new FQException('您还未开启', 500);
                }

                $md5OldPassword = md5($oldPassword);

                if ($model->monitoringPassword != $md5OldPassword) {
                    throw new FQException('密码错误', 500);
                }

                if (!empty($newPassword)) {
                    $md5Password = md5($newPassword);
                    MonitoringModelDao::getInstance()->updateMonitoringPassword($userId, $md5Password, $newPassword);
                    return true;
                }
                return false;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delMonitoringData($userId)
    {
        try {
            $user = UserRepository::getInstance()->loadUser($userId);
            Sharding::getInstance()->getConnectModel('commonMaster',0)->transaction(function() use($userId, $user) {
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                //将用户监控模式充值
                $model = MonitoringModelDao::getInstance()->findByUserId($userId);
                if (is_null($model)) {
                    throw new FQException('申请失败', 500);
                }
                MonitoringModelDao::getInstance()->updateStatus($userId, 1);
                Log::info(sprintf('MonitoringService::delMonitoringData ok userId=%d', $userId));
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function checkTeen($userId)
    {
        $isTeen = 0;
        $userIdentityModel = UserIdentityModelDao::getInstance()->loadModelForUserId($userId);
        if (!empty($userIdentityModel)) {
            $userAge = CommonUtil::getAge($userIdentityModel->certno);
            if ($userAge < 16) {
                $isTeen = 1;
            }
        }

        $model = MonitoringModelDao::getInstance()->findByUserId($userId, true);
        if ($model != null) {
            $open = 1;
        } else {
            $open = 2;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $key = 'checkteen_' . $userId;
        $have = $redis->get($key);
        if (empty($have)) {
            $time = SurplusTime();
            $redis->setex($key, $time, $userId);
            return [1, $open, $isTeen];
        }
        return [2, $open, $isTeen];
    }


    /**
     * @param $userId
     * @return TeenModel
     */
    public function checkTeenModel($userId, $hours = null)
    {
        if (is_null($hours)) {
            $hours = date("H");
        }
        $teenModel = new TeenModel();
        $model = MonitoringModelDao::getInstance()->issetUserId($userId);
        if (empty($model)) {
            $teenModel->Status = 2;
            return $teenModel;
        }
        $teenModel->Status = 1;
        $redis = RedisCommon::getInstance()->getRedis();
        $key = 'checkteen_' . $userId;
        $have = $redis->get($key);
        $teenModel->Endtime = $have;
//        初始化时间点
        $teenModel->BlockStartTime = mktime(22, 0, 0, date('m'), date('d'), date('Y'));
        $teenModel->BlockEndTime = mktime(6, 0, 0, date('m'), date('d') + 1, date('Y'));
        if ($hours < 6) {
            $teenModel->BlockStartTime = mktime(22, 0, 0, date('m'), date('d') - 1, date('Y'));
            $teenModel->BlockEndTime = mktime(6, 0, 0, date('m'), date('d'), date('Y'));
        }

        //        test
//        $teenModel->BlockStartTime = mktime(10, 0, 0, date('m'), date('d'), date('Y'));
//        $teenModel->BlockEndTime = mktime(6, 0, 0, date('m'), date('d') + 1, date('Y'));
        return $teenModel;
    }

    public function resetMonitor($userId, $certName, $certNo, $bizCode, $channel, $config)
    {
        // $bizCode = "FACE_ALIPAY_SDK";
        $outerOrderNo = CommonUtil::createOrderNo($userId);
        $certifyId = AliPayEasyCommon::getInstance()->init($outerOrderNo, $certName, $certNo, $channel, $bizCode, $config);
        $url = AliPayEasyCommon::getInstance()->certify($certifyId);
        return [$url, $certifyId];
    }

    public function queryMonitor($userId, $certifyId)
    {
        $result = AliPayEasyCommon::getInstance()->query($certifyId);
        Log::info(sprintf('MonitoringService.queryMonitor userId=%d certifyId=%s', $userId, $certifyId));
        if (empty($result)) {
            throw new FQException('申诉失败', 500);
        }
        if ($result->passed != 'T') { //认证通过 T
            throw new FQException('申诉失败', 500);
        }

        $model = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($model){
            MonitoringModelDao::getInstance()->removeByUserId($userId);
        }
    }
}
