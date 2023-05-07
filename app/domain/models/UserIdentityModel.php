<?php
/**
 * User: yond
 * Date: 2020
 * 认证表
 */
namespace app\domain\models;

class UserIdentityModel {

    public $userId = 0;
    // 身份证姓名
    public $certName = '';
    //身份证号码
    public $certno= '';
    //订单号
    public $outerOrderNo = '';
    // 身份核验流水
    public $certifyid = '';
    // 状态0：失败 1：成功 2：待确认
    public $status= '';
    // 创建时间
    public $createTime = 0;

    public function __construct($userId, $certName='', $certno='', $outerOrderNo='', $certifyid='', $status='',$createTime=0) {
        $this->userId = $userId;
        $this->certName = $certName;
        $this->certno = $certno;
        $this->outerOrderNo = $outerOrderNo;
        $this->certifyid = $certifyid;
        $this->status = $status;
        $this->createTime = $createTime;
    }

}