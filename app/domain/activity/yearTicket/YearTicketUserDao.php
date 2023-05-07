<?php

namespace app\domain\activity\yearTicket;

use app\common\RedisCommon;
use app\domain\queue\job\Redis;

class YearTicketUserDao
{
    protected static $instance;
    private $filterUserKey = 'YearTicketServiceFilter_user';


    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YearTicketUserDao();
        }
        return self::$instance;
    }


    private function getStrDate()
    {
        return date("Ymd");
    }

    public function buildUserKey()
    {
        return sprintf('YearTicketService_user_rank');
    }


    /**
     * @info 过滤已经关闭的房间不涨年度积分
     * @param $userId
     * @return int  0正常，1已经关闭了
     */
    private function notEnableUser($userId, $aid)
    {
        $redis = RedisCommon::getInstance()->getRedis();
//        $result = $redis->hGet($this->filterUserKey, $userId);
        $result = $redis->zScore($this->buildUserKey($aid), $userId);
        if (empty($result)) {
            return 0;
        }
        return (int)$result;
    }


    /**
     * @param $addUserMap
     */
    public function storeUserMap($addUserMap, $localUpgradeData)
    {
        if (count($addUserMap) === 0) {
            return;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userRankKey = $this->buildUserKey();
        foreach ($addUserMap as $id => $count) {
            $redis->zIncrBy($userRankKey, $count, $id);
        }
    }



    /**
     * @param $offset
     * @param $count
     * @return array
     */
    public function getRankUserList()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $userKey = $this->buildUserKey();
        $userData = $redis->zRevRange($userKey, 0, 49, true);
        $resetData = [];
        foreach ($userData as $gk => $gv) {
            $itemData['id'] = $gk ?? 0;
            $itemData['score'] = $gv ?? 0;
            $resetData[] = $itemData;
        }
        return $this->getShowRank($resetData);
    }

    public function getShowRank($resetData) {
        $countResetData = count($resetData);
        $result = [];
        foreach ($resetData as $key => $oldValue) {
            if ($countResetData == 1) {
                $value = $oldValue['score'];
            } else {
                if ($key === 0) {
                    $nextValue = $resetData[1]['score'];
                    $value = $oldValue['score'] - $nextValue;
                } else {
                    $nextValue = $resetData[$key - 1]['score'];
                    $value = $nextValue - $oldValue['score'];
                }
            }
            $result[$oldValue['id']] = (int)$value;
        }
        return $result;
    }


    /**
     * @param $guildId
     * @return array
     */
    public function getUserScoreRank($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $userKey = $this->buildUserKey();
        $score = $redis->zScore($userKey, $userId);
        $rank = $redis->zRevRank($userKey, $userId);
        if ($rank != false) {
            $rank += 1;
        } else {
            $rank = 0;
        }
        return [$score, $rank];
    }

    public function getLastRankScore($score, $rank) {
        $redis = RedisCommon::getInstance()->getRedis();
        $userKey = $this->buildUserKey();
        $count = $redis->zCard($userKey);
        if ($rank <= 50) {
            if ($rank == 1) {
                $lastUser = $redis->zRevRange($userKey, $rank + 1, $rank + 1);
                if (!empty($lastUser)) {
                    $lastScore = $redis->zScore($userKey, $lastUser[0]);
                    return $score - $lastScore;
                } else {
                    return $score;
                }
            } elseif($rank == 0){
                if ($count < 50) {
                    $lastUser = $redis->zRevRange($userKey,$count -1,$count - 1);
                } else {
                    $lastUser = $redis->zRevRange($userKey,49,49);
                }
            } else {
                $lastUser = $redis->zRevRange($userKey, $rank - 2, $rank -2);
            }
        } else {
            if ($count < 50) {
                $lastUser = $redis->zRevRange($userKey,$count -1,$count - 1);
            } else {
                $lastUser = $redis->zRevRange($userKey,49,49);
            }
        }
        $lastScore = $redis->zScore($userKey, $lastUser[0]);
        return $lastScore - $score;
    }


}