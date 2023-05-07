<?php


namespace app\domain\pay;
use app\utils\ArrayUtil;
use think\facade\Log;

class ProductArea
{
    public $name = '';
    public $shelvesMap = [];

    public function __construct($name) {
        $this->name = $name;
    }

    public function findShelves($shelvesName) {
        return ArrayUtil::safeGet($this->shelvesMap, $shelvesName);
    }

    public function getAppStoreProductIdList(){
        $result=[];
        foreach ($this->shelvesMap as $shelves) {
            foreach($shelves->products as $product){
                if(isset($product->appStoreProductId)){
                    $result[$product->productId]=$product->appStoreProductId;
                }
            }
        }
        return $result;
    }

    public function findByAppStoreProductId($appStoreProductId, $shelvesName=null) {
        if ($shelvesName != null) {
            $shelves = $this->findShelves($shelvesName);
            if (!$shelves) {
                return null;
            }
            return $shelves->findByAppStoreProductId($appStoreProductId);
        } else {
            foreach ($this->shelvesMap as $_ => $shelves) {
                $product = $shelves->findByAppStoreProductId($appStoreProductId);
                if ($product) {
                    return $product;
                }
            }
            return null;
        }
    }

    public function findByProductId($productId, $shelvesName=null) {
        if ($shelvesName != null) {
            $shelves = $this->findShelves($shelvesName);
            if (!$shelves) {
                return null;
            }
            return $shelves->findByProductId($productId);
        } else {
            foreach ($this->shelvesMap as $_ => $shelves) {
                $product = $shelves->findByProductId($productId);
                if ($product) {
                    return $product;
                }
            }
            return null;
        }
    }

    public function findByRmb($rmb, $shelvesName) {
        $shelves = $this->findShelves($shelvesName);
        if (!$shelves) {
            return null;
        }

        return $shelves->findByRmb($rmb);
    }

    public function decodeFromJson($jsonObj, $productMap) {
        foreach ($jsonObj as $shelvesName => $productIds) {
            $shelves = new ProductShelves();
            $shelves->name = $shelvesName;
            foreach ($productIds as $productId) {
                $product = ArrayUtil::safeGet($productMap, $productId);
                if ($product == null) {
                    Log::error(sprintf('Unknown charge product %d for shelves %s area %s', $productId, $shelves->name, $this->name));
                } else {
                    if (array_key_exists($product->productId, $shelves->productMap)) {
//                        Log::error();
                    } else {
                        $shelves->products[] = $product;
                        $shelves->productMap[$product->productId] = $product;
                        $productMap[$productId] = $product;
                    }
                }
            }
            $this->shelvesMap[$shelves->name] = $shelves;
        }
        return $this;
    }
}