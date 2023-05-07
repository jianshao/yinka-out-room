<?php


namespace app\utils;


class CompareUtil
{
    public static function compare($v1, $v2, $reversed=false) {
        if ($v1 < $v2) {
            return $reversed ? 1 : -1;
        } elseif ($v1 > $v2) {
            return $reversed ? -1 : 1;
        }
        return 0;
    }

    public static function compareArray($array1, $array2, $reversed=false) {
        $len1 = count($array1);
        $len2 = count($array2);
        $len = min($len1, $len2);
        for ($i = 0; $i < $len; $i++) {
            $v1 = $array1[$i];
            $v2 = $array2[$i];
            $ret = self::compare($v1, $v2, $reversed);
            if ($ret != 0) {
                return $ret;
            }
        }
        if ($len1 < $len2) {
            return $reversed ? 1 : -1;
        } elseif ($len1 > $len2) {
            return $reversed ? -1 : 1;
        }
        return 0;
    }
}