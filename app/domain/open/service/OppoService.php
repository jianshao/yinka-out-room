<?php


namespace app\domain\open\service;


use app\domain\exceptions\FQException;
use app\domain\open\dao\OppoReportModelDao;
use app\domain\open\dao\PromoteCallbackModelDao;
use app\domain\open\model\OppoCallbackModel;
use app\domain\open\model\OppoReportModel;
use app\domain\open\model\PromoteCallbackModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\open\Prefix;
use app\utils\Aes;
use app\utils\CommonUtil;
use app\utils\RequestOrm;
use think\facade\Log;

class OppoService
{
    protected static $instance;

    private $baseUrl = "https://analytics.oceanengine.com/api/v2/conversion"; // 头条上报地址

    private $baseRegisterUrl = "https://ad.oceanengine.com/track/activate"; //注册上报地址

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @info OPPO上报
     * @param $adid
     * @param $imeiMd5
     * @param $oaid
     * @param $timestamp
     * @param $androidId
     * @return int|string
     */
    public function oppoReport($adid, $imeiMd5, $oaid, $timestamp, $androidId)
    {
        $unixTime = time();
        $model = new OppoReportModel();
        $model->factoryType = PromoteFactoryTypeModel::$OPPO;
        $model->adid = $adid;
        $model->imeiMd5 = $imeiMd5;
        $model->oaid = $oaid;
        $model->tempstamp = $timestamp;
        $model->androidId = $androidId;
        $model->strDate = date("Ymd", $unixTime);
        $model->createTime = $unixTime;
        return OppoReportModelDao::getInstance()->storeModel($model);
    }

    /**
     * @param OppoReportModel $oppoReportModel
     * @param $actionType
     * @param $userId
     */
    public function callbackSync(OppoReportModel $oppoReportModel, $actionType, $userId)
    {
        $microtime = CommonUtil::getCurrentMilis();

        $OppoCallbackModel = new OppoCallbackModel;
        $OppoCallbackModel->ouId = $this->loadToAeskey($oppoReportModel->oaid);
        $OppoCallbackModel->timestamp = $microtime;
        $OppoCallbackModel->pkg = $this->loadPkg();
        $OppoCallbackModel->dataType = $actionType;
        $OppoCallbackModel->channel = 1;
        $OppoCallbackModel->type = 0;
        $OppoCallbackModel->ascribeType = 1;
        $OppoCallbackModel->adId = $oppoReportModel->adid;
        $paramData = $OppoCallbackModel->modelToData();
        $salt = $this->loadSalt();
        $signature = $OppoCallbackModel->loadSignature($microtime, $salt);
        $headers = array(
            'signature' => $signature,
            'timestamp' => $microtime,
            'Content-Type' => 'application/json',
        );
        $link = Prefix::$oppoCallback;
        $options = [
            'timeout' => 2,
            'connect_timeout' => 2,
        ];
        $requestObj = new RequestOrm($headers, $options);
        $responseString = $requestObj->post($link, json_encode($paramData));  //       response example: {"ret":0}
        Log::info(sprintf("OppoService:callbackSync NotifyMessage link=%s responseString=%s params:%s", $link, $responseString, json_encode($paramData)));
        $responseData = json_decode($responseString, true);
        $status = isset($responseData['ret']) && $responseData['ret'] === 0 ? 1 : 0;
//        记录回调信息入库
        $unixTime = time();
        $callbackModel = new PromoteCallbackModel;
        $callbackModel->userId = $userId;
        $callbackModel->factoryType = PromoteFactoryTypeModel::$OPPO;
        $callbackModel->eventType = $actionType;
        $callbackModel->oaid = $oppoReportModel->oaid;
        $callbackModel->callbackUrl = "";
        $callbackModel->status = $status;
        $callbackModel->response = $responseString;
        $callbackModel->strDate = date("Ymd", $unixTime);
        $callbackModel->createTime = $unixTime;
        $result = PromoteCallbackModelDao::getInstance()->storeModel($callbackModel);
        Log::info(sprintf("OppoService:callbackSync resMsg=%s actionType=%d link:%s result=%d", $responseString, $actionType, $link, $result));
        return true;
    }

    /**
     * @param OppoReportModel $oppoReportModel
     * @param $actionType
     * @param $userId
     * @return bool
     */
    public function silentSync(OppoReportModel $oppoReportModel, $actionType, $userId)
    {
//        silent模式不上报第三方厂商
        $status = 1;
        $responseString = "";
        $unixTime = time();
        $callbackModel = new PromoteCallbackModel;
        $callbackModel->userId = $userId;
        $callbackModel->factoryType = PromoteFactoryTypeModel::$OPPO;
        $callbackModel->eventType = $actionType;
        $callbackModel->oaid = $oppoReportModel->oaid;
        $callbackModel->callbackUrl = "";
        $callbackModel->status = $status;
        $callbackModel->response = $responseString;
        $callbackModel->strDate = date("Ymd", $unixTime);
        $callbackModel->createTime = $unixTime;
        $result = PromoteCallbackModelDao::getInstance()->storeModel($callbackModel);
        $link = "";
        Log::info(sprintf("OppoService:silentSync resMsg=%s actionType=%d link:%s result=%d", $responseString, $actionType, $link, $result));
        return true;
    }


    private function loadToAeskey($param)
    {
        $Aes = new Aes();
        $cipher_algo = "aes-128-ecb";
        $key = $this->loadAesKey();
        return $Aes->aesEncryptOrigin($param, $key, $cipher_algo);
    }

    private function loadAesKey()
    {
        return "XGAXicVG5GMBsx5bueOe4w==";
    }

    private function loadSalt()
    {
        return "e0u6fnlag06lc3pl";
    }

    private function loadSignature()
    {
        return "";
    }

    private function loadPkg()
    {
        return "com.party.ccp";
    }


    /**
     * @info 上报厂商
     * @param string $oaid
     * @param int $actionType 转化类型 1:激活，2:注册
     * @param int $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reportToFactory(string $oaid, $actionType = 1, $userId = 0)
    {
        try {
            $oppoReportModel = OppoReportModelDao::getInstance()->loadModelForDeviceId($oaid);
            if ($oppoReportModel === null) {
                return false;
            }
            if (empty($oppoReportModel->oaid)) {
                return false;
            }
            return $this->callbackSync($oppoReportModel, $actionType, $userId);
        } catch (FQException $e) {
            Log::error(sprintf("ToutiaoService::toutiaoReport error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }


    /**
     * @Info 记录行为不上报厂商
     * @param $oaid
     * @param $actionType
     * @param $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reportToSilent($oaid, $actionType, $userId)
    {
        try {
            $oppoReportModel = OppoReportModelDao::getInstance()->loadModelForDeviceId($oaid);
            if ($oppoReportModel === null) {
                return false;
            }
            if (empty($oppoReportModel->oaid)) {
                return false;
            }
            return $this->silentSync($oppoReportModel, $actionType, $userId);
        } catch (FQException $e) {
            Log::error(sprintf("ToutiaoService::reportToSilent error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }


}