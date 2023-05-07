<?php


namespace app\domain\game\taojin\model;


use app\domain\game\taojin\dao\OreModelDao;
use app\domain\game\taojin\OreTypes;
use app\domain\game\taojin\TaojinSystem;
use app\utils\ArrayUtil;

class OreModel
{
    public $oreMap = [];
    public $updateTime = 0;

    public function getOre($oreType) {
        return ArrayUtil::safeGet($this->oreMap, $oreType, 0);
    }

    public function setOre($oreType, $count) {
        $this->oreMap[$oreType] = $count;
    }
    public function adjust($userId, $timestamp) {
        $ore = OreModelDao::getInstance()->loadOre($userId);
        if ($ore==null || !TaojinSystem::getInstance()->getGameStatus($timestamp)|| !TaojinSystem::getInstance()->getGameStatus($ore->updateTime)) {
            $materials['iron_ore'] = 0;
            $materials['silver_ore'] = 0;
            $materials['gold_ore'] = 0;
            $materials['fossil_ore'] = 0;
        } else {
            $materials['iron_ore'] = $ore->getOre(OreTypes::$IRON);
            $materials['silver_ore'] = $ore->getOre(OreTypes::$SILVER);
            $materials['gold_ore'] = $ore->getOre(OreTypes::$GOLD) ;
            $materials['fossil_ore'] = $ore->getOre(OreTypes::$FOSSIL);
        }
        return $materials;
    }
}