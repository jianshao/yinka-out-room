<?php


namespace app\event;


class DukeLevelChangeEvent extends AppEvent
{
    public $userId = 0;
    public $oldDukeLevel = 0;
    public $newDukeLevel = 0;
    public $roomId = 0;

    public function __construct($userId = 0, $oldDukeLevel = 0, $newDukeLevel = 0, $roomId = 0, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->oldDukeLevel = $oldDukeLevel;
        $this->newDukeLevel = $newDukeLevel;
        $this->roomId = $roomId;
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