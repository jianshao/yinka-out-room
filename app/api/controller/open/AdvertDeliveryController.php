<?php


namespace app\api\controller\open;

use app\domain\adserving\AdServingModelDao;
use think\facade\Request;
use think\facade\Log;

class AdvertDeliveryController
{
    /**
     * 保存点击信息  appid,source,idfa,callbackaddress,status
     */
    public function saveClickInfo() {
        $params = Request::param();
        try {
            AdServingModelDao::getInstance()->saveData($params);
            return json(['code' => 0, 'msg' => 'success']);
        }catch (\Exception $e) {
            Log::error(sprintf('AdvertDeliveryController  saveClickInfo: code:%d message:%s', $e->getCode(), $e->getMessage()));
            return json(['code' => 1, 'msg' => 'error']);
        }
    }

    /**
     * 去重idfa
     */
    public function uniqueIdfa() {
        try {
            $appid = Request::param('appid');
            $idfa = Request::param('idfa');
            $data = AdServingModelDao::getInstance()->findOne(['appid' => $appid, 'idfa' => $idfa]);
            if (!empty($data)) {
                return json(['code' => 0, 'msg' => 'success', 'data' => [$idfa => $data['status']]]);
            }
            return json(['code' => 0, 'msg' => 'success', 'data' => [$idfa => 0]]);
        } catch (\Exception $e) {
            Log::error(sprintf('AdvertDeliveryController  uniqueIdfa: code:%d message:%s', $e->getCode(), $e->getMessage()));
            return json(['code' => 1, 'msg' => 'error']);
        }

    }
}