<?php

namespace app\domain\open\service;


//华为api接口封装
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\open\dao\HuaweiReportModelDao;
use app\domain\open\dao\PromoteCallbackModelDao;
use app\domain\open\model\HuaweiCallbackModel;
use app\domain\open\model\HuaweiReportModel;
use app\domain\open\model\HuaweiTokenModel;
use app\domain\open\model\PromoteCallbackModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\open\Prefix;
use app\utils\RequestOrm;
use think\facade\Log;

class HuaweiGalleryService
{
    protected static $instance;
    protected $client_id;
    protected $client_secret;
    protected $appId;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $object = new HuaweiGalleryService();
            $object->client_id = Prefix::$huaweiClientId;
            $object->client_secret = Prefix::$huaweiClientSecret;
            $object->appId = Prefix::$huaweiAppId;
            self::$instance = $object;
        }
        return self::$instance;
    }

    private function getTokenCacheKey()
    {
        return Prefix::$huaweiTokenCacheKey;
    }

    /**
     * @return HuaweiTokenModel
     */
    public function getToken()
    {
        $cacheKey = $this->getTokenCacheKey();
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheData = $redis->get($cacheKey);
        if (!empty($cacheData)) {
            $resultData = json_decode($cacheData, true);
            $model = new HuaweiTokenModel();
            $model->dataToModel($resultData);
            return $model;
        }
        $resultString = $this->loadToken();
        $resultData = json_decode($resultString, true);
        $model = new HuaweiTokenModel();
        $model->dataToModel($resultData);
        if ($model->accessToken !== "") {
            $expTime = $model->expires_in - 300;
            $redis->setex($cacheKey, $expTime, $resultString);
        }
        return $model;
    }

    /**
     * @return string
     */
    private function loadToken()
    {
        $link = Prefix::$huaweiLoadToken;
        if (empty($link)) {
            return "";
        }
        $paramData = [
            'grant_type' => "client_credentials",
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ];
        $headers = array('Content-Type' => 'application/json');
        $options = [
            'timeout' => 2,
            'connect_timeout' => 2,
        ];
        $requestObj = new RequestOrm($headers, $options);
        $result = $requestObj->post($link, json_encode($paramData));
        Log::info(sprintf("consumer NotifyMessage link=%s resMsg=%s params:%s", $link, $result, json_encode($paramData)));
        return $result;
    }

    /**
     * @info 华为上报 入口
     * @param string $oaid
     * @param string $actionType "1":应用激活，"7":注册 "3":次日留存 "6":授权 "9":关键页面访问 "4"付费
     * @param int $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function report(string $oaid, $actionType = "1", $userId = 0)
    {
        try {
            $huaweiReportModel = HuaweiReportModelDao::getInstance()->loadModelForDeviceId($oaid);
            if ($huaweiReportModel === null) {
                return false;
            }
            if (empty($huaweiReportModel->callback) || empty($huaweiReportModel->oaid)) {
                return false;
            }
            return $this->callbackSync($huaweiReportModel, $actionType, $userId);
        } catch (FQException $e) {
            Log::error(sprintf("ToutiaoService::toutiaoReport error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }

    }

    /**
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException]
     */
    public function testReport()
    {
//        $oaid = "1a9932a4131cfc306affb1e1e8065c9feedacaf806fe01c25296b8c09479da62";
        $oaid = "aaaoooaid";
        return HuaweiGalleryService::getInstance()->report($oaid, "1");
    }

    /**
     * @info 华为上报
     * TODO https://developer.huawei.com/consumer/cn/doc/distribution/promotion/ocpd-callback-api-0000001193252525#section145575382294
     * @param HuaweiReportModel $HuaweiChannelModel
     * @param $actionType
     * @param $userId
     * @param string $deviceIdType
     * @return bool
     */
    public function callbackSync(HuaweiReportModel $huaweiChannelModel, $actionType, $userId, $deviceIdType = "OAID")
    {
        $actionTime = $this->getCurrentMilis();
        $reportModel = new HuaweiCallbackModel();
        $reportModel->appId = $this->appId;
        $reportModel->deviceIdType = $deviceIdType;
        $reportModel->deviceId = $huaweiChannelModel->oaid;
        $reportModel->actionTime = $actionTime;
        $reportModel->actionType = $actionType;
        $reportModel->callBack = $huaweiChannelModel->callback;

        $isset = $this->filterCallBack($huaweiChannelModel->oaid, $actionType, $userId, PromoteFactoryTypeModel::$HUAWEI);
        if ($isset) {
            return false;
        }

        $link = Prefix::$HuaweiTrackActivate;
        $accessTokenModel = $this->getToken();
        $paramData = $reportModel->modelToData();
        $headers = array(
            'Content-Type' => 'application/json',
            'client_id' => $this->client_id,
            'Authorization' => sprintf("Bearer %s", $accessTokenModel->accessToken)
        );
        $options = [
            'timeout' => 2,
            'connect_timeout' => 2,
        ];
        $requestObj = new RequestOrm($headers, $options);
        $responseString = $requestObj->post($link, json_encode($paramData));
        Log::info(sprintf("consumer NotifyMessage link=%s responseString=%s params:%s", $link, $responseString, json_encode($paramData)));
        $responseData = json_decode($responseString, true);
        $status = isset($responseData['code']) && $responseData['code'] === 0 ? 1 : 0;

        $callbackUrl = $huaweiChannelModel->callback;
//        记录回调信息入库
        $callbackModel = new PromoteCallbackModel;
        $callbackModel->userId = $userId;
        $callbackModel->factoryType = PromoteFactoryTypeModel::$HUAWEI;
        $callbackModel->eventType = $actionType;
        $callbackModel->oaid = $huaweiChannelModel->oaid;
        $callbackModel->callbackUrl = $callbackUrl;
        $callbackModel->status = $status;
        $callbackModel->response = $responseString;
        $callbackModel->strDate = date("Ymd");
        $callbackModel->createTime = time();
        $result = PromoteCallbackModelDao::getInstance()->storeModel($callbackModel);
        Log::info(sprintf("HuaweiGalleryService callbackSync resMsg=%s actionType=%d params:%s result=%d", $responseString, $actionType, $link, $result));
        return true;
    }

    /**
     */

    /**
     * oaid eventid userid channletype =>status
     * 过滤渠道如果已经上报就不处理
     * @param $oaid
     * @param $actionType
     * @param $userId
     * @param $factory_type
     * @return bool
     */
    private function filterCallBack($oaid, $actionType, $userId, $factory_type)
    {
        $status = PromoteCallbackModelDao::getInstance()->getCallbackStatus($oaid, $actionType, $userId, $factory_type);
        if ($status === 1) {
            return true;
        }
        return false;
    }

    /**
     * @return int
     * example:1648730924084
     *
     */
    public function getCurrentMilis()
    {
        $mill_time = microtime();
        $timeInfo = explode(' ', $mill_time);
        $milis_time = sprintf('%d%03d', $timeInfo[1], $timeInfo[0] * 1000);
        return (int)$milis_time;
    }

    /**
     * storeHuaweiReport
     * @param $oaid
     * @param $taskid
     * @param $subTaskId
     * @param $rtaid
     * @param $channel
     * @param $callback
     * @return int
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeHuaweiReport($oaid, $taskid, $subTaskId, $rtaid, $channel, $callback)
    {
        if (empty($oaid) || empty($callback)) {
            throw new FQException("huawei Report error", 500);
        }
        $unixTime = time();
        $model = new HuaweiReportModel;
        $model->oaid = $oaid;
        $model->factoryType = PromoteFactoryTypeModel::$HUAWEI;
        $model->taskid = $taskid;
        $model->subTaskId = $subTaskId;
        $model->rtaid = $rtaid;
        $model->callback = $callback;
        $model->channel = $channel;
        $model->strDate = date("Ymd", $unixTime);
        $model->createTime = $unixTime;
        return (int)HuaweiReportModelDao::getInstance()->insertIfExists($model);
    }

}