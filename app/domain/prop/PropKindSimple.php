<?php


namespace app\domain\prop;


class PropKindSimple extends PropKind
{
    public static $TYPE_NAME = 'simple';

    public function newProp($propId) {
        return new PropSimple($this, $propId);
    }

    public function getTypeName() {
        return self::$TYPE_NAME;
    }
}