<?php

namespace constant;

/**
 * @desc 首充常量
 * Class FirstChargeConstant
 * @package constant
 */
class FirstChargeConstant
{
    /**
     * 首充领取天数
     */
    const FIRST_CHARGE_RECEIVE_DAY = 3;

    /**
     * 5个自然日内可领取奖励
     */
    const RECEIVE_TOTAL_DAT = 5;

    /**
     * 充值状态
     * 未充值
     */
    const NOT_CHARGE_STATUS = 0;

    /**
     * 充值状态
     * 已充值
     */
    const ALREADY_CHARGE_STATUS = 1;

    /**
     * 充值状态
     * 首充充值
     */
    const FIRST_CHARGE_STATUS = 2;

    /**
     * 领取状态
     * 可领取
     */
    const CAN_RECEIVE_STATUS = 1;

    /**
     * 领取状态
     * 待领取
     */
    const WAIT_RECEIVE_STATUS = 2;

    /**
     * 领取状态
     * 已领取
     */
    const ALREADY_RECEIVE_STATUS = 3;

    /**
     * icon 倒计时 最后6小时显示
     */
    const DOWN_TIME_TOTAL_ICON = 3600 * 6;

    /**
     * 获取弹框场景
     */
    const POP_SCENE_OPEN = 1;

    /**
     * 关闭弹框场景
     */
    const POP_SCENE_CLOSE = 2;

    /**
     * 是否首充 redis field
     */
    const FIRST_CHARGE_REDIS_KEY = 'is_first_charge';

    /**
     * 首充成功时间 redis field
     */
    const FIRST_CHARGE_SUC_TIME = 'first_charge_suc_time';

    /**
     * 首充结束 redis field
     */
    const FIRST_CHARGE_FINISH = 'first_charge_finish';

    /**
     * 仅是充值音豆金额的用户 set
     */
    const USER_RECHARGED_BEEN = 'user_recharged_been';

    /**
     * 不是首充用户充值错误码
     */
    const FIRST_CHARGE_ERROR_CODE = 421;
}