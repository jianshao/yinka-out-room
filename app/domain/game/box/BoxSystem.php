<?php


namespace app\domain\game\box;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class BoxSystem
{
    // list<count>
    public $counts = null;
    // map<boxId, Box>
    public $boxMap = null;
    public $eggCoin = null;
    public $fullServerCoin = null;

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BoxSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('BoxSystem Loaded counts=%s boxes=%s',
                json_encode(self::$instance->counts),
                json_encode(array_keys(self::$instance->boxMap))));
        }
        return self::$instance;
    }

    public function findBox($boxId) {
        return ArrayUtil::safeGet($this->boxMap, $boxId);
    }

    private function loadFromJson() {
//        $confJsonObj = json_decode(\app\domain\game\box\Config::$BOX_CONF, true);
        $confJsonObj = Config::getInstance()->getBoxConf();
        $countsConf = ArrayUtil::safeGet($confJsonObj, 'counts', []);
        $counts = [];
        $boxMap = [];

        foreach ($countsConf as $count) {
            if (!is_integer($count)) {
                Log::error(sprintf('BoxSystem BadCounts %d', $count));
            } else {
                $counts[] = $count;
            }
        }
        $boxesJsonObj = ArrayUtil::safeGet($confJsonObj, 'boxes', []);
        foreach ($boxesJsonObj as $boxJsonObj) {
            $box = new Box();
            $box->decodeFromJson($boxJsonObj);
            if (ArrayUtil::safeGet($boxMap, $box->boxId) != null) {
                Log::error(sprintf('BoxSystem DuplicateBox boxId=%d', $box->boxId));
            }
            $boxMap[$box->boxId] = $box;
            if ($box->boxId == BoxIds::$SILVER) {
                $box->maxPersonalProgress = 1500;
                $box->maxGlobalProgress = 30000;
                $box->personalProgressFullTotalWeight = 5000;
                $box->globalProgressFullTotalWeight = 10000;
                $box->maxPool = 8000;
                $box->maxPoolFull = 8000;
                $box->personalSpecialGiftValue = 521000;
                $box->globalSpecialGiftValue = 521000;
            } elseif ($box->boxId == BoxIds::$GOLD) {
                $box->maxPersonalProgress = 300;
                $box->maxGlobalProgress = 5000;
                $box->personalProgressFullTotalWeight = 5000;
                $box->globalProgressFullTotalWeight = 3000;
                $box->maxPool = 10000;
                $box->maxPoolFull = 10000;
                $box->personalSpecialGiftValue = 888800;
                $box->globalSpecialGiftValue = 888800;
            }
        }

        $this->counts = $counts;
        $this->boxMap = $boxMap;
        $this->eggCoin = $confJsonObj['eggCoin'];
        $this->fullServerCoin = $confJsonObj['fullServerCoin'];
    }
}