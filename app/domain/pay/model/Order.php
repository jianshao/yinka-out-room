<?php


namespace app\domain\pay\model;


class Order
{
    //        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
//  `uid` int(11) unsigned NOT NULL DEFAULT '0',
//  `rmb` decimal(65,2) NOT NULL DEFAULT '0.00' COMMENT '充值人民币金额',
//  `coin` int(11) NOT NULL DEFAULT '0' COMMENT '虚拟币',
//  `content` varchar(255) DEFAULT NULL,
//  `status` varchar(255) DEFAULT NULL COMMENT '订单状态（0未支付，1已支付）',
//  `addtime` datetime NOT NULL,
//  `orderno` varchar(50) DEFAULT NULL,
//  `proxyuid` int(11) unsigned NOT NULL DEFAULT '0',
//  `dealid` varchar(255) DEFAULT NULL,
//  `platform` int(2) unsigned DEFAULT '0' COMMENT '支付平台：0（支付宝），1（微信），2（苹果支付）',
//  `title` varchar(60) NOT NULL COMMENT '订单标题',
//  `type` int(11) NOT NULL DEFAULT '1' COMMENT '购买类型 1充值 2vip',
//  `is_active` int(11) NOT NULL DEFAULT '0' COMMENT '状态 1续费vip 2激活vip 0充值',
//  `channel` varchar(50) DEFAULT '1' COMMENT '渠道',
//  `outparam` text NOT NULL,
    // 订单ID
    public $orderId = '';
    // 用户ID
    public $userId = 0;
    // 人民币
    public $rmb = 0.0;
    public $bean = 0;
    public $content = '';
    // 0未支付，1已支付
    public $status = 0;
    public $createTime = 0; #订单创建
    public $paidTime=0;   #订单支付完成
    public $finishTime=0;   #订单完成
    public $proxyUserId = 0;
    public $dealId = '';
    // 充值渠道ID 0（支付宝），1（微信），2（苹果支付）
    public $payChannel = 0;
    // 订单标题
    public $title = '';
    // 1充值 2vip
    public $type = 0;
    // 状态 1续费vip 2激活vip 0充值  4:自动扣款的订单
    public $isActive = 0;
    // 渠道
    public $channel = '';
    // 商品ID
    public $productId = 0;
    public $outParam = '';
}