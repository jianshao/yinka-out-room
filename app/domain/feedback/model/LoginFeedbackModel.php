<?php


namespace app\domain\feedback\model;


class LoginFeedbackModel
{
//`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
//`account` int(11) NOT NULL COMMENT '用户账号',
//`status` int(11) NOT NULL DEFAULT '1' COMMENT '状态：1展示 0不',
//`phone` varchar(13) NOT NULL DEFAULT '' COMMENT '手机号',
//`problem` varchar(255) NOT NULL DEFAULT '' COMMENT '问题',
//`mode` varchar(30) NOT NULL DEFAULT '' COMMENT '登陆方式',
//`addtime` datetime DEFAULT NULL COMMENT '创建时间',
//
    // 用户账号
    public $account = 0;
    // 状态：1展示 0不
    public $status = 0;
    // 手机号
    public $phone = '';
    // 问题
    public $problem = '';
    // 登陆方式
    public $mode = '';
    // 创建时间
    public $createTime = 0;
}