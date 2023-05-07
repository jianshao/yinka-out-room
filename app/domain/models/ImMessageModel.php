<?php
/**
 * User: li
 * Date: 2019
 * 活动数据表
 */
namespace app\domain\models;

class ImMessageModel{

    //发送者id
    public $fromUserId = 0;
    // 接收者id
    public $toUserId = 0;
    //文字消息内容
    public $text= '';
    //图片消息
    public $image= '';
    // 私聊创建时间
    public $createTime = 0;

    public function __construct($fromUserId, $toUserId, $text='', $image='', $createTime=0) {
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->text = $text;
        $this->image = $image;
        $this->createTime = $createTime;
    }

    public function encodeData(){
        return [
            'fromUid' => $this->fromUserId,
            'toUid' => $this->toUserId,
            'textContent' => $this->text,
            'image' => $this->image,
            'createTime' => $this->createTime
        ];
    }
}