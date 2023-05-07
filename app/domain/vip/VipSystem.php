<?php


namespace app\domain\vip;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class VipSystem
{
    protected static $instance;

    // vip级别 list<VipLevel>
    private $vipLevels = null;
    // vip级别map<level, VipLevel>
    private $vipLevelMap = null;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VipSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('GiftSystemLoaded count=%d',
                count(self::$instance->vipLevels)));
        }
        return self::$instance;
    }

    public function getVipLevels()
    {
        return $this->vipLevels;
    }

    public function findVipLevel($level)
    {
        return ArrayUtil::safeGet($this->vipLevelMap, $level);
    }

    private function loadFromJson()
    {
//        if(config("config.appDev")=="dev"){
        $vipConf = Config::getInstance()->getVipConf();
//        }else{
//            $vipConf = Config::getInstance()->getVipConf();
//        }
        $vipLevels = [];
        $vipLevelMap = [];
        $levelJsonObjs = ArrayUtil::safeGet($vipConf, 'levels', []);
        foreach ($levelJsonObjs as $levelJsonObj) {
            $vipLevel = new VipLevel();
            $vipLevel->decodeFromJson($levelJsonObj);
            if (ArrayUtil::safeGet($vipLevelMap, $vipLevel->level) != null) {
                Log::warning(sprintf('VipSystemLoadError level=%d err=%s',
                    $vipLevel->level, 'DuplicatePanelName'));
            } else {
                $vipLevelMap[$vipLevel->level] = $vipLevel;
            }
        }
        ksort($vipLevelMap);
        foreach ($vipLevelMap as $_ => $vipLevel) {
            // vip高级的继承低级的特权
            $vipLevels[] = $vipLevel;
        }

        $this->vipLevels = $vipLevels;
        $this->vipLevelMap = $vipLevelMap;
    }
}