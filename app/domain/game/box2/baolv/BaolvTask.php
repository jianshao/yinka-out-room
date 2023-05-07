<?php

namespace app\domain\game\box2\baolv;

class BaolvTask
{
    public $taskId = '';
    public $boxId = 0;
    public $userCount = 0;
    public $loopCount = 0;
    public $breakCountPerLoop = 0;
    public $state = 0;
    public $progress = 0;
    public $isUserBreakCount = [];

    public function __construct($taskId = '', $boxId = 0, $userCount = 0, $loopCount = 0,
                                $breakCountPerLoop = 0, $isUserBreakCount = 0)
    {
        $this->taskId = $taskId;
        $this->boxId = $boxId;
        $this->userCount = $userCount;
        $this->loopCount = $loopCount;
        $this->breakCountPerLoop = $breakCountPerLoop;
        $this->state = BaolvTaskState::$INIT;
        $this->progress = 0;
        $this->isUserBreakCount = $isUserBreakCount;
    }

    public function fromJson($jsonObj)
    {
        $this->taskId = $jsonObj['taskId'];
        $this->boxId = $jsonObj['boxId'];
        $this->userCount = $jsonObj['userCount'];
        $this->loopCount = $jsonObj['loopCount'];
        $this->breakCountPerLoop = $jsonObj['breakCountPerLoop'];
        $this->state = $jsonObj['state'];
        $this->progress = $jsonObj['progress'];
        $this->isUserBreakCount = $jsonObj['isUserBreakCount'];
        return $this;
    }

    public function toJson()
    {
        return [
            'taskId' => $this->taskId,
            'boxId' => $this->boxId,
            'userCount' => $this->userCount,
            'loopCount' => $this->loopCount,
            'breakCountPerLoop' => $this->breakCountPerLoop,
            'state' => $this->state,
            'progress' => $this->progress,
            'isUserBreakCount' => $this->isUserBreakCount,
        ];
    }
}