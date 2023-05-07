<?php
/**
 * 定时任务
 * 每周更新周星
 */

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\activity\weekStar\ZhouXingService;
use app\domain\activity\weekStar\ZhouXingSystem;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

class SetGiftStartCommand extends Command
{

    protected function configure()
    {
        $this->setName('SetGiftStartCommand')->setDescription('SetGiftStartCommand');
    }

    public static $reward = [1=>'charm',2=>'wealth',3=>'month'];

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $giftId = ZhouXingService::getInstance()->getWeekGiftId();
        $this->weeks(time(),$giftId);
        $this->addGiftKey();
//        $giftId = ZhouXingService::getInstance()->getWeekGiftId(); //获取周星礼物
//        WeekStarService::getInstance()->saveGiftImg($giftId);
    }

    //获取指定时间的周一时间戳
    public function calcWeekStart($timestamp)
    {
        return strtotime('monday this week', $timestamp);
    }

    //排名积分
    public function calcIntegral($rank)
    {
        $rankIntegralMap = [9, 7, 6, 5, 4, 3, 3, 3, 3, 3];
        if ($rank >= 0 && $rank < count($rankIntegralMap)) {
            return $rankIntegralMap[$rank];
        }
        return 0;
    }

    /**
     * @周星榜
     * @dongbozhao
     * @2020-12-18
     * @key ：weeksStarUid2020-12-07
     * @key ：weeksStarTouid2020-12-07
     */
    public function weeks($time,$giftId)
    {
        echo 'gift=====>'.$giftId."\r\n";
        $yiZhou = 7 * 86400;
        $benZhouYi = $this->calcWeekStart($time); //获得本周一时间
        $shangZhouYi = $benZhouYi - $yiZhou;

        $kaiShi = date('Y-m-d 00:00:00', $shangZhouYi);
//        $jieShu = date('Y-m-d 00:00:00', $benZhouYi);

//        $where = [ //上周周星搜索条件
//            ['success_time', '>=', strtotime($kaiShi)],
//            ['success_time', '<', strtotime($jieShu)],
//            ['ext_1', '=', $giftId],
//            ['event_id', '=', 10002],
//        ];
//
//        $uidList = Db::table('zb_user_asset_log')
//            ->where($where)
//            ->field('uid,sum(abs(change_amount)) coin')
//            ->group('uid')
//            ->order('coin desc,uid desc')
//            ->limit(10)
//            ->select()
//            ->toArray(); //财富榜
//
//        $touidList = Db::table('zb_user_asset_log')
//            ->where($where)
//            ->field('touid uid,sum(abs(change_amount)) coin')
//            ->group('touid')
//            ->order('coin desc,uid desc')
//            ->limit(10)
//            ->select()
//            ->toArray(); //魅力榜

        //获取今天是今年的周几（用于周榜）
        $week = date('oW',$shangZhouYi);
        $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);

        /* 本周贡献前十 */
        $weekStarGxList = $redis->zRevRange(sprintf(ZhouXingService::$comtributeCachekey,$giftId,$week),0,9,true);
        $uidList = [];
        if (!empty($weekStarGxList)) {
            foreach ($weekStarGxList as $k => $v) {
                $uidList[] = [
                    'uid' => $k,
                    'coin' => $v
                ];
            }
        }
        /* 本周收益前十 */
        $weekStarSyList = $redis->zRevRange(sprintf(ZhouXingService::$incomeCachekey,$giftId,$week),0,9,true);
        $touidList = [];
        if (!empty($weekStarSyList)) {
            foreach ($weekStarSyList as $k => $v) {
                $touidList[] = [
                    'uid' => $k,
                    'coin' => $v
                ];
            }
        }

        $caifuKey = 'weekStarUid_' . $kaiShi; //财富榜key
        $weekKey = 'weekStarToUid_' . $kaiShi;
        $weekRankKey = 'weekStar_' . $kaiShi;
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->set($caifuKey, json_encode($uidList));
        $redis->set($weekKey, json_encode($touidList));
        foreach ($touidList as $index => $data) {
            $integral = $this->calcIntegral($index);
            $uid = $data['uid'];
            $redis->zAdd($weekRankKey, $integral, $uid);
        }
        $type = [1=>'万千宠爱', 2=>'君临天下', 3=>'荣誉星耀', 4=>'周星礼物'];
        $sendRewardArray = [];
        if( !empty($uidList) ){
            $sendRewardArray += ['wealth' => reset($uidList)['uid'] ];
        }
        if( !empty($touidList) ){
            $sendRewardArray += ['charm' => reset($touidList)['uid'] ];
        }
        echo 'charm=====>'.reset($uidList)['uid']."\r\n";
        echo 'wealth=====>'.reset($touidList)['uid']."\r\n";
        $this->sendRewardMonth(strtotime($kaiShi),$sendRewardArray);
    }

    public function addGiftKey(){
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $key = $redis->get('frequency');
        $redis->set('frequency',$key + 1);
    }

    public function sendRewardMonth($curTime,$sendRewardArray){
        $startingTime = '2021-02-01 00:00:00';
        $startingTime = strtotime($startingTime);
        $weekKeys = ZhouXingService::getInstance()->calcWeekKeys($curTime, $startingTime);
        $curTimeDate = date('Y-m-d 00:00:00',$curTime);
        if( in_array($curTimeDate,$weekKeys) && $curTimeDate == end($weekKeys) ){
            $monthlyList = ZhouXingService::getInstance()->monthRank($curTime, $startingTime);
            if( !empty($monthlyList) ){
                echo 'month=====>'.reset($monthlyList)['uid']."\r\n";
                $sendRewardArray += ['month' => reset($monthlyList)['uid'] ];
            }
        }
        foreach ($sendRewardArray as $k => $v){
            $this->sendRewardWeeks([$k=>$v]);
        }

    }

    public function sendRewardWeeks($array){
        print_r($array)."\r\n";
        if(!empty($array[self::$reward[1]])){
            $this->reward($array[self::$reward[1]],1);
        }
        if(!empty($array[self::$reward[2]])){
            $this->reward($array[self::$reward[2]],2);
        }
        if(!empty($array[self::$reward[3]])){
            $this->reward($array[self::$reward[3]],3);
        }
    }

    public function reward($uid,$type){
        //1:万千宠爱 2:君临天下 3:荣誉星耀
        if($type == 1){
            $charmRewards = ZhouXingSystem::getInstance()->charmRewards;
            if(!empty($charmRewards)){
                foreach($charmRewards['reward'] as $reward){
                    if(strpos($reward['assetId'],'gift') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'万千宠爱系统发放礼物');
                    }else if(strpos($reward['assetId'],'prop') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'','万千宠爱系统发放道具');
                    }
                }
            }
        }elseif($type == 2){
            $richRewards = ZhouXingSystem::getInstance()->richRewards;
            if(!empty($richRewards)){
                foreach($richRewards['reward'] as $reward){
                    if(strpos($reward['assetId'],'gift') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'君临天下系统发放礼物');
                    }else if(strpos($reward['assetId'],'prop') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'','君临天下系统发放道具');
                    }
                }
            }
        }elseif($type == 3){
            $honorRewards = ZhouXingSystem::getInstance()->honorRewards;
            if(!empty($honorRewards)){
                foreach($honorRewards['reward'] as $reward){
                    if(strpos($reward['assetId'],'gift') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'荣誉星耀系统发放礼物');
                    }else if(strpos($reward['assetId'],'prop') !== false){
                        $this->inner($uid,$reward['assetId'],$reward['count'],'','荣誉星耀系统发放道具');
                    }
                }
            }
        }
    }

    /**
     * @param $uid
     * @param $assetId
     * @param $change
     * @param $operatorId
     * userId uid
     * assetId 豆： user:bean 钻石： user:diamond 金币： user:coin 礼物：gift:id 装扮：prop:id
     * change num
     * operatorId 管理员id
     * token 管理员token
     * activity week_star
     */
    public function inner($uid, $assetId, $change, $reason = '',$activity='week_star')
    {
        $data = [
            'userId' => $uid,
            'assetId' => $assetId,
            'change' => $change,
            'operatorId' => '',
            'reason' => $reason,
            'token' => '',
            'activity' => $activity,
        ];
        print_r($data)."\r\n";
        $socket_url = config('config.app_api_url') . 'api/inner/adjustUserAsset';
        curlData($socket_url, $data);
    }

}