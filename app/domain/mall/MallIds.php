<?php

namespace app\domain\mall;

class MallIds
{
    public static $BEAN = 'bean';
    public static $COIN = 'coin';
    public static $ORE = 'ore';
    public static $GAME = 'game';
    public static $GASHAPON = 'gashapon';
    public static $MALL = 'mall'; # 第二版商城

    public static function isValid($mallId) {
        return in_array($mallId, [self::$BEAN, self::$COIN, self::$ORE]);
    }
}