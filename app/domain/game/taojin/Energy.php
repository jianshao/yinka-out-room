<?php


namespace app\domain\game\taojin;


use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\game\taojin\dao\EnergyDao;
use think\facade\Log;

class Energy
{
    private $user = null;
    public $count = 0;
    private $_isLoaded = false;

    public function __construct($user, $count=0) {
        $this->user = $user;
        $this->count = 0;
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
            Log::info(sprintf('EnergyLoaded userId=%d balance=%d', $this->getUserId(), $this->balance($timestamp)));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function add($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            $this->count += $count;
            EnergyDao::getInstance()->incEnergy($this->getUserId(), $count);

            BIReport::getInstance()->reportEnergy($this->getUserId(), $count, $this->count, $timestamp, $biEvent);

            Log::info(sprintf('EnergyAddOk userId=%d count=%d balance=%d',
                $this->getUserId(), $count, $this->balance($timestamp)));
        }
        return $this->balance($timestamp);
    }

    public function consume($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            if ($this->balance($timestamp) < $count) {
                throw new AssetNotEnoughException('体力不足', 500);
            }

            if (!EnergyDao::getInstance()->decEnergy($this->getUserId(), $count)) {
                Log::warning(sprintf('EnergyConsumeNotEnough userId=%d count=%d balance=%d',
                    $this->getUserId(), $count, $this->balance($timestamp)));
                throw new AssetNotEnoughException('体力不足', 500);
            }
            $this->count -= $count;

            BIReport::getInstance()->reportEnergy($this->getUserId(), -$count, $this->count, $timestamp, $biEvent);

            Log::info(sprintf('EnergyConsumeOk userId=%d count=%d balance=%d',
                $this->getUserId(), $count, $this->balance($timestamp)));

            // TODO event
        }
        return $this->balance($timestamp);
    }

    public function balance($timestamp) {
        return $this->count;
    }

    private function doLoad($timestamp) {
        $count = EnergyDao::getInstance()->loadEnergy($this->getUserId());
        if ($count === null) {
            throw new FQException('用户金币数据不存在', 500);
        }
        $this->count = $count;
    }
}