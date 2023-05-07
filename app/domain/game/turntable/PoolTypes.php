<?php


namespace app\domain\game\turntable;


class PoolTypes
{
    public static $NEWER = 'newer';
    public static $DAILY = 'daily';
    public static $POOL_SORT_MAP = [
        'newer' => 1,
        'daily' => 2
    ];
    public static function isValid($poolType) {
        return in_array($poolType, [self::$NEWER, self::$DAILY]);
    }
}