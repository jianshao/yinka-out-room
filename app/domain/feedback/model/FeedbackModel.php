<?php


namespace app\domain\feedback\model;


class FeedbackModel
{
    public $userId = 0;
    public $content = '';
    public $createTime = 0;

    public function __construct($userId=0, $content='', $createTime=0) {
        $this->userId = $userId;
        $this->content = $content;
        $this->createTime = $createTime;
    }
}