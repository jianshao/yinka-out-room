<?php


namespace app\domain\pay;


use think\facade\Log;

class ProductShelves
{
    public $name = '';
    public $products = [];
    public $productMap = [];

    public function findByAppStoreProductId($appStoreProductId) {
        foreach ($this->products as $product) {
            if ($product->appStoreProductId == $appStoreProductId) {
                return $product;
            }
        }
        return null;
    }

    public function findByProductId($productId) {
        foreach ($this->products as $product) {
            if ($product->productId == $productId) {
                return $product;
            }
        }
        return null;
    }

    public function findByRmb($rmb) {
        $rmbStr = sprintf('%.2f', $rmb);
        foreach ($this->products as $product) {
            $productRmbStr = sprintf('%.2f', $product->price);
            Log::info(sprintf('ProductShelves::findByRmb productId=%d rmb=%s productRmb=%s', $product->productId, $rmbStr, $productRmbStr));
            if ($productRmbStr == $rmbStr) {
                return $product;
            }
        }
        return null;
    }
}