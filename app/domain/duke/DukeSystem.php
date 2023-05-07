<?php

namespace app\domain\duke;

use app\domain\Config;
use app\domain\duke\model\DukeModel;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class DukeSystem
{
    protected static $instance;
    // map<level, DukeLevel>
    private $dukeLevelMap = [];
    private $dukeLevelList = [];
    // 每30天一个周期
    private $dukeCycle = 30;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DukeSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('DukeSystemLoaded levels %s', json_encode(array_keys(self::$instance->dukeLevelMap))));
        }
        return self::$instance;
    }

    /**
     * @param $level
     * @return DukeLevel|null
     */
    public function findDukeLevel($level) {
        return ArrayUtil::safeGet($this->dukeLevelMap, $level);
    }

    public function getDukeLevelList() {
        return $this->dukeLevelList;
    }

    public function getNextLevel($level) {
        return $this->findDukeLevel($level + 1);
    }

    public function getPrevLevel($level) {
        return $this->findDukeLevel($level - 1);
    }

    public function addCycle($dukeExpiresTime, $timestamp, $nCycle) {
        $nCycleTime = $nCycle * $this->dukeCycle * 86400;
        return $dukeExpiresTime <= 0 ? $timestamp + $nCycleTime : $dukeExpiresTime + $nCycleTime;
    }

    public function adjustUpgrade($dukeModel, $timestamp) {
        $ret = false;
        $nextDukeLevel = $this->findDukeLevel($dukeModel->dukeLevel + 1);
        // 满足下一级别的值就升级
        while ($nextDukeLevel && $dukeModel->dukeValue >= $nextDukeLevel->value) {
            $ret = true;
            // 升级，并增加周期
            $dukeModel->dukeLevel += 1;
            $dukeModel->dukeValue -= $nextDukeLevel->value;
            $dukeModel->dukeExpiresTime = $this->addCycle($timestamp, $timestamp, 1);
            // 找到下一级别
            $nextDukeLevel = $this->findDukeLevel($dukeModel->dukeLevel + 1);
        }
        return $ret;
    }

    public function adjustDegrade($dukeModel, $timestamp) {
        $ret = false;
        $curDukeLevel = $this->findDukeLevel($dukeModel->dukeLevel);
        // 有级别，并且过期才会变
        while ($curDukeLevel && $dukeModel->dukeExpiresTime <= $timestamp) {
            // 过期了，计算降级以及剩余经验值
            // 1. 计算到期时间到当前时间的天数
            $days = max(1, TimeUtil::calcDiffDays($dukeModel->dukeExpiresTime, $timestamp));
            // 计算到当前时间需要几个保级
            $needKeepN = intval(($days + ($this->dukeCycle - 1)) / $this->dukeCycle);
            // 计算当前经验值够保几次
            $canKeepN = intval($dukeModel->dukeValue / $curDukeLevel->relegation);
            // 能保N次
            $keepN = min($canKeepN, $needKeepN);

            if ($keepN > 0) {
                // 增加N个周期
                $dukeModel->dukeExpiresTime = $this->addCycle($dukeModel->dukeExpiresTime, $timestamp, $keepN);
                // 减去N个保级经验值
                $dukeModel->dukeValue -= $keepN * $curDukeLevel->relegation;
                $ret = true;
            }

            if ($dukeModel->dukeExpiresTime <= $timestamp) {
                // 降级
                $dukeModel->dukeLevel -= 1;
                // 降级后加一个周期
                $dukeModel->dukeExpiresTime = $this->addCycle($dukeModel->dukeExpiresTime, $timestamp, 1);
                // 找到下一个经验值
                $curDukeLevel = $this->findDukeLevel($dukeModel->dukeLevel);
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * 根据给定的dule数据和当前时间计算当前的爵位信息
     *
     * @param $dukeModel
     * @param $timestamp
     */
    public function adjustDuke($dukeModel, $timestamp) {
        $ret1 = $this->adjustDegrade($dukeModel, $timestamp);
        $ret2 = $this->adjustUpgrade($dukeModel, $timestamp);
        return $ret1 || $ret2;
    }

    public function calcDukeLevel($dukeLevel, $dukeValue, $dukeExpiresTime, $timestamp) {
        $dukeModel = new DukeModel();
        $dukeModel->dukeLevel = $dukeLevel;
        $dukeModel->dukeExpiresTime = $dukeExpiresTime;
        $dukeModel->dukeValue = $dukeValue;
        $this->adjustDuke($dukeModel, $timestamp);
        return $dukeModel->dukeLevel;
    }

    private function loadFromJson() {
        $dukeConf = Config::getInstance()->getDukeConf();
        $dukeLevelsConf = ArrayUtil::safeGet($dukeConf, 'levels', []);
        $dukeLevelMap = [];
        $dukeLevelList = [];
        foreach ($dukeLevelsConf as $dukeLevelConf) {
            $dukeLevel = new DukeLevel();
            $dukeLevel->decodeFromJson($dukeLevelConf);
            if (ArrayUtil::safeGet($dukeLevelMap, $dukeLevel->level) != null) {
                Log::warning(sprintf('DukeSystemLoadError level=%s err=%s',
                    $dukeLevel->level, 'DuplicateLevel'));
            }
            $dukeLevelMap[$dukeLevel->level] = $dukeLevel;
        }

        ksort($dukeLevelMap);
        foreach ($dukeLevelMap as $_ => $dukeLevel) {
            $dukeLevelList[] = $dukeLevel;
        }

        $this->dukeLevelList = $dukeLevelList;
        $this->dukeLevelMap = $dukeLevelMap;
    }
}