<?php

namespace app\utils;


/**
 * 只用位运算不用算数运算实现整数的 + - * /
 */

class Arithmetic
{
    const MAX_INTEGER = 2147483647;
    const MIN_INTEGER = -2147483648;

    /**
     * @param int $a
     * @param int $b
     * @return int  $a + $b;
     */

    public static function add(int $a, int $b): int
    {
        $sum = $a;
        while ($b) {
            $sum = $a ^ $b;       // 不考虑进位
            $b = ($a & $b) << 1;  //  只考虑进位
            $a = $sum;
        }
        return $sum;
    }

    /**
     * 相反数 <= 二进制表达取反+1(补码)
     * @param int $n
     * @return int
     */
    public static function negateNumber(int $n): int
    {
        return self::add(~$n, 1);
    }

    /**
     * a-b = a + (-b)
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function minus(int $a, int $b): int
    {
        return self::add($a, self::negateNumber($b));
    }

    /**
     * @param int $a
     * @param int $b
     * @return int  $a * $b
     */
    public static function multiple(int $a, int $b): int
    {
        $res = 0;
        while ($b) {
            if (($b & 1)) {
                $res = self::add($res, $a);
            }
            $a <<= 1;
            $b >>= 1;
        }
        return $res;
    }

    private static function isNegative(int $n): bool
    {
        return $n < 0;
    }

    /**
     * a/b  a = MIN_INTEGER, b!=MIN_INTEGER ?
     * @param int $a
     * @param int $b
     * @return int
     */
    private static function p(int $a, int $b): int
    {
        $x = self::isNegative($a) ? self::negateNumber($a) : $a;
        $y = self::isNegative($b) ? self::negateNumber($b) : $b;
        $res = 0;
        for ($i = 31; $i > -1; $i = self::minus($i, 1)) {
            if (($x >> $i) >= $y) {
                $res |= (1 << $i);
                $x = self::minus($x, $y << $i);
            }
        }
        return self::isNegative($a) ^ self::isNegative($b) ? self::negateNumber($res) : $res;
    }

    /**
     * @param int $a
     * @param int $b
     * @return int $a / $b
     */
    public static function pide(int $a, int $b): int
    {
        if ($b === 0) {
            throw new RuntimeException("pisor is 0");
        }
        if ($a === self::MIN_INTEGER && $b === self::MIN_INTEGER) {
            return 1;
        } else if ($b === self::MIN_INTEGER) {
            return 0;
        } else if ($a === self::MIN_INTEGER) {
            $res = self::p(self::add($a, 1), $b);
            return self::add($res, self::p(self::minus($a, self::multiple($res, $b)), $b));
        } else {
            return self::p($a, $b);
        }
    }


    /**
     * 是否是正数
     * @param $num
     * @return bool
     */
    public static function is_positive($num)
    {
        if (floor($num) == $num) {
            return true;
        } else {
            return false;
        }
    }
}

