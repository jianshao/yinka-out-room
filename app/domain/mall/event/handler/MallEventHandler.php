<?php


namespace app\domain\mall\event\handler;


use app\domain\mall\dao\MallBuyRecordModel;
use app\domain\mall\dao\MallBuyRecordModelDao;
use app\domain\mall\MallIds;
use app\event\BuyGoodsEvent;
use Exception;
use think\facade\Log;

class MallEventHandler
{
    /**
     * @param $event BuyGoodsEvent
     */
    public function onBuyGoodsEvent($event){
        try{
            Log::info(sprintf('MallEventHandler::onBuyGoodsEvent userId=%d mallId=%s consumeAsset=%s:%d addAssetId=%s:%d from=%d',
                $event->userId, $event->mallId, $event->consumeAsset->assetId, $event->consumeAsset->count,
                $event->addAsset->assetId, $event->addAsset->count, $event->from));
//            if (!in_array($event->mallId, [MallIds::$COIN, MallIds::$ORE]))  {
//                return;
//            }
//            $model = new MallBuyRecordModel($event->userId, $event->addAsset->assetId, $event->addAsset->count,
//                $event->consumeAsset->assetId, $event->consumeAsset->count, $event->mallId, $event->from,
//                $event->timestamp);
//            MallBuyRecordModelDao::getInstance()->add($model);
        } catch (Exception $e) {
            Log::error(sprintf('MallEventHandler::onBuyGoodsEvent userId=%d mallId=%s consumeAsset=%s:%d addAssetId=%s:%d from=%d ex=%d:%s',
                $event->userId, $event->mallId, $event->consumeAsset->assetId, $event->consumeAsset->count,
                $event->addAsset->assetId, $event->addAsset->count, $event->from, $e->getCode(), $e->getMessage()));
        }
    }
}