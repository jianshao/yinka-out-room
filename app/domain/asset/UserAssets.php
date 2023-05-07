<?php

namespace app\domain\asset;

use app\domain\bank\Bank;
use app\domain\exceptions\FQException;
use app\domain\game\taojin\Ore;
use app\domain\gift\GiftBag;
use app\domain\prop\PropBag;
use app\domain\user\ActiveDegree;
use app\domain\user\Bean;
use app\domain\user\Coin;
use app\domain\bank\BankAccountTypeIds;
use app\domain\user\Diamond;
use app\domain\game\taojin\Energy;
use app\domain\user\service\MonitoringService;
use app\domain\user\service\UnderAgeService;
use app\domain\vip\Vip;
use think\facade\Log;

/**
 * 用户资产
 */
class UserAssets
{
    private $user = null;
    // 道具背包
    private $propBag = null;
    // 礼物背包
    private $giftBag = null;
    // 银行
    private $bank = null;
    // 用户豆
    private $bean = null;
    // 用户钻石
    private $diamond = null;
    // 用户金币
    private $coin = null;
    // 用户活跃度
    private $activeDegree = null;
    // 用户体力; 淘金之旅专用
    private $energy = null;

    // 游戏矿石
    private $ore = null;

    public function __construct($user) {
        $this->user = $user;
        Log::info(sprintf('UserAssets init %d', $user->getUserId()));
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    /**
     * 获取用户道具背包
     */
    public function getPropBag($timestamp) {
        if ($this->propBag == null) {
            $propBag = new PropBag($this->user);
            $propBag->load($timestamp);
            $this->propBag = $propBag;
        }
        return $this->propBag;
    }

    /**
     * 获取用户礼物背包
     */
    public function getGiftBag($timestamp) {
        if ($this->giftBag == null) {
            $giftBag = new GiftBag($this->user);
            $giftBag->load($timestamp);
            $this->giftBag = $giftBag;
        }
        return $this->giftBag;
    }

    /**
     * 获取用户银行
     */
    public function getBank($timestamp) {
        if ($this->bank == null) {
            $bank = new Bank($this->user);
            $bank->load($timestamp);
            $this->bank = $bank;
        }
        return $this->bank;
    }

    /**
     * 获取用户豆
     */
    public function getBean($timestamp) {
        if ($this->bean == null) {
            $bean = new Bean($this->user);
            $bean->load($timestamp);
            $this->bean = $bean;
        }
        return $this->bean;
    }

    /**
     * 获取用户钻石
     */
    public function getDiamond($timestamp) {
        if ($this->diamond == null) {
            $diamond = new Diamond($this->user);
            $diamond->load($timestamp);
            $this->diamond = $diamond;
        }
        return $this->diamond;
    }

    /**
     * 获取用户金币
     */
    public function getCoin($timestamp) {
        if ($this->coin == null) {
            $coin = new Coin($this->user);
            $coin->load($timestamp);
            $this->coin = $coin;
        }
        return $this->coin;
    }

    /**
     * 获取用户活跃值
     */
    public function getActiveDegree($timestamp) {
        if ($this->activeDegree == null) {
            $activeDegree = new ActiveDegree($this->user);
            $activeDegree->load($timestamp);
            $this->activeDegree = $activeDegree;
        }
        return $this->activeDegree;
    }

    /**
     * 获取用户体力
     *
     * @param $timestamp
     * @return Energy
     */
    public function getEnergy($timestamp) {
        if ($this->energy == null) {
            $energy = new Energy($this->user);
            $energy->load($timestamp);
            $this->energy = $energy;
        }
        return $this->energy;
    }

    /**
     * 获取用户体力
     *
     * @param $timestamp
     * @return Vip
     */
    public function getVip($timestamp) {
        return $this->user->getVip($timestamp);
    }

    /**
     * 获取用户矿石
     *
     * @param $timestamp
     * @return Ore
     */
    public function getOre($timestamp) {
        if ($this->ore == null) {
            $ore = new Ore($this->user);
            $ore->load($timestamp);
            $this->ore = $ore;
        }
        return $this->ore;
    }

    /**
     * 给用户增加count个单位的kindId型的资产
     *
     * @param kindId: 资产类型Id
     * @param count: 数量
     * @param timestamp: 当前时间
     */
    public function add($kindId, $count, $timestamp, $biEvent) {
        $kind = AssetSystem::getInstance()->findAssetKind($kindId);
        if ($kind == null) {
            throw new FQException('不能识别的资产类型', 500);
        }
        return $kind->add($this, $count, $timestamp, $biEvent);
    }

    /**
     * 给用户增加count个单位的kindId型的资产
     */
    public function consume($kindId, $count, $timestamp, $biEvent, $source='') {
        if($source != 'admin' && in_array($kindId,[AssetKindIds::$DIAMOND,AssetKindIds::$BEAN,AssetKindIds::$GAME_SCORE])){
            $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($this->getUserId());
            if($isUnderAge){
                throw new FQException('未满18周岁用户暂不支持此功能', 500);
            }
        }
        if($source != 'admin' && in_array($kindId,[AssetKindIds::$DIAMOND,AssetKindIds::$BEAN,AssetKindIds::$GAME_SCORE])){
            list($enable, $monitoringTime) = MonitoringService::getInstance()->getMonitor($this->getUserId());
            if ($enable) {
                throw new FQException("青少年模式下无法使用", 500);
            }
        }
        $kind = AssetSystem::getInstance()->findAssetKind($kindId);
        if ($kind == null) {
            throw new FQException('不能识别的资产类型', 500);
        }
        return $kind->consume($this, $count, $timestamp, $biEvent);
    }

    /**
     * 给用户减少count个单位的kindId型的资产
     *
     * @return: 实际消耗的数量
     */
    public function forceConsume($kindId, $count, $timestamp, $biEvent) {
        $kind = AssetSystem::getInstance()->findAssetKind($kindId);
        if ($kind == null) {
            throw new FQException('不能识别的资产类型', 500);
        }
        $balance = $kind->balance($this, $timestamp);
        $consumeCount = min($balance, $count);
        return $kind->consume($this, $consumeCount, $timestamp, $biEvent);
    }

    /**
     * 获取kindId型资产的余额
     */
    public function balance($kindId, $timestamp) {
        $kind = AssetSystem::getInstance()->findAssetKind($kindId);
        if ($kind == null) {
            throw new FQException('不能识别的资产类型', 500);
        }
        return $kind->balance($this, $timestamp);
    }
}
