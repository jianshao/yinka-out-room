<?php


namespace app\domain\sensors;

class SensorsEvent
{
    // 发送验证码
    const SEND_CODE_EVENT            = 'get_code_result';

    // 登录注册结果
    const LOGIN_EVENT                = 'login_result';

    // 提交个人信息
    const SET_PROFILE_EVENT          = 'submit_personalInformation';

    // 认证结果触发
    const ID_CARD_AUTH_EVENT         = 'real_name_authentication';

    // 发送私聊聊天消息并返回发送结果时触发
    const SEND_MSG_EVENT             = 'send_msg';

    // 充值有结果触发
    const PAY_SUCCESS_EVENT          = 'topup_arrival';

    // 金币增加
    const ADD_COIN_EVENT             = 'get_gold_coins';

    // 金币减少
    const CONSUME_COIN_EVENT         = 'consumption_gold';

    // 钻石增加
    const ADD_DIAMOND_EVENT          = 'income_record';

    // 钻石减少
    const CONSUME_DIAMOND_EVENT      = 'consumption_diamond';

    // 音豆减少
    const CONSUME_BEAN_EVENT         = 'consumption_record';

}