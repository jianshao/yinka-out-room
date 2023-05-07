<?php

namespace app\domain\gift;

use app\utils\ArrayUtil;
use app\utils\ClassRegister;

class GiftActionRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GiftActionRegister();
        }
        return self::$instance;
    }

}


