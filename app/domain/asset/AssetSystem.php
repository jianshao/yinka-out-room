<?php

namespace app\domain\asset;

use app\domain\bank\BankSystem;
use app\domain\gift\GiftSystem;
use app\domain\prop\PropSystem;
use app\utils\ArrayUtil;

/**
 * 资产系统
 */
class AssetSystem
{
    private $kindMap = [];

    // 单例
    protected static $instance;

    private $propLoaded = false;
    private $bankLoaded = false;
    private $giftLoaded = false;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AssetSystem();
            self::$instance->doLoad();
        }
        return self::$instance;
    }

    public function findAssetKind($kindId) {
        if (AssetUtils::isPropAsset($kindId)) {
            $this->loadProp();
        } else if (AssetUtils::isGiftAsset($kindId)) {
            $this->loadGift();
        } else if (AssetUtils::isBank($kindId)) {
            $this->loadBank();
        }

        return ArrayUtil::safeGet($this->kindMap, $kindId);
    }

    private function loadProp() {
        if (!$this->propLoaded) {
            $this->propLoaded = true;
            $propKindMap = PropSystem::getInstance()->getKindMap();
            foreach ($propKindMap as $kindId => $kind) {
                $assetKind = new AssetKindProp($kind);
                $this->kindMap[$assetKind->kindId] = $assetKind;
            }
        }
    }

    private function loadBank() {
        if (!$this->bankLoaded) {
            $this->bankLoaded = true;
            $bankAccountTypeMap = BankSystem::getInstance()->getAccountTypeMap();
            foreach ($bankAccountTypeMap as $typeId => $bankAccountType) {
                $assetKind = new AssetKindBank($bankAccountType);
                $this->kindMap[$assetKind->kindId] = $assetKind;
            }
        }
    }

    private function loadGift() {
        if (!$this->giftLoaded) {
            $this->giftLoaded = true;
            $giftKindMap = GiftSystem::getInstance()->getKindMap();
            foreach ($giftKindMap as $kindId => $giftKind) {
                $assetKind = new AssetKindGift($giftKind);
                $this->kindMap[$assetKind->kindId] = $assetKind;
            }
        }
    }

    private function doLoad() {
        // 1. 加载道具 改为延时加载
        // 2. 加载银行系统 改为延时加载
        // 3. 加载礼物系统 改为延时加载

        // 4. 豆
        $this->kindMap[AssetKindIds::$BEAN] = new AssetKindBean(AssetKindIds::$BEAN);
        // 5. 钻石
        $this->kindMap[AssetKindIds::$DIAMOND] = new AssetKindDiamond(AssetKindIds::$DIAMOND);
        // 6. 金币
        $this->kindMap[AssetKindIds::$COIN] = new AssetKindCoin(AssetKindIds::$COIN);
        // 7. 活跃度
        $this->kindMap[AssetKindIds::$ACTIVE_DEGREE] = new AssetKindActiveDegree(AssetKindIds::$ACTIVE_DEGREE);
        // vip
        $this->kindMap[AssetKindIds::$VIP] = new AssetKindVip(AssetKindIds::$VIP);
        // svip
        $this->kindMap[AssetKindIds::$SVIP] = new AssetKindSvip(AssetKindIds::$SVIP);
        // 月vip
        $this->kindMap[AssetKindIds::$VIP_MONTH] = new AssetKindVipMonth(AssetKindIds::$VIP_MONTH);
        // 月svip
        $this->kindMap[AssetKindIds::$SVIP_MONTH] = new AssetKindSvipMonth(AssetKindIds::$SVIP_MONTH);
        // 用户体力
        $this->kindMap[AssetKindIds::$TAOJIN_ENERGY] = new AssetKindEnergy(AssetKindIds::$TAOJIN_ENERGY);
        // 铁矿石
        $this->kindMap[AssetKindIds::$TAOJIN_ORE_IRON] = new AssetKindOreIron(AssetKindIds::$TAOJIN_ORE_IRON);
        // 银矿石
        $this->kindMap[AssetKindIds::$TAOJIN_ORE_SILVER] = new AssetKindOreSilver(AssetKindIds::$TAOJIN_ORE_SILVER);
        // 金矿石
        $this->kindMap[AssetKindIds::$TAOJIN_ORE_GOLD] = new AssetKindOreGold(AssetKindIds::$TAOJIN_ORE_GOLD);
        // 化石
        $this->kindMap[AssetKindIds::$TAOJIN_ORE_FOSSIL] = new AssetKindOreFossil(AssetKindIds::$TAOJIN_ORE_FOSSIL);
    }
}

