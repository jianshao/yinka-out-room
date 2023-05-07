<?php

namespace app\domain\vip\constant;

/**
 * @desc vip常量
 * Class VipConstant
 * @package constant
 */
class VipConstant
{
    /**
     * 开通过vip redis set
     */
    const USER_VIP_PAY = 'user_vip_pay';

    /**
     * 用户vip过期时间 zset
     */
    const USER_VIP_EXP_TIME = 'user_vip_exp_time';

    /**
     * 用户svip过期时间 zset
     */
    const USER_SVIP_EXP_TIME = 'user_svip_exp_time';

    /**
     * 自动续费周期单位 月  MONTH  DAY
     */
    const PERIOD_TYPE = 'MONTH';

    /**
     * 自动续费周期
     */
    const PERIOD = 1;

    /**
     * 提前多少天扣款
     */
    const ADVANCE_DAY_EXECUTE = 1;

    /**
     * 已签约
     */
    const AGREEMENT_STATUS_TRUE = 1;

    /**
     * 未签约或已解除签约
     */
    const AGREEMENT_STATUS_FALSE = 2;

    /**
     * 签约过期
     */
    const AGREEMENT_STATUS_EXPIRED = 3;

}