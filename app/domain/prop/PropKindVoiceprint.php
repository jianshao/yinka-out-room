<?php

namespace app\domain\prop;

/**
 * 麦位光圈
 */
class PropKindVoiceprint extends PropKindAttire
{
    public static $TYPE_NAME = 'voiceprint';

    public static function newInstance() {
        return new PropKindVoiceprint();
    }

    public function getTypeName() {
        return self::$TYPE_NAME;
    }
}


