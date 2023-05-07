<?php


namespace app\domain\activity\guoqing;


use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class GuoQingSystem
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
    // 发动态奖励
    public $forumReward = 0;
    // boxs
    public $boxs = [];
    public $boxMap = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GuoQingSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function findBox($boxId){
        return ArrayUtil::safeGet($this->boxMap, $boxId);
    }

    private function loadFromJson() {
        $conf = Config::loadConf();

        $this->startTime = $conf['startTime'];
        $this->stopTime = $conf['stopTime'];
        $this->giftIds = $conf['giftIds'];
        $this->rate = $conf['rate'];
        $this->basePool = $conf['basePool'];
        $this->poolRates = $conf['poolRates'];
        $this->forumReward = $conf['forumReward'];

        $boxs = [];
        $boxMap = [];
        $boxsConf = $conf['boxs'];
        foreach ($boxsConf as $boxConf) {
            $box = new GuoQingBox();
            $box->fromJson($boxConf);
            if (array_key_exists($box->boxId, $boxMap)) {
                throw new FQException('配置错误', 500);
            }
            $boxMap[$box->boxId] = $box;
            $boxs[] = $box;
        }

        $this->boxMap = $boxMap;
        $this->boxs = $boxs;
    }
}