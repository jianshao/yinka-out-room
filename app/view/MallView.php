<?php

namespace app\view;

use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\gift\Gift;
use app\domain\gift\GiftSystem;
use app\domain\mall\BuyTypes;
use app\domain\mall\Goods;
use app\domain\mall\MallIds;
use app\domain\prop\PropSystem;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class MallView
{
    public static function calcAreaTypeByPid($pid) {
        return ArrayUtil::safeGet(PropView::$pidToPropTypeName, $pid);
    }

    public static function isShowInMall($goods) {
        if ($goods->state != Goods::$ST_IN_SHELVES) {
            return false;
        }
        if (!AssetUtils::isPropAsset($goods->deliveryAsset->assetId)
            && !AssetUtils::isGiftAsset($goods->deliveryAsset->assetId)) {
            return false;
        }
        return true;
    }

    public static function calcAttireTypeByGoods($goods) {
        if (array_key_exists($goods->type, PropView::$propTypeToAttireTypeMap)) {
            return PropView::$propTypeToAttireTypeMap[$goods->type];
        }
        return 1;
    }

    public static function calcIsVipGoods($goods) {
        return ($goods->buyType == BuyTypes::$VIP || $goods->buyType == BuyTypes::$SVIP) ? 1 : 0;
    }

    public static function encodePriceList($goods) {
        $ret = [];
        if ($goods == null or empty($goods->priceList)) {
            $ret[] = ['day' => 0, 'price' => 0];
        } else {
            foreach ($goods->priceList as $price) {
                $ret[] = [
                    'day' => $price->count,
                    'price' => $price->assetItem->count
                ];
            }
        }
        return $ret;
    }

    public static function getBubbleWorldImage($propKindId) {
        $propKind = PropSystem::getInstance()->findPropKind($propKindId);
        if ($propKind) {
            return CommonUtil::buildImageUrl($propKind->bubbleWordImage);
        }
        return '';
    }

    public static function encodeGoods($goods, $mall, $shelvesArea) {
        $propKindId = AssetUtils::getPropKindIdFromAssetId($goods->deliveryAsset->assetId);
        return [
            'attire_type' => self::calcAttireTypeByGoods($goods),
            'attire_image' => CommonUtil::buildImageUrl($goods->image),
            'attire_imageSvga' => CommonUtil::buildImageUrl($goods->animation),
            'attire_name' => $goods->name,
            'id' => $propKindId,
            'is_buy' => $goods->isBuy() ? 1 : 0,
            'attire_describe' => $goods->desc,
            'attire_price' => self::encodePriceList($goods),
            'room_id' => 0,
            'get_type' => self::calcGetType($goods, $mall),
            'is_vip' => ($goods->buyType == BuyTypes::$VIP || $goods->buyType == BuyTypes::$SVIP) ? 1 : 0,
            'activity_url' => $goods->activityUrl,
            'bubble_word_image' => self::getBubbleWorldImage($propKindId)
        ];
    }

    public static function calcGoodsEndTimeStr($goods, $timestamp) {
        $ret = '永久';
        if ($goods->expiresTime > 0) {
            $delta = max(0, $goods->expiresTime - $timestamp);
            if ($delta <= 0) {
                $ret = '已过期';
            } elseif ($delta <= 86400) {
                $ret = '1天';
            } else {
                $tmp = $delta / 86400;
                $ret = floor($tmp) . '天';
            }
        }
        return $ret;
    }

    public static function encodeGoodsWithOre($goods, $mall, $shelvesArea) {
        $giftValue = 0;
        if ($goods->deliveryAsset != null) {
            $giftKindId = AssetUtils::getGiftKindIdFromAssetId($goods->deliveryAsset->assetId);
            if ($giftKindId != null) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftKindId);
                if ($giftKind != null
                    && $giftKind->price != null
                    && $giftKind->price->assetId == AssetKindIds::$BEAN) {
                    $giftValue = $giftKind->price->count;
                }
            }
        }

        $isGameExchange = 0;
        $priceItem = $goods->getFirstPriceAsset();
        if ($priceItem != null) {
            $priceMap = [
                AssetKindIds::$TAOJIN_ORE_FOSSIL => 1,
                AssetKindIds::$TAOJIN_ORE_GOLD => 2,
                AssetKindIds::$TAOJIN_ORE_SILVER => 3,
                AssetKindIds::$TAOJIN_ORE_IRON => 4
            ];

            $isGameExchange = ArrayUtil::safeGet($priceMap, $priceItem->assetId, 0);
        }
        return [
            'giftid' => $goods->goodsId,
            'gift_name' => $goods->name,
            'gift_image' => CommonUtil::buildImageUrl($goods->image),
            'giftgame_price' => $goods->getTotalPrice(),
            'is_gameexchange' => $isGameExchange,
            'gift_coin' => $giftValue
        ];
    }

    public static function encodeGoodsWithCoin($goods, $mall, $shelvesArea) {
        return [
            'reward_id' => $goods->goodsId,
            'reward_name' => $goods->name.'*'.$goods->deliveryAsset->count.'天',
            'reward_image' => CommonUtil::buildImageUrl($goods->image),
            'reward_price' => $goods->getTotalPrice(),
            'reward_type' => self::calcAttireTypeByGoods($goods),
            'room_id' => 0,
            'is_newmall'=> 0
        ];
    }

    public static function encodeGoodsWithPropBag($goods, $mall, $shelvesArea, $propBag, $timestamp) {
        $prop = null;
        if ($goods->deliveryAsset != null) {
            $propKindId = AssetUtils::getPropKindIdFromAssetId($goods->deliveryAsset->assetId);
            if ($propKindId != null) {
                $prop = $propBag->findPropByKindId($propKindId);
            }
        }
        $isHave = $prop != null ? 1 : 0;
        $propKindId = AssetUtils::getPropKindIdFromAssetId($goods->deliveryAsset->assetId);
        return [
            'attire_type' => self::calcAttireTypeByGoods($goods),
            'attire_image' => CommonUtil::buildImageUrl($goods->image),
            'corner_sign' => '',
            'attire_imageSvga' => CommonUtil::buildImageUrl($goods->animation),
            'attire_name' => $goods->name,
            'multiple' => $prop?$prop->kind->multiple:2,
            'id' => $propKindId,
            'is_buy' => $goods->isBuy() ? 1 : 0,
            'attire_describe' => $goods->desc,
            'attire_price' => self::encodePriceList($goods),
            'room_id' => 0,
            'get_type' => self::calcGetType($goods, $mall),
            'is_vip' => ($goods->buyType == BuyTypes::$VIP || $goods->buyType == BuyTypes::$SVIP) ? 1 : 0,
            'activity_url' => $goods->activityUrl,
            'bubble_word_image' => self::getBubbleWorldImage($propKindId),
            'userEndTime' => PropView::calcPropEndTimeStr($prop, $timestamp),
            'isHave' => $isHave,
            'endTime' => self::calcGoodsEndTimeStr($goods, $timestamp),
            'actions' => $goods->actions
        ];
    }

    public static function calcGetType($goods, $mall) {
        switch ($goods->buyType) {
            case BuyTypes::$GOLD_BOX:
                return 1;
                break;
            case BuyTypes::$SILVER_BOX:
                return 2;
                break;
            case BuyTypes::$BUY:
                if ($mall->getMallId() == MallIds::$BEAN) {
                    return 3;
                } else {
                    return 8;
                }
                break;
            case BuyTypes::$FIRST_PAY:
                return 4;
                break;
            case BuyTypes::$VIP:
                return 6;
                break;
            case BuyTypes::$SVIP:
                return 7;
                break;
            case BuyTypes::$DUKE:
                return 10;
                break;
            case BuyTypes::$ACTIVITY:
                // TODO
                return 9;
                break;
            default:
                return 0;
                break;
        }
    }
}