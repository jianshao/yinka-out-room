<?php


namespace app\domain\game\taojin;


use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\game\taojin\dao\OreModelDao;
use app\domain\game\taojin\model\OreModel;
use app\utils\ArrayUtil;
use think\facade\Log;

class Ore
{
    private $user = null;
    // OreModel
    private $model = null;
    private $_isLoaded = false;
    private $oldOreMap = [];

    public function __construct($user) {
        $this->user = $user;
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
            Log::info(sprintf('OreLoaded userId=%d iron=%d silver=%d gold=%d fossil=%d',
                $this->getUserId(), $this->model->getOre(OreTypes::$IRON), $this->model->getOre(OreTypes::$SILVER),
                $this->model->getOre(OreTypes::$GOLD), $this->model->getOre(OreTypes::$FOSSIL)));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function addOre($oreType, $count, $timestamp, $biEvent) {
        assert($count >= 0);
        $this->systemDecOre($timestamp, $biEvent);
        $balance = $this->model->getOre($oreType);
        $balance += $count;
        $this->model->setOre($oreType, $balance);
        $this->model->updateTime = $timestamp;

        OreModelDao::getInstance()->incOre($this->getUserId(), $oreType, $count, $this->model->updateTime);
        BIReport::getInstance()->reportOre($this->getUserId(), $oreType, $count, $balance, $timestamp, $biEvent);
        Log::info(sprintf('OreAddOk userId=%d count=%d balance=%d',
            $this->getUserId(), $count, $this->balanceOre($oreType,$timestamp)));
        return $balance;
    }

    public function consumeOre($oreType, $count, $timestamp, $biEvent) {
        assert($count >= 0);
//        $this->systemDecOre($timestamp, $biEvent);
        $balance = $this->model->getOre($oreType);
        if ($balance < $count) {
            throw new AssetNotEnoughException('矿石数量不足', 500);
        }
        if (!OreModelDao::getInstance()->decOre($this->getUserId(), $oreType, $count, $timestamp)) {
            Log::warning(sprintf('OreConsumeNotEnough userId=%d count=%d balance=%d',
                $this->getUserId(), $count, $this->balanceOre($oreType, $timestamp)));
            throw new AssetNotEnoughException('矿石数量不足', 500);
        }

        $balance -= $count;
        $this->model->setOre($oreType, $balance);
        $this->model->updateTime = $timestamp;
        BIReport::getInstance()->reportOre($this->getUserId(), $oreType, -$count, $balance, $timestamp, $biEvent);
        Log::info(sprintf('OreConsumeOk userId=%d count=%d balance=%d',
            $this->getUserId(), $count, $this->balanceOre($oreType, $timestamp)));
        return $balance;
    }

    /**
     * 系统清除矿石
     * @param $oreType
     * @param $timestamp
     * @param $biEvent
     */
    public function systemDecOre($timestamp, $biEvent) {
        if (!empty($this->oldOreMap)) {
            //todo ：待优化
            Log::info(sprintf('systemDecOre userId=%d oldOreMap=%s',
                $this->getUserId(), json_encode($this->oldOreMap)));
            $biEvent = BIReport::getInstance()->makeActivityExpiredBIEvent(0, "taojin", 0, 0);
            foreach ($this->oldOreMap as $k => $v) {
                $oldBalance = ArrayUtil::safeGet($this->oldOreMap, $k,0);
                if ($oldBalance > 0) {
                    $this->model->setOre($k, 0);
                    $this->model->updateTime = $timestamp;
                    OreModelDao::getInstance()->incOre($this->getUserId(), $k, -$oldBalance, $this->model->updateTime);
                    BIReport::getInstance()->reportOre($this->getUserId(), $k, -$oldBalance, 0, $timestamp, $biEvent);
                    Log::info(sprintf('OreDecOk userId=%d count=%d balance=%d',
                        $this->getUserId(), -$oldBalance, $this->balanceOre($k,$timestamp)));
                }
            }

            $this->model->oldOreMap = null;
        }
    }

    public function balanceOre($oreType, $timestamp) {
        return $this->model->getOre($oreType);
    }

    public function doLoad($timestamp) {
        $oldModel = OreModelDao::getInstance()->loadOre($this->getUserId());

        if ($oldModel == null) {
            $model = new OreModel();
            $model->updateTime = $timestamp;
            OreModelDao::getInstance()->saveOre($this->getUserId(), $model);
        }elseif(!TaojinSystem::getInstance()->getGameStatus($timestamp) || !TaojinSystem::getInstance()->getGameStatus($oldModel->updateTime)) {
            $model = new OreModel();
            $model->updateTime = $timestamp;
            $this->oldOreMap = $oldModel->oreMap;
        }else{
            $model = $oldModel;
        }
        $this->model = $model;
    }
}