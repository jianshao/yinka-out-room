<?php

namespace app\domain\prop;

/**
 * 座驾
 */
class PropKindMount extends PropKindAttire
{
    public static $TYPE_NAME = 'mount';

    public static function newInstance() {
        return new PropKindMount();
    }

    public function getTypeName() {
        return self::$TYPE_NAME;
    }
}


