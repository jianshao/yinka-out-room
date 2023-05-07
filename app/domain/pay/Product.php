<?php


namespace app\domain\pay;


use app\domain\asset\AssetItem;
use app\utils\ArrayUtil;

class Product
{
    public $productId = 0;
    public $price = 0.0;
    public $bean = 0;
    public $present = 0;
    public $image = '';
    public $chargeMsg = '';
    public $status = 1;
    public $appStoreProductId = '';
    public $deliveryAssets = null;
    public $tag = "";
    public $isAutoRenewal = false;  // 自动续费的产品
    public $autoRenewalPrice = 0;  // 自动续费价格


    public function decodeFromJson($jsonObj)
    {
        $this->productId = $jsonObj['productId'];
        $this->price = $jsonObj['price'];
        $this->bean = $jsonObj['bean'];
        $this->present = $jsonObj['present'];
        $this->image = $jsonObj['image'];
        $this->appStoreProductId = ArrayUtil::safeGet($jsonObj, 'appStoreProductId');
        $this->chargeMsg = ArrayUtil::safeGet($jsonObj, 'chargeMsg', '');
        $this->status = ArrayUtil::safeGet($jsonObj, 'status', 0);
        $this->productId = ArrayUtil::safeGet($jsonObj, 'productId', '');
        $deliveryItems = ArrayUtil::safeGet($jsonObj, 'deliveryItems', []);
        $this->tag = ArrayUtil::safeGet($jsonObj, 'tag', '');
        $this->deliveryAssets = AssetItem::decodeList($deliveryItems);
        $this->isAutoRenewal = ArrayUtil::safeGet($jsonObj, 'isAutoRenewal', false);
        $this->autoRenewalPrice = ArrayUtil::safeGet($jsonObj, 'autoRenewalPrice', '');
        return $this;
    }
}