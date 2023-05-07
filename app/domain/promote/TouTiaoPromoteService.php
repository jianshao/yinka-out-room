<?php


namespace app\domain\promote;


use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\open\service\ToutiaoService;
use app\domain\user\dao\MemberDetailModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\ChargeEvent;
use app\event\UserLoginEvent;
use app\event\UserRegisterEvent;

//头条推广上报service
class TouTiaoPromoteService extends PromoteService
{
    protected static $instance;
    private $callbackForUserId = "ProblemService_callback_filter";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TouTiaoPromoteService();
        }
        return self::$instance;
    }


    /**
     * TODO 头条归因 激活注册
     * @param UserRegisterEvent $event
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onUserRegisterEvent(UserRegisterEvent $event)
    {
        $clientInfo = $event->clientInfo;
        $imeiMD5 = $clientInfo->imei ?: "";
        $idfaMD5 = $clientInfo->idfa ?: "";
        $oaid = $clientInfo->oaid ?: "";
        $reportReFirst = ToutiaoService::getInstance()->report($idfaMD5, $imeiMD5, 0, $oaid, $event->userId);
        $reportReSecond = ToutiaoService::getInstance()->report($idfaMD5, $imeiMD5, 1, $oaid, $event->userId);
        if (empty($reportReFirst) || empty($reportReSecond)) {
            return false;
        }
        return true;
    }

    /**
     * TODO 头条归因 次留
     * @param UserLoginEvent $event
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
//        load 用户
        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        if ($userModel === null) {
            throw new FQException("fatal error load userinfo error", 500);
        }

//        过滤注册时间
        if ($userModel->registerTime <= 1) {
            return false;
        }
        $loalDateObject = new \DateTime(date("Y-m-d"));
        $registerDateObject = date_create(date("Y-m-d", $userModel->registerTime));
        $dateIntervalModel = date_diff($loalDateObject, $registerDateObject);
        if ($dateIntervalModel->days !== 1) {
            return false;
        }

//        上报次留
        $userDetailModel = MemberDetailModelDao::getInstance()->loadModelForUserId($event->userId);
        $oaid = $userDetailModel->oaid;
        $idfa = $userModel->idfa;
        $imei = $userModel->imei;
        $eventType = 6;
//        过滤
        $filterRe = $this->getCallbackStatusForUidEventType($event->userId, $eventType);
        if ($filterRe === false) {
            return false;
        }

        return ToutiaoService::getInstance()->report($idfa, $imei, $eventType, $oaid, $event->userId);
    }


    /**
     * TODO 头条归因 付费
     * @param ChargeEvent $event
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onChargeEvent(ChargeEvent $event)
    {
//        初始化用户信息数据
        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常");
        }
//        loadmemberDetail 数据表的oaid;
        $userDetailModel = MemberDetailModelDao::getInstance()->loadModelForUserId($event->userId);
        $oaid = $userDetailModel->oaid;
        $idfa = $userModel->idfa;
        $imei = $userModel->imei;
        $eventType = 2;
//        过滤
        $filterRe = $this->getCallbackStatusForUidEventType($event->userId, $eventType);
        if ($filterRe === false) {
            return false;
        }
//        上报用户付费行为
        return ToutiaoService::getInstance()->report($idfa, $imei, $eventType, $oaid, $event->userId);
    }


    /**
     * @info 过滤report状态
     * @param $userId
     * @param $eventType
     * @return bool  推送true 不推送 false
     */
    private function getCallbackStatusForUidEventType($userId, $eventType)
    {
        if (config("config.appDev") === "dev") {
            return true;
        }
        if (empty($userId)) {
            return false;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getFiltercallbackForUserIdKey($userId, $eventType);
        $incrNumber = $redis->incr($cacheKey);
        if ($incrNumber > 1) {
            return false;
        }
        $redis->expire($cacheKey, 172800);
        return true;
    }


    /**
     * @param $userId
     * @param $eventType
     * @return string
     */
    private function getFiltercallbackForUserIdKey($userId, $eventType)
    {
        return sprintf("%s_userId:%s_eventType=%s", $this->callbackForUserId, $userId, $eventType);
    }
}