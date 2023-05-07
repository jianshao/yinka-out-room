<?php


namespace app\domain\task\inspector;

use app\domain\asset\AssetKindIds;
use app\event\SendGiftEvent;
use think\facade\Log;


class TaskInspectorSendBean extends TaskInspector
{
    public static $TYPE_ID = 'user.send.bean';

    public function processEventImpl($task, $event){
        if ($event instanceof SendGiftEvent) {
            $dukeValue = $this->calcDukeValueByGiftDetails($event->sendDetails);
            Log::info(sprintf('TaskInspectorSendBean::processEventImpl userId=%d dukeValue=%d',
                $event->fromUserId, $dukeValue));
            if ($dukeValue > 0) {
                return $task->setProgress(1, $event->timestamp);
            }
        }
        return array(false, 0);
    }

    public function calcDukeValueByGiftDetails($sendDetails) {
        $dukeValue = 0;
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->consumeAsset
                    && $giftDetail->consumeAsset->assetId == AssetKindIds::$BEAN) {
                    $dukeValue += $giftDetail->consumeAsset->count;
                }
            }
        }
        return $dukeValue;
    }

}