<?php

namespace app\domain\prop;

use app\utils\ArrayUtil;

/**
 * 气泡框道具
 */
class PropKindBubble extends PropKindAttire
{
    public static $TYPE_NAME = 'bubble';

    public static function newInstance() {
        return new PropKindBubble();
    }

    public function getTypeName() {
        return self::$TYPE_NAME;
    }
}


