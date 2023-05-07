<?php


namespace app\domain\activity\luckStar;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\bi\BIReport;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\utils\CommonUtil;
use think\facade\Log;

class LuckStarService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LuckStarService();
        }
        return self::$instance;
    }
    /**
     * @param $listUser
     * @param $redis
     * @param $today
     * @param $uid
     * @return array
     */
    public function dealCurrentRanking($listUser, $redis, $today, $uid) {
        if (!empty($listUser)) {
            $uids = array_keys($listUser);
            $userModels = UserModelDao::getInstance()->findUserModelsByUserIds($uids);
            if ($userModels) {
                foreach ($userModels as $userModel) {
                    $userResTmp[$userModel->userId] = [
                        'id' => $userModel->userId,
                        'nickname' => $userModel->nickname,
                        'pretty_id' => $userModel->prettyId,
                        'username' => $userModel->username,
                        'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                        'rank' => $listUser[$userModel->userId],
                    ];
                }
                for ($i=0; $i < count($uids); $i++) {
                    @$userData[] = $userResTmp[$uids[$i]];
                }
            }
            //判断自己上一名
            $rankNum = '未上榜';
            $myrankNum = $redis->ZREVRANK('rank_box_fuxing_'.$today,$uid);
            if ($myrankNum === false) {
                @$myRankTmp = $redis->ZREVRANGE('rank_box_fuxing_'.$today,-1,-1,true);
                @$numTmp = array_values($myRankTmp);
                @$myRank = $numTmp[0];
            }else{
                $rankNum = $myrankNum+1;
                if ($myrankNum != 0) {
                    @$myRankTmp = $redis->ZREVRANGE('rank_box_fuxing_'.$today,$myrankNum-1,$myrankNum,true);
                    @$numTmp = array_values($myRankTmp);
                    @$myRank = $numTmp[0] - $numTmp[1];
                }else {
                    $myRank = $redis->zScore('rank_box_fuxing_'.$today, $uid);
                }
            }
            $listCount = count($userData);
            for($i = 0; $i < $listCount; $i++) {
                if($i <= 2) {
                    if ($i == 0) {
                        @$userData[$i]['headFrame'] = getavatar('/banner/20200618/4396d4185f2d7ca9faee46f0afaa3bcc.png');
                    }elseif($i == 1){
                        @$userData[$i]['headFrame'] = getavatar('/banner/20200618/04f441a7053554b0a272441b83161158.png');
                    }else{
                        @$userData[$i]['headFrame'] = getavatar('/banner/20200618/4ecbc2a980d547653253a66954b8c327.png');
                    }
                } else {
                    @$userData[$i]['headFrame'] = '';
                }
            }
            return ['list' => $userData];
        } else {
            return ['list' => []];
        }
    }

    /**
     * 处理瓜分榜数据
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function dealPartitionData($data) {
        $res = [];
        if(!empty($data)) {
            foreach($data as $k=>$v) {
                $userInfo = UserModelDao::getInstance()->loadUserModel($k);
                $userData['id'] = $k;
                $userData['nickname'] = $userInfo->nickname;
                $userData['avatar'] = CommonUtil::buildImageUrl($userInfo->avatar);
                $userData['score'] = $v;
                $res[] = $userData;
            }
        } else {
            $res = [];
        }
        return $res;
    }

    /**
     * 福星降临瓜分番茄豆
     * @param $type
     * @param $num
     * @param $luckStarConfig
     * @return bool
     */
    public function luckyStarComes($activeName,$type, $num, $luckStarConfig) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = date('Ymd');
        $unitPrice=$this->ActiveNameToUnitPrice($activeName,$type);
        $allCountPrice = $unitPrice * $num * $luckStarConfig['rate'];     //总价值
        //奖池内番茄豆增加砸蛋价值1%
        $luckStarPool = $redis->get($luckStarConfig['pool_prefix'].$date);      //获取当前奖池数值
        if(!$luckStarPool) {
            $luckStarPool = $luckStarConfig['init_pool_value'];     //是正常值的100倍
            $redis->set($luckStarConfig['pool_prefix'].$date, $luckStarPool);    //初始值为100000；
        }
        Log::record('LuckStar奖池更改前-----'.$luckStarPool);
        $luck_star_pool_value = $redis->incr($luckStarConfig['pool_prefix'].$date, $allCountPrice);
        Log::record('LuckStar奖池增加后-----'.$luck_star_pool_value);
        return true;
    }

    public function luckyStarRankList($activeName, $type, $userId, $num, $luckStarConfig) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = date('Ymd');
        $unitPrice=$this->ActiveNameToUnitPrice($activeName,$type);
        $allCountPrice = $unitPrice * $num * $luckStarConfig['rate'];     //总价值
    }

    /**
     * @param $activeName
     * @param $type
     * @return int
     */
    private function ActiveNameToUnitPrice($activeName, $type)
    {
        $unitPrice = 0;
        if ($activeName === "breakBox") {
            if ($type == 1) {
                $unitPrice = 20;
            }
            if ($type == 2) {
                $unitPrice = 100;
            }
            if ($type == 3) {
                $unitPrice = 600;
            }
        }

        if ($activeName === "turntable") {
            if ($type == 1) {
                $unitPrice = 300;
            }
            if ($type == 2) {
                $unitPrice = 1000;
            }
        }
        return $unitPrice;
    }



    /**
     * 福星降临瓜分番茄豆
     * @param $type
     * @param $num
     * @param $luckStarConfig
     * @return bool
     */
    public function luckyStarComesBackup($type, $num, $luckStarConfig) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = date('Ymd');
        $unitPrice = 0;
        if ($type == 1) {
            $unitPrice = 20;
        }
        if ($type == 2) {
            $unitPrice = 100;
        }
        if ($type == 3) {
            $unitPrice = 600;
        }
        $allCountPrice = $unitPrice * $num * $luckStarConfig['rate'];     //总价值

        //奖池内番茄豆增加砸蛋价值1%
        $luckStarPool = $redis->get($luckStarConfig['pool_prefix'].$date);      //获取当前奖池数值
        if(!$luckStarPool) {
            $luckStarPool = $luckStarConfig['init_pool_value'];     //是正常值的100倍
            $redis->set($luckStarConfig['pool_prefix'].$date, $luckStarPool);    //初始值为100000；
        }
        Log::record('LuckStar奖池更改前-----'.$luckStarPool);
        $luck_star_pool_value = $redis->incr($luckStarConfig['pool_prefix'].$date, $allCountPrice);
        Log::record('LuckStar奖池增加后-----'.$luck_star_pool_value);
        return true;
    }
    /**
     * 瓜分任务
     */
    public function luckStarPartition() {
        $redis = RedisCommon::getInstance()->getRedis();
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        Log::record('luck_star_config---'.json_encode($luckStarConfig));
        $time = time();
        if ($time >= strtotime($luckStarConfig['first_partition_time']) && $time < strtotime($luckStarConfig['last_partition_time']) + 5) {
            $partitionTime = date('Ymd', strtotime('-1 day'));    //获取昨天的排名数据
            $listUser = $redis->zRevRange('divide:beans:' . $partitionTime, 0, 9, true);
            $luckStarPool = $redis->get($luckStarConfig['pool_prefix'] . $partitionTime);
            $luckStarPool = floor($luckStarPool / $luckStarConfig['real_rate']);
            if (!empty($listUser)) {
                $allUid = array_keys($listUser);
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'luck_star', $partitionTime, 1);
                foreach ($allUid as $k => $v) {
                    $addCoin = $this->partitionAction($k, $luckStarPool, $luckStarConfig);
                    if($redis->zScore($luckStarConfig['partition_rank_prefix'] . $partitionTime, $v) === false ) {
                        try {
                            Sharding::getInstance()->getConnectModel('userMaster', $v)->transaction(function() use($v, $time, $addCoin, $biEvent, $redis, $luckStarConfig, $partitionTime) {
                                $user = UserRepository::getInstance()->loadUser($v);
                                $bean = $user->getAssets()->getBean($time);
                                $bean->add($addCoin, $time, $biEvent);
                                $redis->zAdd($luckStarConfig['partition_rank_prefix'] . $partitionTime, $addCoin, $v);
                            });
                        } catch (\Exception $e) {
                            Log::record("code ---".$e->getCode(), "message---".$e->getMessage(), "trace---".$e->getTraceAsString());
                        }
                    }
                }
            }
            $rankList = $redis->ZREVRANGE($luckStarConfig['partition_rank_prefix'] . $partitionTime, 0, -1, true);
            $rankList = $this->dealPartitionData($rankList);
            $redis->set($luckStarConfig['partition_rank_cache_prefix'] . $partitionTime, json_encode($rankList));
        } else {
            Log::record('瓜分活动已结束---'.date('Y-m-d H:i:s'));
        }
    }

    private function partitionAction($key, $luckStarPool, $luckStarConfig) {
        $partitionRate = json_decode($luckStarConfig['partition_rate']);
        return floor($luckStarPool * $partitionRate[$key]);
    }
}