<?php


namespace app\event;


//后台头像/昵称/个性签名/背景墙 审核处理
class MemberDetailAuditEvent extends AppEvent
{
    public $userId = 0;                             //用户id
    public $memberDetailAuditModel = null;          //审核记录模型
    public $upResult = null;                        //dbupdate result
    public $userModel = null;                       //用户模型
    public $status = 0;                             //处理行为: 0未审核,1审核通过,2未通过

    public function __construct($userId = 0, $memberDetailAuditModel = null, $upResult = null, $userModel = null, $status = 0, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->memberDetailAuditModel = $memberDetailAuditModel;
        $this->upResult = $upResult;
        $this->userModel = $userModel;
        $this->status = $status;
    }

    public function jsonToModel($data)
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
    }


    public function modelToJson()
    {
        return [
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp,
        ];
    }

}