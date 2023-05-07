<?php

namespace app\domain\mall;

use app\domain\asset\AssetItem;
use app\domain\asset\AssetUtils;
use app\utils\ArrayUtil;

/**
 * 商品
 */
class Goods
{
    // 商品ID
    public $goodsId = '';
    // 显示名称
    public $name = '';
    // 单位
    public $unit = '';
    // 图片
    public $image = '';
    public $imageAndroid = '';
    // 动效
    public $animation = '';
    // 描述
    public $desc = '';
    // 购买类型 buy表示需要消耗资产购买，jump表示跳转
    public $buyType = '';
    // 类型 avatar mount bubble voiceprint等
    public $type = '';
    // 跳转到哪儿
    public $jump = '';
    // 状态 0 上架 1下架
    public $state = 0;
    // 活动url
    public $activityUrl = '';
    // 价格, 只有buyType=buy时才有效
    public $priceList = null;
    // 发什么资产
    public $deliveryAsset = null;
    // 过期时间 -1 永不过期
    public $expiresTime = -1;
    // 更新时间
    public $updateTime = 0;
    // 商品昨用 能卖能赠送
    public $actions = [];

    public static $ST_IN_SHELVES = 1;

    public function isExpires($timestamp) {
        return $this->expiresTime >= 0 and $this->expiresTime >= $timestamp;
    }

    public function isBuy() {
        return in_array(BuyTypes::$BUY, $this->actions);
    }

    public function isSend() {
        return in_array(BuyTypes::$SEND, $this->actions);
    }

    public function getDeliveryAssetKindId(){
        $assetId = $this->deliveryAsset->assetId;
        if(AssetUtils::isPropAsset($assetId)){
            return AssetUtils::getPropKindIdFromAssetId($assetId);
        }elseif (AssetUtils::isGiftAsset($assetId)){
            return AssetUtils::getGiftKindIdFromAssetId($assetId);
        }

        return null;
    }

    public function getTotalPrice() {
        $price = 0;
        foreach ($this->priceList as $key => $value) {
            $price += $value->count * $value->assetItem->count;
        }
        return $price;
    }

    public function getCountByPrice($price) {
        foreach ($this->priceList as $key => $value) {
            if($price == $value->assetItem->count){
                return $value->count;
            }
        }
        return 0;
    }

    public function getFirstPriceAsset() {
        foreach ($this->priceList as $price) {
            if ($price->assetItem != null) {
                return $price->assetItem;
            }
        }
        return null;
    }

    public function decodeFromJson($jsonObj) {
        $this->goodsId = $jsonObj['goodsId'];
        $this->name = $jsonObj['name'];
        $this->desc = $jsonObj['desc'];
        $this->unit = $jsonObj['unit'];
        $this->image = $jsonObj['image'];
        $this->imageAndroid = ArrayUtil::safeGet($jsonObj, 'imageAndroid',"");
        $this->animation = $jsonObj['animation'];
        $this->buyType = $jsonObj['buyType'];
        $this->type = $jsonObj['type'];
        $this->expiresTime = ArrayUtil::safeGet($jsonObj, 'expiresTime', 0);
        $this->jump = ArrayUtil::safeGet($jsonObj, 'jump', '');
        $this->state = ArrayUtil::safeGet($jsonObj, 'state', 0);
        $this->activityUrl = ArrayUtil::safeGet($jsonObj, 'activityUrl', '');
        $this->priceList = [];
        foreach (ArrayUtil::safeGet($jsonObj, 'priceList', []) as $priceConf) {
            $price = new GoodsPrice();
            $price->decodeFromJson($priceConf);
            $this->priceList[] = $price;
        }
        $deliveryAsset = new AssetItem();
        $deliveryAsset->decodeFromJson($jsonObj['content']);
        $this->deliveryAsset = $deliveryAsset;
        $this->updateTime = ArrayUtil::safeGet($jsonObj, 'updateTime',0);
        $this->actions = ArrayUtil::safeGet($jsonObj, 'actions', []);

        if ($this->buyType == BuyTypes::$BUY && !ArrayUtil::safeGet($this->actions, BuyTypes::$BUY)){
            $this->actions[] = BuyTypes::$BUY;
        }

        return $this;
    }
}


