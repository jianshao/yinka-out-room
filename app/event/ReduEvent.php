<?php


namespace app\event;

//热度值监听事件
class ReduEvent extends AppEvent
{
    public $msgId = 0;
    public $visitorNum = 0;
    public $roomId = 0;
    public $toUserId = 0;

    public function __construct($msgId = null, $visitorNum = null, $roomId = null, $toUserId = null, $timestamp = null)
    {
        parent::__construct($timestamp);
        $this->msgId = $msgId;
        $this->visitorNum = $visitorNum;
        $this->roomId = $roomId;
        $this->toUserId = $toUserId;
    }


    public function dataToModel($jsonObj)
    {
        $this->msgId = $jsonObj['msgId'];
        $this->visitorNum = $jsonObj['visitorNum'];
        $this->roomId = $jsonObj['roomId'];
        $this->toUserId = $jsonObj['toUserId'];
        $this->timestamp = $jsonObj['timestamp'];
        return $this;
    }

    public function modelToData(ReduEvent $model)
    {
        return [
            'msgId' => $model->msgId,
            'visitorNum' => $model->visitorNum,
            'roomId' => $model->roomId,
            'toUserId' => $model->toUserId,
            'timestamp' => $model->timestamp,
        ];
    }

}