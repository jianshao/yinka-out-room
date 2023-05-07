<?php


namespace app\domain\prop\model;


class PropBagModel
{
    public $nextId = 0;
    public $createTime = 0;
    public $updateTime = 0;

    public function __construct($nextId=0, $createTime=0, $updateTime=0) {
        $this->nextId = $nextId;
        $this->createTime = $createTime;
        $this->updateTime = $updateTime;
    }
}