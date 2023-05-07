<?php

namespace app\domain\prop;


/**
 * IM气泡框道具
 */
class PropKindImBubble extends PropKindAttire
{
    public static $TYPE_NAME = 'im_bubble';

    public static function newInstance() {
        return new PropKindImBubble();
    }

    public function getTypeName() {
        return self::$TYPE_NAME;
    }
}


