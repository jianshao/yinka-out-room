<?php


namespace app\domain\open\service;


use app\domain\exceptions\FQException;
use app\domain\open\dao\BiZhanReportModelDao;
use app\domain\open\dao\PromoteCallbackModelDao;
use app\domain\open\model\BiZhanCallbackConvTypeModel;
use app\domain\open\model\BiZhanCallbackModel;
use app\domain\open\model\BiZhanReportModel;
use app\domain\open\model\PromoteCallbackModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\open\Prefix;
use app\utils\RequestOrm;
use think\facade\Log;

class BiZhanService
{
    protected static $instance;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @param $trackId  string  追踪id
     * @param $accountId string  账户ID
     * @param $campaignId string  计划 ID
     * @param $unitId string  单元 ID
     * @param $creativeId string 创意 ID，
     * @param $os string 客户端操作系统 数字,取 0~3。0 表示 Android，1表示 iOS，2 表示 Windows Phone，3 表示其他
     * @param $imei string  用户终端的 IMEI 原始值为 15 位 IMEI，取其 32 位小写 MD5 编码。
     * @param $callbackUrl string  回调地址（需要 urlencode） 字符串，需 urlencode 编码，如https://cm.bilibili.com/conv/api/conversion/ad/cb/v1?track_id=__track_id__ ,（回传链接中 track_id 会替换成对应值）
     * @param $mac1 string  MAC地址md5
     * @param $idfaMd5 string   iOS IDFA
     * @param $aaId string  Android AdvertisingID
     * @param $androidId string   用户终端的 Android ID
     * @param $oaidMd5 string  安卓匿名设备标识符
     * @param $ts string  客户端触发监测的时间  UTC 时间戳毫秒数
     * @return mixed
     */
    public function report($trackId, $accountId, $campaignId, $unitId, $creativeId, $os, $imei, $callbackUrl, $mac1, $idfaMd5, $aaId, $androidId, $oaidMd5, $ts)
    {
        $unixTime = time();
        $model = new BiZhanReportModel();
        $model->factoryType = PromoteFactoryTypeModel::$BIZHAN;

        $model->trackId = $trackId;
        $model->accountId = $accountId;
        $model->campaignId = $campaignId;
        $model->unitId = $unitId;
        $model->creativeId = $creativeId;
        $model->os = $os;
        $model->imei = $imei;
        $model->callbackUrl = $callbackUrl;
        $model->mac1 = $mac1;
        $model->idfaMd5 = $idfaMd5;
        $model->aaId = $aaId;
        $model->androidId = $androidId;
        $model->oaidMd5 = $oaidMd5;
        $model->ts = $ts;
        $model->strDate = date("Ymd", $unixTime);
        $model->createTime = $unixTime;
        return BiZhanReportModelDao::getInstance()->storeModel($model);
    }

    /**
     * @param $idfaMD5
     * @param $imeiMD5
     * @param string $eventType
     * @param string $oaid
     * @param int $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reportToFactory($idfa, $imei, $eventType = "", $oaid = "", $userId = 0)
    {
        try {
            $biZhanReportModel = null;
            if ($idfa !== "" && $idfa !== '9f89c84a559f573636a47ff8daed0d33' && $idfa !== "__IDFAMD5__") {
                $biZhanReportModel = BiZhanReportModelDao::getInstance()->LoadModelForIdfa($idfa);
            }
            if ($biZhanReportModel === null) {
                if ($oaid !== "" || $imei !== "") {
                    $biZhanReportModel = BiZhanReportModelDao::getInstance()->loadModelForDeviceId($imei, $oaid);
                }
            }
            if ($biZhanReportModel === null) {
                return false;
            }
            return $this->callbackSync($biZhanReportModel, $eventType, $userId);
        } catch (FQException $e) {
            Log::error(sprintf("BiZhanService::reportToFactory error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }


    /**
     * @param BiZhanReportModel $oppoReportModel
     * @param string $actionType
     * @param $userId
     */
    public function callbackSync(BiZhanReportModel $reportModel, $actionType, $userId, $priceCents = 0)
    {
        $convValue = 0;
        if ($actionType === BiZhanCallbackConvTypeModel::$USER_COST) {
            $convValue = $priceCents;
        }
        $BiZhanCallbackModel = new BiZhanCallbackModel();
        $BiZhanCallbackModel->convType = $actionType;
        $BiZhanCallbackModel->convTime = $reportModel->ts;
        $BiZhanCallbackModel->convValue = $convValue;
        $BiZhanCallbackModel->convCount = 1;
        $BiZhanCallbackModel->imei = $reportModel->imei;
        $BiZhanCallbackModel->idfa = $reportModel->idfaMd5;
        $BiZhanCallbackModel->oaid = $reportModel->oaidMd5;
        $BiZhanCallbackModel->mac = $reportModel->mac1;
        $BiZhanCallbackModel->trackId = $reportModel->trackId;
        $paramData = $BiZhanCallbackModel->modelToData();
        $headers = array(
            'Content-Type' => 'application/json',
        );
        $link = sprintf("%s%s", Prefix::$biZhanCallback, http_build_query($paramData));
        $options = [
            'timeout' => 2,
            'connect_timeout' => 2,
        ];
        $requestObj = new RequestOrm($headers, $options);
        $responseString = $requestObj->get($link);  //       response example=> success: {"code":0,"messa ge":"","data":""}  error: {"code":-2,"message":"invalid trackId!","data":null}
//        $responseString='{"code":0,"messa ge":"","data":""}';
        Log::info(sprintf("BiZhanService:callbackSync NotifyMessage link=%s responseString=%s params:%s", $link, $responseString, json_encode($paramData)));
        $responseData = json_decode($responseString, true);
        $status = isset($responseData['code']) && $responseData['code'] === 0 ? 1 : 0;
//        记录回调信息入库
        $unixTime = time();
        $callbackModel = new PromoteCallbackModel();
        $callbackModel->userId = $userId;
        $callbackModel->factoryType = PromoteFactoryTypeModel::$BIZHAN;
        $callbackModel->eventType = BiZhanCallbackConvTypeModel::getEvnetTypeForTypeName($actionType);
        $callbackModel->idfaMD5 = $BiZhanCallbackModel->getIdfa();
        $callbackModel->imeiMD5 = $BiZhanCallbackModel->getImei();
        $callbackModel->oaid = $BiZhanCallbackModel->getOaid();
        $callbackModel->callbackUrl = "";
        $callbackModel->status = $status;
        $callbackModel->response = $responseString;
        $callbackModel->strDate = date("Ymd", $unixTime);
        $callbackModel->createTime = $unixTime;
        $result = PromoteCallbackModelDao::getInstance()->storeModel($callbackModel);
        Log::info(sprintf("BiZhanService:callbackSync resMsg=%s actionType=%d link:%s result=%d", $responseString, $actionType, $link, $result));
        return true;
    }

}