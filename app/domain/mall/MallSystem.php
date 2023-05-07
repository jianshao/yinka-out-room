<?php

namespace app\domain\mall;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;


/**
 * 商城
 */
class MallSystem
{
    protected static $instance;
    // map<goodsId, Goods>
    private $goodsMap = null;
    // map<mallId, Mall>>
    private $mallMap = null;
    // map<assetId, [Goods, Mall]> 资产对应的商品，为了兼容老版本
    private $assetIdToGoodsMap = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MallSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('MallSystemLoaded mallIds %s', json_encode(array_keys(self::$instance->mallMap))));
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

    public function findGoodsByConsumeAssetIdInMall($mallId, $assetId) {
        $mall = $this->findMallByMallId($mallId);
        if ($mall != null) {
            foreach ($mall->getShelvesAreaList() as $shelvesArea) {
                foreach ($shelvesArea->shelvesList as $shelves) {
                    foreach ($shelves->goodsList as $goods) {
                        $asset = $goods->getFirstPriceAsset();
                        if ($asset && $asset->assetId == $assetId) {
                            return $goods;
                        }
                    }
                }
            }
        }
        return null;
    }

    public function getMallMap() {
        return $this->mallMap;
    }

    public function findGoodsByAssetId($assetId) {
        return ArrayUtil::safeGet($this->assetIdToGoodsMap, $assetId);
    }

    private function loadFromJson() {
        $goodsJsonObj = Config::getInstance()->getGoodsConfig();
        $mallJsonObj = Config::getInstance()->getMallConfig();

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

        $assetIdToGoodsMap = [];
        foreach ($goodsMap as $_goodsId => $goods) {
            if ($goods->isBuy() && $goods->deliveryAsset) {
                $mall = ArrayUtil::safeGet($goodsIdToMallMap, $goods->goodsId);
                if ($mall) {
                    $assetIdToGoodsMap[$goods->deliveryAsset->assetId] = [$goods, $mall];
                }
            }
        }

        $this->goodsMap = $goodsMap;
        $this->mallMap = $mallMap;
        $this->assetIdToGoodsMap = $assetIdToGoodsMap;
    }
}

