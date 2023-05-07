<?php

namespace app\domain\prop;

use app\utils\ClassRegister;

class PropUnitRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropUnitRegister();
        }
        return self::$instance;
    }
}


