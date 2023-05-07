<?php


namespace app\domain\activity\zhuawawa;


use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class ZhuawawaSystem
{
    protected static $instance;

    public $startTime = 0;
    public $stopTime = 0;
    // 奖池的礼物
    public $props = [];
    // 签到信息
    public $checkins = [];
    // 奖池数据
    public $rewardPool = [];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhuawawaSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function getReward($level)
    {
        foreach ($this->checkins as $item) {
            if ($level === $item['level']) {
                return $item;
            }
        }
        return [];
    }

    private function loadFromJson()
    {
        $conf = Config::loadConf();

        $this->startTime = $conf['startTime'];
        $this->stopTime = $conf['stopTime'];
        $this->props = $conf['props'];
        $this->rewardPool = $conf['rewardPool'];
    }

    public function getRewardPool()
    {
        if (empty($this->rewardPool)) {
            throw new FQException('ZhuawawaSystem rewardPool error');
        }
        return $this->rewardPool;
    }


    /**
     * @param $id
     * @return array|mixed
     * @throws FQException
     */
    public function getPropForId($id)
    {
        if (empty($this->props)) {
            throw new FQException('ZhuawawaSystem rewardPool error');
        }
        foreach ($this->props as $prop) {
            if ($prop['id'] === $id) {
                return $prop;
            }
        }
        return [];
    }


    /**
     * @return array
     * @throws FQException
     */
    public function loadPropData(){
        $result=[];
        $rewardPoolData=$this->getRewardPool();
        $giftData=ArrayUtil::safeGet($rewardPoolData,"gifts",[]);
        if (empty($giftData)){
            return $result;
        }
        $result=[];
        foreach ( $giftData as list($id,$_)){
            if (empty($id)){
                continue;
            }
            $result[]=$this->getPropForId($id);
        }
        return $result;
    }
}