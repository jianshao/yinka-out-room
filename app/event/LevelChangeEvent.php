<?php

namespace app\event;



//等级变化
class LevelChangeEvent extends AppEvent
{
    public $userId = 0;
    public $oldLevel = 0;
    public $newLevel = 0;
    public function __construct($userId = 0, $oldLevel = 0, $newLevel = 0, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->oldLevel = $oldLevel;
        $this->newLevel = $newLevel;
    }



    public function jsonToModel($data){
        $this->userId=$data['user_id']??0;
        $this->timestamp=$data['timestamp']??0;
    }



    public function modelToJson(){
        return [
            'user_id'=>$this->userId,
            'timestamp'=>$this->timestamp,
        ];
    }
}