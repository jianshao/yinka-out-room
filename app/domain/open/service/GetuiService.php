<?php

namespace app\domain\open\service;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\notice\dao\PushReportModelDao;
use app\domain\notice\model\PushReportModel;
use app\domain\open\model\GetuiCallbackModel;
use think\facade\Log;

class GetuiService
{
    protected static $instance;

    private $cachePrefix = "getuiservice_";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GetuiService();
        }
        return self::$instance;
    }

    private function getItemGetuiCallCacheKey(GetuiCallbackModel $getuiCallbackModel)
    {
        return sprintf("%s_c:%s_t:%s_a:%s", $this->cachePrefix . "fitlerItemCall", $getuiCallbackModel->cid, $getuiCallbackModel->taskid, $getuiCallbackModel->actionId);
    }

    /**
     * @param $itemOriginData
     */
    public function filterItemGetuiCall(GetuiCallbackModel $getuiCallbackModel)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getItemGetuiCallCacheKey($getuiCallbackModel);
        $number = $redis->incr($cacheKey);
        if ($number >= 2) {
            throw new FQException("item data is pushed", 500);
        }
        $redis->expire($cacheKey, 86400);
        return true;
    }

    /**
     * @info 个推单条推送
     * @param GetuiCallbackModel $getuiCallbackModel
     * @param $originData
     * @param $unixTime
     * @param $masterSecret
     * @return int
     * @throws FQException
     */
    public function itemGetuiCallBack(GetuiCallbackModel $getuiCallbackModel, $originData, $unixTime, $masterSecret)
    {
        $paramsArr = $getuiCallbackModel->modelTodata();
        $this->authGetuiSign($paramsArr, $masterSecret);
        $model = new PushReportModel();
        $model->platform = "getuipush";
        $model->msgId = $getuiCallbackModel->msgid ?: "";
        $model->taskId = $getuiCallbackModel->taskid ?: "";
        $model->reportTime = $getuiCallbackModel->recvtime ?: 0;
        $model->mobile = $getuiCallbackModel->cid ?: "";
        $model->status = $getuiCallbackModel->actionId ?: "";
        $model->statusDesc = $getuiCallbackModel->desc ?: "";
        $model->ext_1 = $getuiCallbackModel->appid ?: "";
        $model->originParam = $originData;
        $model->createTime = $unixTime;
        return PushReportModelDao::getInstance()->store($model);
    }


    /**
     * @info 验证个推sign
     * @param $paramsArr
     * @param $masterSecret
     * @throws FQException
     */
    private function authGetuiSign($paramsArr, $masterSecret)
    {
        $appid = $paramsArr['appid'] ?? "";
        $cid = $paramsArr['cid'] ?? "";
        $taskid = $paramsArr['taskid'] ?? "";
        $msgid = $paramsArr['msgid'] ?? "";
        $sign = $paramsArr['sign'] ?? "";
        $makeSign = md5(sprintf("%s%s%s%s%s", $appid, $cid, $taskid, $msgid, $masterSecret));
        if ($makeSign !== $sign) {
            $errMsg = sprintf("itemGetuiCallBack fatal error sign error makeSign=%s paramSign=%s masterSecret=%s", $makeSign, $sign, $masterSecret);
            Log::INFO($errMsg);
            throw new FQException($errMsg, 500);
        }
    }


}