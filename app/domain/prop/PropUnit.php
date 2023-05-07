<?php

namespace app\domain\prop;

/**
 * 道具单位类
 */
abstract class PropUnit
{
    public $displayName = '';

    /**
     * 给prop增加count个单位
     * @param prop: 道具
     * @param count: 数量
     * @param timestamp: 增加时的时间戳
     */
    abstract public function add($prop, $count, $timestamp);

    /**
     * 给prop消耗count个单位
     * @param prop: 道具
     * @param count: 数量
     * @param timestamp: 增加时的时间戳
     */
    abstract public function consume($prop, $count, $timestamp);

    /**
     * 当前剩余多少个单位
     */
    abstract public function balance($prop, $timestamp);

    /**
     * 是否是时间类型的
     */
    abstract public function isTiming();

    public function buildDisplay($prop, $timestamp) {
        $balance = $this->balance($prop, $timestamp);
        return $balance . $this->displayName;
    }

    abstract public function balanceByPropModel($propModel, $timestamp);

    public function processWear($prop, $timestamp) {}
    public function processUnwear($prop, $timestamp) {}

    public function translateOld($prop, $timestamp) {}
    /**
     * @param $jsonObj
     */
    public function decodeFromJson($jsonObj) {
        $this->displayName = $jsonObj['displayName'];
        $this->decodeFromJsonImpl($jsonObj);
    }

    protected function decodeFromJsonImpl($jsonObj) {

    }

    function breakUpBalance($prop, $timestamp) {
        return $this->balance($prop, $timestamp);
    }

    function transTimeStr($timestamp){
        $str = '';
        $day = intval($timestamp/86400);
        if ($day > 0){
            $str = $day.'天';
        }
        $hour = intval(($timestamp - $day*86400)/3600);

        $minutes = intval(($timestamp - $day*86400 - $hour*3600)/60);
        if ($minutes > 0){
            $str = $str.$hour.'时'.$minutes.'分';
        }elseif ($hour > 0){
            $str = $str.$hour.'时';
        }

        return $str;
    }
}


