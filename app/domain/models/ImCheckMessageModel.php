<?php
/**
 * User: li
 * Date: 2019
 * 活动数据表
 */
namespace app\domain\models;

class ImCheckMessageModel{

    //发送者id
    public $fromUserId = 0;
    // 接收者id
    public $toUserId = 0;
    //消息类型
    public $type = 0;
    //消息内容
    public $message = '';
    //检测结果
    public $checkResponse = '';
    //接口返回信息
    public $apiResponse = '';
    //消息状态
    public $status = 0;
    //创建时间
    public $createdTime = 0;
    //更新时间
    public $updatedTime = 0;

    public function __construct($fromUserId, $toUserId, $type, $message, $checkResponse, $apiResponse, $status, $createdTime, $updatedTime) {
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->type = $type;
        $this->message = $message;
        $this->checkResponse = $checkResponse;
        $this->apiResponse = $apiResponse;
        $this->status = $status;
        $this->createdTime = $createdTime;
        $this->updatedTime = $updatedTime;
    }

    public function encodeData(){
        return [
            'from_uid' => $this->fromUserId,
            'to_uid' => $this->toUserId,
            'type' => $this->type,
            'message' => $this->message,
            'check_response' => $this->checkResponse,
            'api_response' => $this->apiResponse,
            'status' => $this->status,
            'created_time' => $this->createdTime,
            'updated_time' => $this->updatedTime,
        ];
    }
}