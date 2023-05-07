<?php


namespace app\domain\pay\event\handler;


use app\common\RedisCommon;
use app\domain\pay\FirstChargeService;
use app\domain\pay\ProductSystem;
use app\domain\pay\ProductTypes;
use constant\FirstChargeConstant;
use think\facade\Log;

/**
 * @desc 首充
 * Class FirstChargeHandler
 * @package app\domain\pay\event\handler
 */
class FirstChargeHandler
{
    public function onChargeEvent($event)
    {
        try {
            Log::info(sprintf('FirstChargeHandler::onChargeEvent event=%s', json_encode($event)));
            $redis = RedisCommon::getInstance()->getRedis();
            $productType = ProductSystem::getInstance()->getProductType($event->productId);
            if ($productType == ProductTypes::$BEAN) {
                $redis->sAdd(FirstChargeConstant::USER_RECHARGED_BEEN, $event->userId);
            }
            // 首充信息写入redis
            FirstChargeService::getInstance()->cacheFirstChargeInfo($event->userId, $event->productId, $event->timestamp);
        } catch (\Exception $e) {
            Log::error(sprintf('FirstChargeHandler::onChargeEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}