<?php


namespace app\domain\pay\channel;

/**
 * 支付常量
 * Class PayConstant
 * @package app\domain\pay\channel
 */
class PayConstant
{
    const APP_ALIPAY_CHANNEL = 1;  // app内支付宝支付（原生）
    const WEB_ALIPAY_CHANNEL = 2;  // 支付宝网页支付（原生）
    const APP_WECHAT_CHANNEL = 3;  // app内微信支付 （原生）
    const WECHAT_GZH_CHANNEL = 4;  // 原生公众号
    const WEB_WECHAT_CHANNEL = 13;  // 微信web支付
    const WECHAT_CODE_CHANNEL = 15;  // 微信扫码支付
    const H5_ALIPAY_CHANNEL = 16;  // 支付宝手机网页支付

    const CHINAUMS_APP_ALI_CHANNEL = 31;   // 支付宝app支付银联商务
    const CHINAUMS_WECHAT_APPLET_CHANNEL = 32; // 微信小程序支付银联商务
    const CHINAUMS_WECHAT_GZH_CHANNEL = 33; // 微信公众号支付银联商务
    const CHINAUMS_H5_ALI_CHANNEL = 34; // ali-h5 支付银联商务
    const CHINAUMS_CTOB_CHANNEL = 35; // c扫b 聚合银联商务

    const SHENG_WECHAT_APPLET_CHANNEL = 42; // 微信小程序支付-盛付通
    const SHENG_WECHAT_GZH_CHANNEL = 43; // 微信公众号-盛付通
    const SHENG_WECHAT_SCAN_CHANNEL = 45; // 微信扫码 -盛付通

    const DIN_WECHAT_H5_CHANNEL = 51; // 智付-微信H5
    const DIN_ALI_H5_CHANNEL = 52; // 智付-支付宝H5

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