<?php

namespace app\domain\game\gashapon;


use app\domain\asset\AssetItem;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use app\domain\Config;
use think\facade\Log;


/**
 * 金币抽奖
 */
class GashaponSystem
{
    protected static $instance;
    // 转盘列表
    public $lotteryMap = [];
    // 价格配置
    public $price = null;
    public $counts = [1, 10];
    public $lotteryTotalWeight = 0;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GashaponSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function getConf() {
        return Config::getInstance()->getConfigByKey("gashapon_conf");
    }

    public function findLottery($lotteryId) {
        return ArrayUtil::safeGet($this->lotteryMap, $lotteryId);
    }

    public static function setConf($conf) {
        self::decodeConf($conf);
        Config::getInstance()->setConfigByKey('gashapon_conf', $conf);
    }

    private static function decodeConf($conf)
    {
        $gashaponConfig = ArrayUtil::safeGet($conf, 'gashapon');

        $lotterys = ArrayUtil::safeGet($gashaponConfig, 'lotterys');
        if ($lotterys == null) {
            throw new FQException('没有奖励配置', 500);
        }

        $lotteryMap = [];
        foreach ($lotterys as $lotteryJson) {
            $lottery = new Lottery();
            $lottery->loadFromJson($lotteryJson);

            if (array_key_exists($lottery->lotteryId, $lotteryMap)) {
                Log::warning(sprintf('GashaponSystem::decodeConf DuplicateBox lotteryId=%s', $lottery->lotteryId));
                throw new FQException('道具id配置重复，id=' . $lottery->lotteryId, 500);
            }

            $lotteryMap[$lottery->lotteryId] = $lottery;
        }

        if (ArrayUtil::safeGet($gashaponConfig, 'price') == null) {
            throw new FQException('没有配置价格', 500);
        }

        $price = new AssetItem($gashaponConfig['price']['assetId'], $gashaponConfig['price']['count']);

        $counts = ArrayUtil::safeGet($gashaponConfig, 'count');

        return [
            'lotteryMap' => $lotteryMap,
            'price' => $price,
            'counts' => $counts
        ];
    }

    protected function loadFromJson() {
        $config = $this->getConf();

        $decodedConf = $this->decodeConf($config);

        $this->lotteryMap = $decodedConf['lotteryMap'];
        $this->price = $decodedConf['price'];

        if(ArrayUtil::safeGet($decodedConf, 'counts') != null) {
            $this->counts = $decodedConf['counts'];
        }

        $this->lotteryTotalWeight = 0;
        foreach ($this->lotteryMap as $k => $lottery) {
            $this->lotteryTotalWeight += $lottery->weight;
        }

        Log::info(sprintf('GashaponSystem::loadFromJson lotteryMap=%s', json_encode($this->lotteryMap)));
    }

    public function decodeFromRedisJson($jsonObj) {
        $giftMap = $jsonObj['gifts'];
        foreach ($giftMap as $lotteryId => $count){
            $lottery = ArrayUtil::safeGet($this->lotteryMap, $lotteryId);
            if(!empty($lottery)){
                $lottery->weight = $count;
            }
        }

        return $this;
    }

    public function encodeToDaoRedisJson() {
        $giftMap = [];
        foreach ($this->lotteryMap as $lotteryId => $lottery){
            $giftMap[$lotteryId] = $lottery->weight;
        }
        $ret = [
            'gifts' => $giftMap,
        ];
        return json_encode($ret);
    }

}

