<?php


namespace app\domain\redpacket;


use app\domain\Config;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

class RedPacketSystem
{
    protected static $instance;

    // map<seconds, display>
    private $secondToDisplay = [];
    // map<display, seconds>
    private $displayToSeconds = [];
    // map<name, list<RedPacket> >
    private $areaMap = [];
    private $times = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('RedPacketSystemLoaded areas=%s',
                json_encode(array_keys(self::$instance->areaMap))));
        }
        return self::$instance;
    }

    public function getTimes() {
        return $this->times;
    }

    public function getSecondsByDisplay($display) {
        return ArrayUtil::safeGet($this->displayToSeconds, $display);
    }

    public function getArea($areaName) {
        return ArrayUtil::safeGet($this->areaMap, $areaName);
    }

    public function findRedPacketByBean($bean) {
        foreach ($this->areaMap as $_ => $redPacketList) {
            foreach ($redPacketList as $redPacket) {
                if ($redPacket->value == $bean) {
                    return $redPacket;
                }
            }
        }
        return null;
    }

    public function findProductIdByBean($areaName, $bean) {
        $redPackets = $this->getArea($areaName);
        if ($redPackets != null) {
            foreach ($redPackets as $redPacket) {
                if ($redPacket->value == $bean) {
                    return $redPacket->productId;
                }
            }
        }
        return null;
    }

    private function loadFromJson() {
        $conf = Config::getInstance()->getRedPacketConf();
        $timesConf = ArrayUtil::safeGet($conf, 'timesConf', []);
        if (count($timesConf) <= 0) {
            Log::error(sprintf('RedPacketSystem::loadFromJson NotConfigTimes'));
            throw new FQException('红包配置错误', 500);
        }

        $secondToDisplay = [];
        $displayToSeconds = [];
        foreach ($timesConf as $timeConf) {
            $t = [$timeConf['seconds'], $timeConf['display']];
            $secondToDisplay[$t[0]] = $t[1];
            $displayToSeconds[$t[1]] = $t[0];
        }

        $areasConf = ArrayUtil::safeGet($conf, 'areas', []);
        $areasMap = [];
        foreach ($areasConf as $name => $redPacketConfList) {
            $redPackets = [];
            foreach ($redPacketConfList as $redPacketConf) {
                $rp = new RedPacket();
                $rp->decodeFromJson($redPacketConf);
                $redPackets[] = $rp;
            }
            $areasMap[$name] = $redPackets;
        }

        $times = ArrayUtil::safeGet($conf, 'times', []);

        $this->secondToDisplay = $secondToDisplay;
        $this->displayToSeconds = $displayToSeconds;
        $this->areaMap = $areasMap;
        $this->times = $times;
    }
}