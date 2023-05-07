<?php

namespace app\domain\prop;

use app\utils\ArrayUtil;
use app\utils\ClassRegister;

class PropActionRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropActionRegister();
        }
        return self::$instance;
    }

}


