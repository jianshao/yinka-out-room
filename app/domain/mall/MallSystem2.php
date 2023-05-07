<?php

namespace app\domain\mall;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;


/**
 * 商城
 */
class MallSystem2
{
    protected static $instance;
    # 最近上新礼物显示数量
    public static $newGiftCount = 3;
    # 热门礼物显示数量
    public static $hotGiftCount = 6;
    // map<goodsId, Goods>
    private $goodsMap = null;
    // map<mallId, Mall>>
    private $mallMap = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MallSystem2();
            self::$instance->loadFromJson();
            Log::info(sprintf('MallSystem2Loaded mallIds %s', json_encode(array_keys(self::$instance->mallMap))));
        }
        return self::$instance;
    }

    public function findGoods($goodsId) {
        return ArrayUtil::safeGet($this->goodsMap, $goodsId, null);
    }

    public function getGoodsMap() {
        return $this->goodsMap;
    }

    public function findMallByMallId($mallId) {
        return ArrayUtil::safeGet($this->mallMap, $mallId, null);
    }


    public function getMallMap() {
        return $this->mallMap;
    }


    private function loadFromJson() {
        $goodsJsonObj = Config::getInstance()->getGoodsConfig();
        $mallJsonObj = Config::getInstance()->getMall2Config();

        $goodsMap = [];
        foreach (ArrayUtil::safeGet($goodsJsonObj, 'goods', []) as $goodsConf) {
            $goods = new Goods();
            $goods->decodeFromJson($goodsConf);
            $goodsMap[$goods->goodsId] = $goods;
        }

        $mallMap = [];
        // 为了兼容老版本
        $goodsIdToMallMap = [];

        foreach ($mallJsonObj as $mallId => $mallConf) {
            $mall = new Mall($mallId);
            $mall->decodeFromJson($mallConf);
            $mallMap[$mall->getMallId()] = $mall;
            foreach ($mall->getShelvesAreaList() as $shelvesArea) {
                foreach ($shelvesArea->shelvesList as $shelves) {
                    $shelves->initGoodsList($goodsMap);
                    foreach ($shelves->goodsIdList as $goodsId) {
                        $goodsIdToMallMap[$goodsId] = $mall;
                    }
                }
            }
        }


        $this->goodsMap = $goodsMap;
        $this->mallMap = $mallMap;
    }
}

