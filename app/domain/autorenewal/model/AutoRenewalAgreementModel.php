<?php

namespace app\domain\autorenewal\model;

/**
 * @desc 支付宝签约
 * Class AutoRenewalAgreementModel
 * @package app\domain\autorenewal\model
 */
class AutoRenewalAgreementModel
{
    public $userId = 0;
    public $agreementNo = '';   // 用户签约号
    public $externalAgreementNo = '';  // 商户签约号,唯一
    public $transactionIds = ''; // 每一期的订单id，用,分割
    public $status = 0;   // 订阅状态 1 签约中; 2 关闭签约  3 签约过期
    public $signTime = '';  // 签约时间
    public $executeTime = '';  // 下次扣款时间
    public $signType = 2;  // 签约类型  2 vip   3 svip
    public $firstProductId = 0;  // 开始订阅的商品
    public $productId = 0;  // 后续扣款的商品
    public $renewStatus = 0;  // 续费状态.   1: 等待扣费;  2: 扣费失败
    public $outparam = '';  // 支付宝返回签约信息
    public $contractSource = '';  // 签约来源  (alipay  apple)
    public $configSource = '';  // 支付宝读取的配置文件来源
}