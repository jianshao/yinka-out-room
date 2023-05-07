<?php

namespace app\domain\prop;

use think\facade\Log;

/**
 * 装扮类道具
 */
class PropKindAvatar extends PropKindAttire
{
    public static $TYPE_NAME = 'avatar';

    public function __constructor() {
    }

    public function getTypeName() {
        $ret = self::$TYPE_NAME;
        return $ret;
    }
}


