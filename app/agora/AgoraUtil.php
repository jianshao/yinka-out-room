<?php


namespace app\agora;


class AgoraUtil
{
    public static function packString($value)
    {
        return pack("v", strlen($value)) . $value;
    }
}