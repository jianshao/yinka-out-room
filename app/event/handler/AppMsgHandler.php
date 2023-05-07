<?php


namespace app\event\handler;


use app\common\server\QueuePush;
use app\event\ReduEvent;
use think\facade\Log;


class AppMsgHandler
{
    // 热度值变化房间消息通知
    public function onReduEvent(ReduEvent $event)
    {
        $queue = new QueuePush;
        $redumodel=new ReduEvent;
        $data=$redumodel->modelToData($event);
        $jsonData = $queue->SetMessageData('AppMsgHandler','onReduEvent', $data);
        $result = $queue->lPush($jsonData);
        Log::info(sprintf('AppMsgHandler::onReduEvent paramData=%s result:%d',
            $jsonData, $result));
    }
}