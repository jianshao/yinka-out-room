<?php


namespace app\domain\activity\weekStar;


use app\domain\Config;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class ZhouXingSystem
{
    protected static $instance;

    // 周星礼物列表
    public $gifts = [];

    // 富豪榜 奖品
    public $richRewards = [];

    // 魅力榜奖品
    public $charmRewards = [];

    // 月分榜奖品
    public $honorRewards = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ZhouXingSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function getGiftNumber(){
        return count($this->gifts);
    }

    private function loadFromJson()
    {
        $weekStarConf = Config::getInstance()->getWeekStarConf();
        $this->gifts = ArrayUtil::safeGet($weekStarConf, 'gifts',[]);
        $this->richRewards = ArrayUtil::safeGet($weekStarConf, 'rich',[]);
        $this->charmRewards = ArrayUtil::safeGet($weekStarConf, 'charm',[]);
        $this->honorRewards = ArrayUtil::safeGet($weekStarConf, 'honor',[]);
    }

    public function getRewards($weekGiftInfo=[])
    {
        $rewards = ['rich'=>[],'charm'=>[],'honor'=>[]];
        if(!empty($this->richRewards)){
            foreach($this->richRewards['reward'] as $rewardInfo){
                $rewards['rich'][] = [
                    'name'  => $rewardInfo['name'],
                    'details' => $rewardInfo['unit'],
                    'image' => CommonUtil::buildImageUrl($rewardInfo['image'])
                ];
            }
        }
        if(!empty($this->charmRewards)){
            foreach($this->charmRewards['reward'] as $rewardInfo){
                $rewards['charm'][] = [
                    'name'  => $rewardInfo['name'],
                    'details' => $rewardInfo['unit'],
                    'image' => CommonUtil::buildImageUrl($rewardInfo['image'])
                ];
            }
            array_push($rewards['charm'],['name'=>'周星礼物冠名','details'=>'7天','image'=>!empty($weekGiftInfo)?$weekGiftInfo['image']:'']);
        }
        if(!empty($this->honorRewards)){
            foreach($this->honorRewards['reward'] as $rewardInfo){
                $rewards['honor'][] = [
                    'name'  => $rewardInfo['name'],
                    'details' => $rewardInfo['unit'],
                    'image' => CommonUtil::buildImageUrl($rewardInfo['image'])
                ];
            }
            array_push($rewards['honor'],['name'=>'专属AAABCD','details'=>'永久','image'=>CommonUtil::buildImageUrl('/useravatar/20210131/9ae032bf64a110c34b9b3bb0e9ccf038.png')]);
        }
        return $rewards;
    }
}