<?php


namespace app\api\controller\inner;


use app\Base2Controller;
use app\domain\hyperf\activity\ActivityService;
use app\event\BreakBoxNewEvent;

class HyperfController extends Base2Controller
{
    //rpc 扣钱
    public function collectFee() {
        $userId = $this->request->param('userId', 0);
        $roomId = $this->request->param('roomId', 0);
        $box = $this->request->param('box');
        $count = $this->request->param('count', 0);
        $totalPrice = $this->request->param('totalPrice', 0);
        $autoBuy = $this->request->param('autoBuy');
        $timestamp = $this->request->param('timestamp');
        $activityType = $this->request->param('activityType', 'box2');
        $balance = ActivityService::getInstance()->collectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp, $activityType);
        return rjson(['balance' => $balance]);
    }

    //rpc 发礼物
    public function deliveryGifts() {
        $userId = $this->request->param('userId', 0);
        $roomId = $this->request->param('roomId', 0);
        $box = $this->request->param('box');
        $count = $this->request->param('count', 0);
        $timestamp = $this->request->param('timestamp');
        $giftMap = $this->request->param('giftMap');
        $specialGiftId = $this->request->param('specialGiftId');
        $activityType = $this->request->param('activityType', 'box2');
        ActivityService::getInstance()->deliveryGifts($userId, $roomId, $box, $count, $giftMap, $specialGiftId, $timestamp, $activityType);
        return rjson();
    }


    //rpc 新人任务
    public function newerTask() {
        $event = $this->request->param('event');
        $event = json_decode($event, true);
        ActivityService::getInstance()->newerTask(new BreakBoxNewEvent($event['userId'], $event['roomId'],
            $event['boxId'], $event['count'], $event['consumeAssetList'], $event['deliveryGiftMap'], $event['deliverySpecialGiftId'],
            $event['timestamp']
        ));
        return rjson();
    }

    //rpc 获取用户气泡框
    public function getUserBubble() {
        $userId = $this->request->param('userId');
        $bubble = ActivityService::getInstance()->getUserBubble($userId);
        return rjson(['bubble' => $bubble]);
    }


}