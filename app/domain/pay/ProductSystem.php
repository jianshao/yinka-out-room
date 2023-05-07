<?php


namespace app\domain\pay;


use app\domain\Config;
use app\domain\mall\BuyTypes;
use app\utils\ArrayUtil;
use think\facade\Log;

class ProductSystem
{
    protected static $instance;
    private $productMap = [];
    private $areaMap = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ProductSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('ProductSystemLoaded productIds=%s areas=%s',
                json_encode(array_keys(self::$instance->productMap)),
                json_encode(array_keys(self::$instance->areaMap))));
        }
        return self::$instance;
    }

    public function getArea($areaName) {
        return ArrayUtil::safeGet($this->areaMap, $areaName);
    }

    public function getShelves($areaName, $shelvesName) {
        $area = $this->getArea($areaName);
        if ($area != null) {
            return $area->findShelves($shelvesName);
        }
        return null;
    }

    public function getProductMap($areaName, $shelvesName) {
        $area = $this->getArea($areaName);
        if ($area != null) {
            $shelves = $area->findShelves($shelvesName);
            if ($shelves != null) {
                return $shelves->productMap;
            }
        }
        return [];
    }

    public function findProduct($productId) {
        return ArrayUtil::safeGet($this->productMap, $productId);
    }

    public function findByRmb($areaName, $shelvesName, $rmb) {
        $area = $this->getArea($areaName);
        if ($area == null) {
            return null;
        }
        return $area->findByRmb($rmb, $shelvesName);
    }

    public function getProductTypeByShelvesName($shelvesName) {
        $SHELVES_NAME_TO_PRODUCT_TYPE = [
            ProductShelvesNames::$SVIP => ProductTypes::$SVIP,
            ProductShelvesNames::$VIP => ProductTypes::$VIP,
            ProductShelvesNames::$RED_PACKET => ProductTypes::$REDPACKET,
            ProductShelvesNames::$SVIP_AUTO => ProductTypes::$SVIP,
            ProductShelvesNames::$FIRST_SVIP_AUTO => ProductTypes::$SVIP,
            ProductShelvesNames::$VIP_AUTO => ProductTypes::$VIP,
            ProductShelvesNames::$FIRST_VIP_AUTO => ProductTypes::$VIP,

        ];
        return ArrayUtil::safeGet($SHELVES_NAME_TO_PRODUCT_TYPE, $shelvesName, ProductTypes::$BEAN);
    }

    public function getProductType($productId) {
        foreach ($this->areaMap as $areaName => $area) {
            foreach ($area->shelvesMap as $shelvesName => $shelves) {
                if (array_key_exists($productId, $shelves->productMap)) {
                    return $this->getProductTypeByShelvesName($shelvesName);
                }
            }
        }
        return ProductTypes::$BEAN;
    }

    public function findProductByRmbInShelves($areaName, $shelvesNames, $rmb) {
        $area = $this->getArea($areaName);
        if ($area == null) {
            return null;
        }
        foreach ($shelvesNames as $shelvesName) {
            $product = $area->findByRmb($rmb, $shelvesName);
            if ($product != null) {
                return $product;
            }
        }
        return null;
    }

    public function findProductByProductIdInShelves($areaName, $shelvesNames, $productId) {
        $area = $this->getArea($areaName);
        if ($area == null) {
            return null;
        }
        foreach ($shelvesNames as $shelvesName) {
            $product = $area->findByProductId($productId, $shelvesName);
            if ($product != null) {
                return $product;
            }
        }
        return null;
    }

    public function findByAppStoreProductId($areaName, $shelvesName, $appStoreProductId) {
        $area = $this->getArea($areaName);
        if ($area == null) {
            return null;
        }
        return $area->findByAppStoreProductId($appStoreProductId, $shelvesName);
    }

    private function loadFromJson() {
        $chargeConf = Config::getInstance()->getChargeConf();
        $productsJsonObj = ArrayUtil::safeGet($chargeConf, 'products');
        $productMap = [];
        foreach ($productsJsonObj as $productJson) {
            $product = new Product();
            $product->decodeFromJson($productJson);
            if (array_key_exists($product->productId, $productMap)) {
                Log::warning(sprintf('ProductSystem::loadFromJson Duplicate productId: %d', $product->productId));
            }
            $productMap[$product->productId] = $product;
        }

        $mallConf = Config::getInstance()->getChargeMallConf();
        $areaMap = [];
        foreach ($mallConf as $areaName => $areaJsonObj) {
            $area = new ProductArea($areaName);
            $area->decodeFromJson($areaJsonObj, $productMap);
            $areaMap[$areaName] = $area;
        }

        $this->productMap = $productMap;
        $this->areaMap = $areaMap;
    }
}