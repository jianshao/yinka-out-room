<?php


namespace app\domain\open\service;


use app\domain\exceptions\FQException;
use app\domain\open\dao\PromoteCallbackModelDao;
use app\domain\open\dao\PromoteReportDao;
use app\domain\open\dao\ToutiaoReportModelDao;
use app\domain\open\model\PromoteCallbackModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\open\model\ToutiaoReportModel;
use app\utils\RequestOrm;
use think\facade\Log;

class ToutiaoService
{
    protected static $instance;

    private $baseUrl = "https://analytics.oceanengine.com/api/v2/conversion"; // 头条上报地址

    private $baseRegisterUrl = "https://ad.oceanengine.com/track/activate"; //注册上报地址

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ToutiaoService();
        }
        return self::$instance;
    }

    /**
     * @param $missionId
     * @param $orderId
     * @param $idfaMD5
     * @param $imeiMD5
     * @param $callback
     * @return int|string
     */
    public function toutiaoReport($aid, $cid, $idfa, $imei, $mac, $oaid, $androidid, $os, $tempstamp, $callback)
    {
        $model = new ToutiaoReportModel();
        $model->factoryType = PromoteFactoryTypeModel::$TOUTIAO;
        $model->aid = $aid;
        $model->cid = $cid;
        $model->idfa = $idfa;
        $model->imei = $imei;
        $model->mac = $mac;
        $model->oaid = $oaid;
        $model->androidid = $androidid;
        $model->os = $os;
        $model->tempstamp = $tempstamp;
        $model->callback = $callback;
        $model->strDate = date("Ymd");
        $model->createTime = time();
        return ToutiaoReportModelDao::getInstance()->storeModel($model);
    }

    /**
     * @info 星图上报
     * @param $idfa
     * @param $imei
     * @param $mac
     * @param $oaid
     * @param $androidid
     * @param $os
     * @param $tempstamp
     * @param $demandId
     * @param $itemId
     * @param $callback
     * @return int|string
     */
    public function xingtuReport($idfa, $imei, $mac, $oaid, $androidid, $os, $tempstamp, $demandId, $itemId, $callback)
    {
        $model = new ToutiaoReportModel();
        $model->factoryType = PromoteFactoryTypeModel::$XINGTU;
        $model->aid = "";
        $model->cid = "";
        $model->idfa = $idfa;
        $model->imei = $imei;
        $model->mac = $mac;
        $model->oaid = $oaid;
        $model->androidid = $androidid;
        $model->os = $os;
        $model->tempstamp = $tempstamp;
        $model->callback = $callback;
        $model->strDate = date("Ymd");
        $model->createTime = time();
        $model->ext1 = $demandId;
        $model->ext2 = $itemId;
        return ToutiaoReportModelDao::getInstance()->storeModel($model);
    }

    /**
     * @param $idfaMD5
     * @param $imeiMD5
     * @param int $eventType
     * @param string $oaid
     * @param int $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function report($idfa, $imei, $eventType = 1, $oaid = "", $userId = 0)
    {
        try {
            $model = null;
            if (!empty($idfa)) {
                if ($idfa !== '00000000-0000-0000-0000-000000000000') {
                    $model = PromoteReportDao::getInstance()->LoadModelForIdfa($idfa);
                }
            }
            if (!empty($imei) || $oaid !== "") {
                $model = PromoteReportDao::getInstance()->LoadModelForImei($imei, $oaid);
            }
            if ($model === null) {
                return false;
            }
            $callBackUrl = $model->callback;
            if (empty($callBackUrl)) {
                return false;
            }
            return $this->callbackSync($model, $eventType, $userId);
        } catch (FQException $e) {
            Log::error(sprintf("ToutiaoService::toutiaoReport error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }

    private function makeParam(ToutiaoReportModel $model, $eventType)
    {
        $unixTimeStamp = $this->getUnixTimeStamp();
        $platform = $model->os === 0 ? "android" : "ios";
        return json_encode([
            'event_type' => $eventType,
            'context' => [
                'ad' => [
                    'callback' => $model->callback,
                    'match_type' => 0
                ],
                'device' => [
                    'platform' => $platform,
                    'imei' => $model->imei
                ],
                'timestamp' => $unixTimeStamp,
            ],
        ]);
    }

    /**
     * @info 头条上报 记录db
     * TODO https://open.oceanengine.com/labels/7/docs/1696710656359439
     * http://ad.oceanengine.com/track/activate/?callback={callback_param}&muid={muid}&os={os}&source={source}&conv_time={conv_time}&event_type={event_type}&signature={signature}
     * @param ToutiaoReportModel $model
     * @param $eventType
     * @param $userId
     * @return bool
     */
    public function callbackSync(ToutiaoReportModel $model, $eventType, $userId)
    {
        $link = $this->makeCallbackLink($model, $eventType);
        if ($link === "") {
            $dataArr = ToutiaoReportModelDao::getInstance()->modelToData($model);
            Log::error(sprintf("ToutiaoService callbackSync resMsg=%s eventType=%d", $dataArr, $eventType));
            return false;
        }
        $headers = array('Content-Type' => 'application/json');
        $options = [
            'timeout' => 2,
            'connect_timeout' => 2,
        ];
        $requestObj = new RequestOrm($headers, $options);
        $response = $requestObj->get($link);
        $responseData = json_decode($response, true);

        $status = isset($responseData['ret']) && $responseData['ret'] === 0 ? 1 : 0;
        $factory_type = $model->factoryType;
        $callbackUrl = $model->callback;
//        记录回调信息入库
        $callbackModel = new PromoteCallbackModel;
        $callbackModel->userId = $userId;
        $callbackModel->factoryType = $factory_type;
        $callbackModel->eventType = $eventType;
        $callbackModel->idfaMD5 = $model->idfa;
        $callbackModel->imeiMD5 = $model->imei;
        $callbackModel->oaid = $model->oaid;
        $callbackModel->callbackUrl = $callbackUrl;
        $callbackModel->status = $status;
        $callbackModel->response = $response;
        $callbackModel->strDate = date("Ymd");
        $callbackModel->createTime = time();
        $result = PromoteCallbackModelDao::getInstance()->storeModel($callbackModel);
        Log::info(sprintf("ToutiaoService callbackSync resMsg=%s eventType=%d params:%s result=%d", $response, $eventType, $link, $result));
        return true;
    }

    /**
     * @param ToutiaoReportModel $model
     * @param $eventType
     * @return string
     */
    private function makeCallbackLink(ToutiaoReportModel $model, $eventType)
    {
        if ($model->os === 0) {
            if ($model->imei !== "") {
                return sprintf("%s?callback=%s&imei=%s&os=%s&conv_time=%d&event_type=%d", $this->baseRegisterUrl, urlencode($model->callback), $model->imei, $model->os, time(), $eventType);
            }
            if ($model->oaid !== "") {
                return sprintf("%s?callback=%s&oaid=%s&os=%s&conv_time=%d&event_type=%d", $this->baseRegisterUrl, urlencode($model->callback), $model->oaid, $model->os, time(), $eventType);
            }
            return "";
        }
        return sprintf("%s?callback=%s&idfa=%s&os=%s&conv_time=%d&event_type=%d", $this->baseRegisterUrl, urlencode($model->callback), $model->idfa, $model->os, time(), $eventType);
    }

    /**
     * @return false|string
     *        1604888786102
     *        1646122161139
     */
    private function getUnixTimeStamp()
    {
        $unixTimeStamp = microtime(true);
        $result = str_replace(".", "", $unixTimeStamp);
        return substr($result, 0, -1);
    }

}