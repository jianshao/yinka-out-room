<?php


namespace app\domain\thirdpay\chinaums;

/**
 * @desc 银联商务常量
 * Class PayConstant
 * @package app\domain\thirdpay\chinaums
 */
class PayConstant
{
    /**
     * @desc 以zb_paychannel表中的id为主
     * 支付宝app支付银联商务
     */
    const CHINAUMS_APP_ALI_CHANNEL = 31;

    /**
     * 微信小程序支付银联商务
     */
    const CHINAUMS_WECHAT_APPLET_CHANNEL = 32;

    /**
     * 微信公众号支付银联商务
     */
    const CHINAUMS_WECHAT_GZH_CHANNEL = 33;

    /**
     * ali-h5 支付银联商务
     */
    const CHINAUMS_H5_ALI_CHANNEL = 34;

    /**
     * c扫b 聚合银联商务
     */
    const CHINAUMS_CTOB_CHANNEL = 35;

    /**
     * @desc 银联商务支付渠道
     */
    const CHINAUMS_CHANNEL_PAYS = [
        self::CHINAUMS_APP_ALI_CHANNEL,
        self::CHINAUMS_WECHAT_APPLET_CHANNEL,
        self::CHINAUMS_WECHAT_GZH_CHANNEL,
        self::CHINAUMS_H5_ALI_CHANNEL,
        self::CHINAUMS_CTOB_CHANNEL,
    ];

    /**
     * @desc 需要code的银联商务支付渠道
     */
    const CHINAUMS_CHANNEL_CODE_PAYS = [
        self::CHINAUMS_WECHAT_APPLET_CHANNEL,
    ];
}