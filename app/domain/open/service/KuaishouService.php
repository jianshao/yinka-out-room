<?php


namespace app\domain\open\service;


use app\domain\exceptions\FQException;
use app\domain\open\dao\KuaishouReportModelDao;
use app\domain\open\dao\PromoteReportDao;
use app\domain\open\model\KuaishouReportModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\queue\producer\KuaishouMessage;
use think\facade\Log;

class KuaishouService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KuaishouService();
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
    public function kuaishouReport($missionId, $orderId, $idfaMD5, $imeiMD5, $callback)
    {
        $model = new KuaishouReportModel;
        $model->factoryType = PromoteFactoryTypeModel::$JUXING;
        $model->missionId = $missionId;
        $model->orderId = $orderId;
        $model->idfaMD5 = $idfaMD5;
        $model->imeiMD5 = $imeiMD5;
        $model->callbackUrl = $callback;
        $model->strDate = date("Ymd");
        $model->createTime = time();
        return KuaishouReportModelDao::getInstance()->storeModel($model);
    }


    /**
     * @param $idfaMD5
     * @param $imeiMD5
     * @param int $eventType
     */
    public function juxingReport($idfaMD5, $imeiMD5, $eventType = 1)
    {
        try {
            $model = null;
            if (!empty($idfaMD5)) {
                if ($idfaMD5 !== '00000000-0000-0000-0000-000000000000') {
                    $model = PromoteReportDao::getInstance()->LoadModelForIdfa($idfaMD5);
                }
            }
            if (!empty($imeiMD5)) {
                $model = PromoteReportDao::getInstance()->LoadModelForImei($imeiMD5);
            }
            if ($model === null) {
                throw new FQException("juxingReport model fatal error empty", 500);
            }
            $callBackUrl = $model->callbackUrl;
            if (!$callBackUrl) {
                throw new FQException("juxingReport callBackUrl empty error", 500);
            }
            $this->callbackSync($model, $eventType);
            return true;
        } catch (FQException $e) {
            Log::error(sprintf("KuaishouService::juxingReport error err=%d:msg=%s strace=%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }

    /**
     * @param KuaishouReportModel $model
     * callbackurl demo http://ad.partner.gifshow.com/track/activate?event_type=1&event_time=1536045380000&callback=DHAJASALKFyk1uCKBYCyXp-iIDS-uHDd_a5SJ9Dbwkqv46dahahd87TW7hhkJkd
     * @param $eventType
     */
    public function callbackSync(KuaishouReportModel $model, $eventType)
    {
        $eventTime = $this->getEventTime();
        $notifyUrl = sprintf("%s&event_type=%d&event_time=%d", $model->callbackUrl, $eventType, $eventTime);
        $data = [
            'url' => $notifyUrl,
            'idfa_md5' => $model->idfaMD5,
            'imei_md5' => $model->imeiMD5,
            'factory_type' => $model->factoryType,
        ];
        KuaishouMessage::getInstance()->notify($data);
    }


    private function getEventTime()
    {
        list($t1, $t2) = explode(" ", microtime());
        return (float)sprintf("%.0f", (floatval($t1) + floatval($t2)) * 1000);
    }


}