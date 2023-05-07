<?php
/*
 * 访客类
 */

namespace app\api\controller\v1;

use app\api\view\v1\VipView;
use app\domain\asset\AssetKindIds;
use app\domain\autorenewal\dao\AutoRenewalAgreementModelDao;
use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\exceptions\FQException;
use app\domain\pay\ChargeService;
use app\domain\pay\ProductAreaNames;
use app\domain\pay\ProductShelvesNames;
use app\domain\pay\ProductSystem;
use app\domain\pay\ProductTypes;
use app\domain\vip\dao\VipModelDao;
use app\domain\vip\service\VipService;
use app\domain\vip\VipSystem;
use app\query\site\service\SiteService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class VipController extends ApiBaseController
{
    /**
     * 会员特权列表
     * @return mixed
     */
    public function privilegeList()
    {
        $level = Request::param('type'); // 1:月会员 2：年会员
        if (!$level) {
            return rjson([], 500, '参数错误');
        }

        $queryVipLevel = VipSystem::getInstance()->findVipLevel($level);
        if ($queryVipLevel == null) {
            return rjson([], 500, '会员等级不存在');
        }

        $userId = intval($this->headUid);

        $vipModel = VipModelDao::getInstance()->loadVip($userId);

        if ($vipModel == null) {
            return rjson([], 500, '用户信息错误');
        }

        // 获取所有级别的特权
        $privilegeDescList = [];
        $vipLevels = VipSystem::getInstance()->getVipLevels();
        $privilegeId = 1;
        $privilegeGroup = [];
        foreach ($vipLevels as $vipLevel) {
            if ($vipLevel->level == $level) {
                foreach ($vipLevel->privilegeDescList as $privilegeDesc) {
                    $privilegeDescList[] = [
                        'id' => $privilegeId,
                        'sort' => $privilegeId++,
                        'pid' => $privilegeDesc->pid,
                        'type' => $vipLevel->level,
                        'picture' => CommonUtil::buildImageUrl($privilegeDesc->picture),
                        'title' => $privilegeDesc->title,
                        'preview_picture' => CommonUtil::buildImageUrl($privilegeDesc->previewPicture),
                        'content' => $privilegeDesc->content,
                        'content_small' => $privilegeDesc->contentSmall,
                        'state' => $privilegeDesc->status
                    ];
                }
                $privilegeGroup = $vipLevel->privilegeGroup;
            }
        }

        // 获取权益组
        $groupList = [];
        foreach ($privilegeGroup as $group) {
            $privilegeList = [];
            foreach ($privilegeDescList as $privilegeDesc) {
                if ($privilegeDesc['pid'] == $group->id) {
                    $privilegeList[] = $privilegeDesc;
                }
            }
            $groupList[] = [
                'id' => $group->id,
                'title' => $group->title,
                'content' => $group->content,
                'pic' => CommonUtil::buildImageUrl($group->pic),
                'status' => $group->status,
                'privilege_list' => $privilegeList,
            ];
        }

        $timestamp = time();
        $vipInfo = VipView::viewVip($vipModel, $timestamp);
        // 是否为自动续费用户
        $agreementVipModel = AutoRenewalAgreementModelDao::getInstance()->getUserAgreement($userId, ProductTypes::$VIP);
        $agreementSVipModel = AutoRenewalAgreementModelDao::getInstance()->getUserAgreement($userId, ProductTypes::$SVIP);
        $vipAutoPay = (bool)$agreementVipModel;
        $svipAutoPay = (bool)$agreementSVipModel;
        $vipInfo['vip_auto_pay'] = $vipAutoPay;
        $vipInfo['svip_auto_pay'] = $svipAutoPay;

        $result = [
            'privilege_list' => $privilegeDescList,
            'userInfo' => $vipInfo,
            'privilege_group_list' => $groupList
        ];
        return rjson($result);
    }

    private function getMonthAndAssetId($product)
    {
        if ($product->deliveryAssets && count($product->deliveryAssets) > 0) {
            foreach ($product->deliveryAssets as $assetItem) {
                if ($assetItem->assetId == AssetKindIds::$VIP_MONTH || $assetItem->assetId == AssetKindIds::$SVIP_MONTH) {
                    return [$assetItem->count, $assetItem->assetId];
                }
            }
        }
        return [0, AssetKindIds::$VIP_MONTH];
    }

    private function encodeVipProduct($product, $isChecked, $typeStr, $firstProductIds)
    {
        list($month, $assetId) = $this->getMonthAndAssetId($product);
        if ($month > 0) {
            $typeStrCover = $typeStr;
            $ret = [
                'productId' => $product->productId,
                'month' => $month,
                'sale' => intval($product->price),
                'before_sale' => intval($product->present),
                'tag' => $assetId == AssetKindIds::$SVIP_MONTH,
                'is_checked' => $isChecked,
                'type' => $typeStrCover,
                'title' => $product->chargeMsg, // 会员展示文案
                'first_membership' => false,   // 是否是新客专享会员商品
                'is_auto_renewal' => $product->isAutoRenewal,    // 是否是自动续费产品
                'auto_renewal_price' => $product->autoRenewalPrice // 自动续费次月价格
            ];
            if (in_array($product->productId, $firstProductIds)) {
                $ret['first_membership'] = true;
            }
            if (!empty($product->appStoreProductId)) {
                $ret['sign'] = $product->appStoreProductId;
            }
            return $ret;
        }
        return null;
    }

    /**
     * @param $products
     * @param $typeStr
     * @return array
     */
    private function encodeVipProducts($products, $typeStr, $areaName)
    {
        $isChecked = true;
        $ret = [];
        $defaultProduct = null;
        $firstVipProduct = ProductSystem::getInstance()->getProductMap($areaName, ProductShelvesNames::$FIRST_VIP_AUTO);
        $firstSvipProduct = ProductSystem::getInstance()->getProductMap($areaName, ProductShelvesNames::$FIRST_SVIP_AUTO);
        $firstProductIds = array_merge(array_keys($firstVipProduct), array_keys($firstSvipProduct));
        foreach ($products as $product) {
            $encodedProduct = $this->encodeVipProduct($product, $isChecked, $typeStr, $firstProductIds);
            if ($encodedProduct) {
                if ($defaultProduct === null) {
                    $defaultProduct = $product;
                }
                $isChecked = false;
                $ret[] = $encodedProduct;
            }
        }
        return [$ret, $defaultProduct];
    }

    /**
     * 会员init
     * 作废，老版本逻辑
     */
    public function vipChargeInit()
    {
        $showType = Request::param('showType', 1); // 1:获取全部的vip、svip   2:获取svip
        if (!$showType) {
            return rjson([], 500, '参数错误');
        }

        try {
            $result = [];
            $userId = intval($this->headUid);
            if ($showType == 1) {
                $vipResult = $this->getVipResult($userId, ProductTypes::$VIP, ProductShelvesNames::$VIP, false);
                $svipResult = $this->getVipResult($userId, ProductTypes::$SVIP, ProductShelvesNames::$SVIP, false);

                $coinIos = array_merge(ArrayUtil::safeGet($vipResult, 'coin_ios'), ArrayUtil::safeGet($svipResult, 'coin_ios'));
                if (!config("config.old_ios_show_new_vip",false)){
                    foreach ($coinIos as $key => $coin) {
                        if (in_array(ArrayUtil::safeGet($coin, 'productId'), [108, 106, 107])) {
                            unset($coinIos[$key]);
                        }
                    }
                    $coinIos = array_values($coinIos);
                }
                $result['coin_ios'] = $this->handleIsChecked($coinIos);

                $coinAndroid = array_merge(ArrayUtil::safeGet($vipResult, 'coin_android'), ArrayUtil::safeGet($svipResult, 'coin_android'));
                $result['coin_android'] = $this->handleIsChecked($coinAndroid);

                $result['default_ios_id'] = ArrayUtil::safeGet($vipResult, 'default_ios_id');
                $result['default_android_id'] = ArrayUtil::safeGet($vipResult, 'default_android_id');
            } else if ($showType == 2) {
                $result = $this->getVipResult($userId, ProductTypes::$SVIP, ProductShelvesNames::$SVIP, false);
            }

            $channel = $this->getPayChannels();
            $result['pay_channel'] = $channel;
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 新版会员初始化
     */
    public function vipChargeInitSecond()
    {
        $showType = Request::param('showType', 1); // 1:vip   2:svip
        if (!$showType) {
            return rjson([], 500, '参数错误');
        }

        try {
            $result = [];
            $userId = intval($this->headUid);

            // 仅原生支付宝支持自动续费
            $isGetOn = true;
            $aliPayChannelWay = config("config.ali_pay_channel_way", 1);
            if ($aliPayChannelWay != 1){
                $isGetOn = false;
            }
            if ($showType == 1) {
                $result = $this->getVipResult($userId, ProductTypes::$VIP, ProductShelvesNames::$VIP, $isGetOn);
            } else if ($showType == 2) {
                $result = $this->getVipResult($userId, ProductTypes::$SVIP, ProductShelvesNames::$SVIP, $isGetOn);
            }

            $channel = $this->getPayChannels();
            $result['pay_channel'] = $channel;
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function handleIsChecked($coins)
    {
        foreach ($coins as $key => &$item) {
            $item['is_checked'] = false;
            if ($key == 0){
                $item['is_checked'] = true;
            }
        }
        return $coins;
    }

    /**
     * @desc 获取vip组装结果
     * @param $userId
     * @param $productType 2:vip  3:svip
     * @param $shelvesNames // vip svip
     * @param $isGetOn  // 是否获取自动续费的商品
     * @return array
     */
    public function getVipResult($userId, $productType, $shelvesNames, $isGetOn)
    {
        $iosOnShelves = $androidOnShelves = null;
        // 自动续费商品
        if ($isGetOn){
            list($iosOnShelves, $androidOnShelves) = $this->getVipOnShelves($userId, $productType);
        }

        $iosShelves = ProductSystem::getInstance()->getShelves(ProductAreaNames::$IOS, $shelvesNames);
        $androidShelves = ProductSystem::getInstance()->getShelves(ProductAreaNames::$ANDROID, $shelvesNames);

        // 组装数据
        list($iosProducts, $defaultIosId) = $this->formetProducts($iosShelves, $iosOnShelves, ProductAreaNames::$IOS, $shelvesNames);
        list($androidProducts, $defaultAndroidId) = $this->formetProducts($androidShelves, $androidOnShelves, ProductAreaNames::$ANDROID, $shelvesNames);

        return [
            'coin_ios' => $iosProducts,
            'coin_android' => $androidProducts,
            'default_ios_id' => $defaultIosId,
            'default_android_id' => $defaultAndroidId,
        ];
    }

    /**
     * @desc 获取自动续费的产品
     * @param $userId
     * @param $productTypes 2:vip  3:svip
     * @return array
     * @throws \Exception
     */
    public function getVipOnShelves($userId, $productTypes)
    {
        $isBuyVip = VipService::getInstance()->isVipPayOpen($userId);
        // 自动续费商品
        $shelvesOnNames = ProductShelvesNames::$VIP_AUTO;
        if ($productTypes == ProductTypes::$VIP) {
            if (!$isBuyVip) {
                $shelvesOnNames = ProductShelvesNames::$FIRST_VIP_AUTO;
            }
        }
        if ($productTypes == ProductTypes::$SVIP) {
            $shelvesOnNames = ProductShelvesNames::$SVIP_AUTO;
            if (!$isBuyVip) {
                $shelvesOnNames = ProductShelvesNames::$FIRST_SVIP_AUTO;
            }
        }
        // 是否为自动续费用户
        $vipAutoPay = AutoRenewalService::getInstance()->processVipAgreementStatus($userId, $productTypes);
        $iosOnShelves = $androidOnShelves = null;
        if (!$vipAutoPay) {
            // 获取连续续费的商品
            $iosOnShelves = ProductSystem::getInstance()->getShelves(ProductAreaNames::$IOS, $shelvesOnNames);
            $androidOnShelves = ProductSystem::getInstance()->getShelves(ProductAreaNames::$ANDROID, $shelvesOnNames);
        }
        return [$iosOnShelves, $androidOnShelves];
    }

    /**
     * @desc 组装数据
     * @param $shelves
     * @param $onShelves
     * @param $areaNames
     * @param $typeStr
     * @return array
     */
    public function formetProducts($shelves, $onShelves, $areaNames, $typeStr)
    {
        $products = [];
        $defaultId = 0;
        if ($shelves != null) {
            $products = $shelves->products;
            if ($onShelves != null) {
                $products = array_merge($onShelves->products, $products);
            }
            list($products, $defaultProduct) = $this->encodeVipProducts($products, $typeStr, $areaNames);
            $defaultId = $defaultProduct ? $defaultProduct->productId : 0;
        }

        return [$products, $defaultId];
    }

    /**
     * 会员init
     */
    public function vipChargeInitOld()
    {
        $type = Request::param('showType'); //1 :1,3,6,12   2:12,1,3,6
        if (!$type) {
            return rjson([], 500, '参数错误');
        }
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        $conf = json_decode($siteConf['vip_charge'], true);
        $result['coin_ios'] = $conf["coin_ios$type"];
        $result['coin_android'] = $conf["coin_android$type"];
        $result['pay_channel'] = $this->getPayChannels();
        return rjson($result);
    }

    private function getPayChannels(){
        $payChannels = ChargeService::getInstance()->getPayChannels();
        $payChannelList = [];
        foreach ($payChannels as $payChannel) {
            $payChannelList[] = [
                'id' => $payChannel->id,
                'pid' => $payChannel->pid,
                'content' => $payChannel->content,
                'check' => $payChannel->check,
                'type' => $payChannel->type
            ];
        }
        return $payChannelList;
    }
}
