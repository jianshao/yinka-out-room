<?php


namespace app\api\controller\v1\Activity;


use app\common\RedisCommon;
use app\domain\activity\luckStar\LuckStarService;
use think\facade\Log;
use \app\facade\RequestAes as Request;

class LuckStarController
{
    /**
     * 福星降临，瓜分音豆
     */
    public function luckStarPrayInfo() {
        $token = Request::param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $uid = $redis->get($token);
        if (!$uid) {
            return rjson([], 500, '用户信息错误');
        }
        $partitionTime = date('Y-m-d 00:00:00',strtotime('+1 day'));    //瓜分时间  明天0点
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        $is_end = false;
        if(time() > strtotime($luckStarConfig['end_time'])) {
            $is_end = true;
        }
        if(strtotime($partitionTime) >= strtotime($luckStarConfig['end_time'])) {
            $partitionTime = $luckStarConfig['last_partition_time'];         //最后一次瓜分时间
        }
        if(strtotime($partitionTime) < strtotime($luckStarConfig['first_partition_time'])) {
            $partitionTime = $luckStarConfig['first_partition_time'];         //第一次瓜分时间
        }
        $luckPartitionDate = json_decode($luckStarConfig['partition_date']);
        $today = date('Ymd');                               //当天partitionRankList
        $whatDay = array_search($today, $luckPartitionDate);
        $listUser = $redis->zRevRange('divide:beans:'.$today,0,9,true);
        $currentRankList = LuckStarService::getInstance()->dealCurrentRanking($listUser, $redis, $today, $uid); //当日实时排名数据
        $luckStarPool = $redis->get($luckStarConfig['pool_prefix'].$today);                      //获取当前奖池数值
        if(empty($luckStarPool)) {
            $luckStarPool = $luckStarConfig['init_pool_value'];
        }
        $partitionRankList = [];
        foreach ($luckPartitionDate as $k => $v) {
            $rankList = $redis->get($luckStarConfig['partition_rank_cache_prefix'].$v);
            if(!$rankList) {
                $rankList = $redis->ZREVRANGE($luckStarConfig['partition_rank_prefix'].$v, 0, -1, true);
                $rankList = LuckStarService::getInstance()->dealPartitionData($rankList);
                $redis->set($luckStarConfig['partition_rank_cache_prefix'].$v, json_encode($rankList));
            } else {
                $rankList = json_decode($rankList,true);
            }
            $partitionRankList[] = $rankList;
        }
        $config['activity_time'] = date("Y年m月d日", strtotime($luckStarConfig['start_time']))."-".date("Y年m月d日", strtotime($luckStarConfig['end_time']));
        $config['init_pool_value'] = $luckStarConfig['init_pool_value']/100;
        $config['partition_time'] = json_decode($luckStarConfig['partition_time']);
        $config['partition_rate'] = json_decode($luckStarConfig['partition_rate']);
        $config['rate'] = (int)$luckStarConfig['rate'];
        $ext['type'] = 'attire';
        $ext['attire_id'] = 0;
        $ext['attire_name'] = '';
        $ext['attire_image'] = '';
        $ext['attire_move_image'] = '';
        $ext['multiple'] = '';
        $result = [
            'is_end'         => $is_end,
            'partitionTime'     => $partitionTime,
            'newYearPool'       => floor($luckStarPool / $luckStarConfig['real_rate']),
            'currentRankList'   => $currentRankList,
            'partitionRankList' => $partitionRankList,
            'whatDay' => empty($whatDay) ? 0 : $whatDay,
            'config' => $config,
            'ext' => $ext,
            'getGiftStatus' => 1,
        ];
        return rjson($result,200,'返回成功');
    }
}