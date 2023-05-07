<?php

namespace app\utils;

class TimeUtil {
    public static function calcDayStartTimestamp($timestamp) {
        $datestr = date('Y-m-d', $timestamp);
        return strtotime($datestr);
    }

    public static function calcWeekStartTimestamp($timestamp) {
        return strtotime('next Monday', $timestamp) - 7 * 24 * 3600;
    }

    public static function isSameDay($timestamp1, $timestamp2) {
        return self::timeToStr($timestamp1, '%Y-%m-%d') == self::timeToStr($timestamp2, '%Y-%m-%d');
    }

    /**
     * 判断两日期是不是同一周
     * 星期是按按周一到周日
     */
    public static function isSameWeek($timestamp1, $timestamp2){
        return self::calcWeekStartTimestamp($timestamp1) == self::calcWeekStartTimestamp($timestamp2);
    }

    public static function strToTime($str) {
        $ret = strtotime($str);
        if ($ret === false) {
            return -1;
        }
        return $ret;
    }

    public static function timeToStr($timestamp, $fmt='') {
        if ($fmt === '') {
            $fmt = '%Y-%m-%d %H:%M:%S';
        }
        return strftime($fmt, $timestamp);
    }

    public static function calcDiffDays($ts1, $ts2) {
        $diffSeconds = TimeUtil::calcDayStartTimestamp($ts2) - TimeUtil::calcDayStartTimestamp($ts1);
        return intval(ceil($diffSeconds / 86400.0));
    }

    //将生日转化为年龄
    public static function birthdayToAge($birthday){
        $birthday = date('Y-m-d', strtotime($birthday));
        if($birthday>date('Y-m-d',time())){
            $birthday = '1999-01-01';
        }
        list($year,$month,$day) = explode("-",$birthday);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff  = date("d") - $day;

        if($year_diff <= 18){
            return 18;
        }

        if ($day_diff < 0 || $month_diff < 0){
            $year_diff--;
        }

        return $year_diff;
    }

    public static function getMillisecond() {
        list($usec, $sec) = explode(' ', microtime());
        return round($usec * 1000);
    }

    public static function isTimestamp($timestamp) {
        if(strtotime(date('Y-m-d H:i:s',$timestamp)) === $timestamp) {
            return $timestamp;
        } else return false;
    }

    public static function calcDays($timestamp1, $timestamp2) {
        if ($timestamp1 > $timestamp2) {
            return round(($timestamp1 - $timestamp2) / (24 * 3600));
        }
        return 0;
    }
}