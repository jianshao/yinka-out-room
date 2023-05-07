<?php


namespace app\domain\gift;

use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\gift\dao\GiftModelDao;
use app\domain\gift\model\GiftModel;
use app\utils\ArrayUtil;
use think\facade\Log;

class GiftBag
{
    // 所属user
    private $user = 0;
    // map<kindId, Gift>
    private $kindGiftMap = null;
    // 是否加载了
    private $_isLoaded = false;

    public function __construct($user) {
        $this->user = $user;
    }

    /**
     * 获取背包user对象
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * 是否加载了
     */
    public function isLoaded() {
        return $this->_isLoaded;
    }

    /**
     * 加载用户背包
     */
    public function load($timestamp) {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            Log::info(sprintf('GiftBagLoaded userId=%d count=%d',
                $this->getUserId(), count($this->kindGiftMap)));
        }
    }

    /**
     * 获取背包userId
     */
    public function getUserId() {
        return $this->user->getUserId();
    }

    /**
     * 获取用户所有的道具
     *
     * @return map<kindId, Gift>
     */
    public function getGiftMap() {
        return $this->giftMap;
    }

    /**
     * 查找类型为kindId的道具
     *
     * @param kindId: 类型ID
     * @return 找到返回Gift, 没找到返回null
     */
    public function findGiftByKindId($kindId) {
        return ArrayUtil::safeGet($this->kindGiftMap, $kindId);
    }

    /**
     *
     */
    public function add($kindId, $count, $timestamp, $biEvent) {
        assert($count > 0);
        $gift = $this->findGiftByKindId($kindId);
        if ($gift == null) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($kindId);
            if ($giftKind == null) {
                throw new FQException('礼物不存在', -1);
            }
            $model = new GiftModel($kindId, $timestamp, $timestamp, $count);
            $gift = new Gift($giftKind);
            $gift->model = $model;
            GiftModelDao::getInstance()->createGift($this->getUserId(), $gift->model);
            $this->kindGiftMap[$kindId] = $gift;
        } else {
            if (!GiftModelDao::getInstance()->incGift($this->getUserId(), $kindId, $count, $timestamp)) {
                throw new AssetNotEnoughException('背包礼物不足');
            }
            $gift->model->count += $count;
            $gift->model->updateTime = $timestamp;
        }

        $balance = $gift->model->count;

        BIReport::getInstance()->reportGift($this->getUserId(), $kindId, $count, $balance, $timestamp, $biEvent);

        Log::info(sprintf('GiftBagAddOk userId=%d giftId=%d count=%d balance=%d',
                    $this->getUserId(), $gift->kind->kindId, $count, $balance));

        // TODO event

        return $balance;
    }

    public function consume($kindId, $count, $timestamp, $biEvent) {
        assert($count >= 0);
        $gift = $this->findGiftByKindId($kindId);

        if ($gift == null) {
            throw new AssetNotEnoughException('礼物背包数量不足', 500);
        }

        $balance = $gift->model->count;

        if ($balance < $count) {
            throw new AssetNotEnoughException('礼物背包数量不足', 500);
        }

        if (!GiftModelDao::getInstance()->decGift($this->getUserId(), $kindId, $count, $timestamp)) {
            throw new AssetNotEnoughException('礼物背包数量不足', 500);
        }

        $gift->model->count -= $count;
        $gift->model->updateTime = $timestamp;

        $balance = $gift->model->count;

        BIReport::getInstance()->reportGift($this->getUserId(), $kindId, -$count, $balance, $timestamp, $biEvent);

        Log::info(sprintf('GiftBagConsumeOk userId=%d giftId=%d count=%d balance=%d',
            $this->getUserId(), $gift->kind->kindId, $count, $balance));

        // TODO event

        return $balance;
    }

    /**
     * 强制消耗count个，如果不够则扣除所有余额
     *
     * @return: 实际消耗的数量
     */
    public function forceConsume($kindId, $count, $timestamp, $biEvent) {
        assert($count >= 0);
        $gift = $this->findGiftByKindId($kindId);
        if ($gift == null) {
            return 0;
        }

        $consumeCount = min($count, $gift->model->count);

        if ($consumeCount <= 0) {
            return 0;
        }

        $gift->model->count -= $consumeCount;
        $gift->model->updateTime = $timestamp;

        if (!GiftModelDao::getInstance()->decGift($this->getUserId(), $kindId, $count, $timestamp)) {
            throw new AssetNotEnoughException('礼物背包数量不足', 500);
        }

        $balance = $gift->model->count;

        BIReport::getInstance()->reportGift($this->getUserId(), $kindId, -$count, $balance, $timestamp, $biEvent);

        Log::info(sprintf('GiftBagForceConsumeOk userId=%d giftId=%d count=%d consumeCount=%d balance=%d',
            $this->getUserId(), $gift->kind->kindId, $count, $consumeCount, $balance));

        // TODO event

        return balance;
    }

    public function balance($kindId, $timestamp) {
        $gift = $this->findGiftByKindId($kindId);
        return $gift != null ? $gift->model->count : 0;
    }

    private function doLoad($timestamp) {
        $giftModels = GiftModelDao::getInstance()->loadAllGiftByUserId($this->getUserId());
        $kindGiftMap = [];
        foreach ($giftModels as $giftModel) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftModel->kindId);
            if ($giftKind != null) {
                $gift = new Gift($giftKind);
                $gift->model = $giftModel;
                // 如果已经存在该类型的礼物，则删除
                $existsGift = ArrayUtil::safeGet($kindGiftMap, $giftKind->kindId);
                if ($existsGift != null) {
                    unset($kindGiftMap[$existsGift->kind->kindId]);
                }
                $kindGiftMap[$gift->kind->kindId] = $gift;
            } else {
                Log::warning(sprintf('GiftBagLoadUnknownKind userId=%d kindId=%d',
                    $this->getUserId(), $giftModel->kindId));
            }
        }

        $this->kindGiftMap = $kindGiftMap;
    }
}