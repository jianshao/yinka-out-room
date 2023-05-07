<?php


namespace app\domain\activity\zhongqiuPK;


class ZhongQiuPKSystem
{
    protected static $instance;
    # 蛋黄帮派
    public static $eggFaction = 'eggFaction';
    # 伍仁帮派
    public static $wuRenFaction = 'wuRenFaction';
    public $startTime = 0;
    public $stopTime = 0;
    // 奖池的礼物
    public $giftIds = [];
    // 礼物的百分之二进奖池
    public $rate = 0;
    // 奖池初始值
    public $basePool = 0;
    // 瓜分奖池的比例
    public $poolRates = [];
    // 补签的豆
    public $retroactive = 0;
    // 签到7天的奖励
    public $checkinReward = 0;
    // 签到信息
    public $checkins = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ZhongQiuPKSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function getCheckInReward($day){
        return $this->checkins[intval($day)-1];
    }

    private function loadFromJson() {
        $conf = Config::loadConf();

        $this->startTime = $conf['startTime'];
        $this->stopTime = $conf['stopTime'];
        $this->giftIds = $conf['giftIds'];
        $this->rate = $conf['rate'];
        $this->basePool = $conf['basePool'];
        $this->poolRates = $conf['poolRates'];
        $this->retroactive = $conf['retroactive'];
        $this->checkins = $conf['checkins'];
        $this->checkinReward = $conf['checkinReward'];
    }
}