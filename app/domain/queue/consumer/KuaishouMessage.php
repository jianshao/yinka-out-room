<?php

namespace app\domain\queue\consumer;

use app\domain\open\dao\KuaishouCallbackModelDao;
use app\domain\open\model\KuaishouCallbackModel;
use app\utils\RequestOrm;
use think\facade\Log;

class KuaishouMessage
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KuaishouMessage();
        }
        return self::$instance;
    }

    public function notify($app, $message)
    {
        $data = $message;
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $callbackUrl = $data['url'] ?? "";
        $idfaMD5 = $data['idfa_md5'] ?? "";
        $imeiMD5 = $data['imei_md5'] ?? "";
        $factory_type = $data['factory_type'] ?? "";
        $response = $requestObj->get($callbackUrl);
        $responseData = json_decode($response, true);
        $status = isset($responseData['result']) && $responseData['result'] === 1 ? 1 : 0;
//        记录回调信息入库
        $model = new KuaishouCallbackModel;
        $model->factoryType = $factory_type;
        $model->idfaMD5 = $idfaMD5;
        $model->imeiMD5 = $imeiMD5;
        $model->callbackUrl = $callbackUrl;
        $model->status = $status;
        $model->response = $response;
        $model->strDate = date("Ymd");
        $model->createTime = time();
        $result = KuaishouCallbackModelDao::getInstance()->storeModel($model);
        Log::info(sprintf("consumer KuaishouMessage messageId=%s resMsg=%s params:%s result=%d", $app->getJobId(), $response, json_encode($message), $result));
        $app->delete();
    }
}