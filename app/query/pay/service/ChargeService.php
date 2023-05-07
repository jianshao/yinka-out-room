<?php


namespace app\query\pay\service;

use app\domain\pay\ProductSystem;
use app\domain\pay\ProductTypes;
use app\query\pay\dao\UserChargeStaticsModelDao;
use app\query\pay\dao\OrderModelDao;
use app\utils\ArrayUtil;
use think\facade\Log;

class ChargeService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChargeService();
        }
        return self::$instance;
    }

    public function isFirstCharged($userId)
    {
        $statics = UserChargeStaticsModelDao::getInstance()->loadUserChargeStatics($userId);
        return ($statics != null && $statics->chargeTimes > 0);
    }

    public function walletDetails($page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime, $type = [])
    {
        try {
            list($total, $Models) = $this->getChargeDetail($page, $pageNum, $userId, $queryStartTime, $queryEndTime, $type);
        } catch (\Exception $e) {
            Log::error(sprintf('BiOrderService::newGetDetailList $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return [0, []];
        }

        if (!is_array($Models) || count($Models) === 0) {
            return [0, []];
        }

        $ret = [];
        foreach ($Models as $model) {
            if ($assetType == 'vip'){
                $itemData = $this->getVipContent($model);
            }else {
                $itemData = $this->getChargeContent($model);
            }
            $itemData['assetType'] = $assetType;
            $ret[] = $itemData;
        }
        return [$total, $ret];
    }

    private function getChargeDetail($page, $pageNum, $userId, $queryStartTime, $queryEndTime, $type = [])
    {
        return OrderModelDao::getInstance()->walletDetail($page, $pageNum, $userId, $queryStartTime, $queryEndTime, $type);
    }


    public function getChargeContent($model): array
    {
        $beanChargeMap = [
            '1' => '支付宝app支付',
            '2' => '支付宝网页支付',
            '3' => '微信app支付',
            '4' => '公众号支付',
            '13' => '微信H5支付',
            '15' => '微信扫码支付',
            '16' => '支付宝H5支付',
            '22' => '苹果支付',
            'default' => '支付'
        ];
        $key = $model->payChannel;
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => ArrayUtil::safeGet($beanChargeMap, $key, '支付')]
                ],
                'timestamp' => $model->finishTime,
                'number' => (string)$model->rmb,
                'status' => (int)$model->status,   //0未支付，2已支付
            ];
    }

    public function getVipContent($model): array
    {
        $product = ProductSystem::getInstance()->findProduct($model->productId);
        $typeStr = '';
        if ($model->type == ProductTypes::$VIP) {
            $typeStr = 'VIP';
        } else if ($model->type == ProductTypes::$SVIP) {
            $typeStr = 'SVIP';
        }

        $content = sprintf("开通%s%s", $typeStr, $product->chargeMsg);
        // 自动扣款的订单
        if ($model->isActive == 4){
            $content = sprintf("%s%s", $typeStr, '连续包月自动扣费');
        }

        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => $content]
                ],
                'timestamp' => $model->finishTime,
                'number' => (string)$model->rmb,
                'status' => (int)$model->status,   //0未支付，2已支付
            ];
    }


}