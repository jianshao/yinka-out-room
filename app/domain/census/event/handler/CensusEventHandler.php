<?php


namespace app\domain\census\event\handler;


use app\domain\asset\AssetKindIds;
use app\domain\census\dao\CensusWholewheatModelDao;
use app\domain\census\model\RoomWholeWheatModel;
use app\service\RoomNotifyService;
use Exception;
use think\facade\Log;

class CensusEventHandler
{
    // 用户送礼增加等级经验值 统计每个厅每天收到多少全麦礼物
    public function onSendGiftEvent($event) {
        try{
            if (count($event->receiveUsers) >= 7 && $event->roomId > 0) {
                $model = new RoomWholeWheatModel();
                $model->sendUid = $event->fromUserId;
                $model->roomId = $event->roomId;
                $model->giftId = $event->giftKind->kindId;
                $model->count = $event->count;
                $model->giftValue = $event->giftKind->getPriceByAssetId(AssetKindIds::$BEAN);
                $model->createTime = $event->timestamp;
                $model->ext = json_encode($event->receiveUsers);
                CensusWholewheatModelDao::getInstance()->saveRecord($model);
            }
        } catch (Exception $e) {
            Log::error(sprintf('CensusEventHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

//        房间全麦热度值累加
        try {
            if (count($event->receiveUsers) >= 7 && $event->roomId > 0) {
                RoomNotifyService::getInstance()->incrPopularValue($event->roomId,$event->giftKind->kindId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('CensusEventHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }


    }
}