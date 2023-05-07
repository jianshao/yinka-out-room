<?php

namespace app\domain\promote;

use app\event\ChargeEvent;
use app\event\UserLoginEvent;
use app\event\UserRegisterEvent;

/**
 * 推广基类
 */
abstract class PromoteService
{
    /**
     * 激活 注册
     */
    abstract public function onUserRegisterEvent(UserRegisterEvent $event);

    /**
     * 用户登录 次留
     */
    abstract public function onUserLoginEvent(UserLoginEvent $event);

    /**
     * 付费
     */
    abstract public function onChargeEvent(ChargeEvent $event);
}


