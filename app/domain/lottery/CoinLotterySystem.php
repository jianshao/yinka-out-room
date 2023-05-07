<?php

namespace app\domain\lottery;


use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use app\domain\Config;
use think\facade\Log;


/**
 * 金币抽奖
 */
class CoinLotterySystem
{
    protected static $instance;
    // 转盘列表
    private $lotterys = [];
    // 转盘列表
    private $lotteryMap = [];
    // 规则说明
    private $rules = null;
    // 抽奖配置
    private $lotteryPrices = null;
    private $lotteryTotalWeight = 0;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CoinLotterySystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    protected function loadFromJson() {
        $lotteryConfig = Config::getInstance()->getLotteryConf();
        $coinLotteryConfig = ArrayUtil::safeGet($lotteryConfig, 'coinLottery');

        foreach(ArrayUtil::safeGet($coinLotteryConfig, 'lotterys') as $lottery) {
            $model = new Lottery();
            $model->loadFromJson($lottery);
            if (ArrayUtil::safeGet($this->lotteryMap, $model->id) != null) {
                Log::warning(sprintf('LotterySystemLoadError id=%d err=%s',
                    $model->id, 'DuplicateLotteryId'));
            } else {
                $this->lotterys[] = $model;
                $this->lotteryMap[$model->id] = $model;
            }
        }

        if(ArrayUtil::safeGet($coinLotteryConfig, 'rules') != null) {
            $this->rules = $coinLotteryConfig['rules'];
        }

        foreach(ArrayUtil::safeGet($coinLotteryConfig, 'priceList', []) as $conf) {
            $model = new LotteryPrice();
            $model->loadFromJson($conf);
            if ($model->price == null || $model->price->count <= 0) {
                throw new FQException('抽奖配置错误', 500);
            }
            $this->lotteryPrices[] = $model;
        }

        $this->lotteryTotalWeight = 0;
        foreach ($this->lotterys as $lottery) {
            $this->lotteryTotalWeight += $lottery->weight;
        }
    }

    public function getLotterys(){
        return $this->lotterys;
    }

    public function getRules(){
        return $this->rules;
    }

    public function getLotteryPrices(){
        return $this->lotteryPrices;
    }

    public function getLotteryTotalWeight(){
        return $this->lotteryTotalWeight;
    }

    public function getPrice($num){
        foreach ($this->lotteryPrices as $model) {
            if ($model->num == $num) {
                return $model->price;
            }
        }
        return null;
    }
}

