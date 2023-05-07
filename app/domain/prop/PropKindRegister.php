<?php

namespace app\domain\prop;
use think\facade\Log;
use app\utils\ClassRegister;

class PropKindRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropKindRegister();
        }
        return self::$instance;
    }
}


