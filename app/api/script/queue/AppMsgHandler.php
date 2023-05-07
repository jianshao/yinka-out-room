<?php

namespace app\api\script\queue;


use app\domain\exceptions\FQException;
use app\event\ReduEvent;
use think\Exception;

/**
 * @info  sub消息订阅者
 * Class UserBucket
 */
class AppMsgHandler
{
    public function onReduEvent($data)
    {
        $model = new ReduEvent();
        $event = $model->dataToModel($data);
        $str = ['msgId' => $event->msgId, 'VisitorNum' => $event->visitorNum];
        $msg['msg'] = json_encode($str);
        $msg['roomId'] = $event->roomId;
        $msg['toUserId'] = $event->toUserId;
        $socket_url = config('config.socket_url');
        $msgData = json_encode($msg);
        $result = curlData($socket_url, $msgData, 'POST', 'json');
        return $result;
    }


}
