<?php


namespace app\domain\activity\yearTicket;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class YearTicketGuildDao
{
    protected static $instance;
    private $filterGuildKey = 'YearTicketServiceFilter_guild';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YearTicketGuildDao();
        }
        return self::$instance;
    }

    public function buildGuildKey($id)
    {
        return sprintf('YearTicketService_guild_rank:%s', $id);
    }

    public function storeGuildMap($addGuildMap, $localUpgradeData)
    {
        if (count($addGuildMap) === 0) {
            return;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $aid = $localUpgradeData['id'];
        $guildRankKey = $this->buildGuildKey($aid);
        if ($aid == 1) {
//            如果是第一阶段
            foreach ($addGuildMap as $id => $count) {
                if ($id) {
                    $redis->zIncrBy($guildRankKey, $count, $id);
                }
            }
        } else {
//            不是第一阶段
            $lastAid = $aid - 1;
            $lastGuildCacheKey = sprintf('year:ticket:guild:cache:%s', $lastAid);
            $lastUpgradeData = YearTicketService::getInstance()->getLevelUpgradeForAid($lastAid);

            if ($this->isFirstUpgradeForAid($lastAid) || $this->getLastScheduleCache($lastGuildCacheKey) == 0) {
                $guildModelSortList = YearTicketService::getInstance()->getGuildRankList($localUpgradeData);
                $userIds = [];
                foreach ($guildModelSortList as $itemModelData) {
                    $userIds[] = $itemModelData->guildId;
                }
                $redis->sAdd($lastGuildCacheKey, ...$userIds);
            }
            $userIds = $redis->sMembers($lastGuildCacheKey);
            foreach ($addGuildMap as $id => $count) {
                if (in_array($id, $userIds)) {
                    $redis->zIncrBy($guildRankKey, $count, $id);
                }
            }
        }
    }

    public function getLastScheduleCache($lastGuildCacheKey) {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->sCard($lastGuildCacheKey);
    }

    /**
     * @param $lastAid
     * @return bool
     */
    private function isFirstUpgradeForAid($lastAid)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $lastGuildCacheKey = sprintf('year:ticket:guild:cache:%s_filter', $lastAid);

        $incr = $redis->incr($lastGuildCacheKey);
        if ($incr === 1) {
            return true;
        }
        return false;
    }


    /**
     * @info 过滤已经关闭的房间不涨年度积分
     * @param $guildId
     * @return int 0正常，1已经关闭了
     */
    private function notEnableGuild($guildId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $result = $redis->hGet($this->filterGuildKey, $guildId);
        if (empty($result)) {
            return 0;
        }
        return (int)$result;
    }

    /**
     * @param $offset
     * @param $count
     * @return mixed
     * @throws FQException
     */
    public function getRankGuildList($offset, $count)
    {
        $dataModelMap = YearTicketGuildDao::getInstance()->loadAll();
        $localUpgradeModel=YearTicketGuildDao::getInstance()->sortModelAll($dataModelMap);
        if (empty($localUpgradeModel)){
            return [];
        }
        return array_slice($localUpgradeModel,$offset,$count);
    }

    public function getCurrentRank($currentData, $lastData) {
        $res = [];
        foreach ($currentData as $member => $score) {
            $itemData['id'] = $member ?? 0;
            $itemData['score'] = $score ?? 0;
            $res[] = $itemData;
        }
        $currentMembers = array_keys($res);
        $lastMembers = array_values($lastData);
        foreach ($lastMembers as $val) {
            if (in_array($val, $currentMembers)) {
                continue;
            } else {
                $itemData['id'] = $val ?? 0;
                $itemData['score'] = 0;
                $res[] = $itemData;
            }
        }
        return $res;
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
            $result[$oldValue['guildId']] = (int)$value;
        }
        return $result;
    }


    /**
     * @param $guildId
     * @return array
     */
    public function getGuildScoreRank($guildId, $hover)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $score = $redis->zScore($this->buildGuildKey($hover), $guildId);
        $rank = $redis->zRevRank($this->buildGuildKey($hover), $guildId);
        if ($rank != false) {
            $rank += 1;
        }else {
            $rank = 0;
        }
        return [$score, $rank];
    }

    public function getLastRankScore($score, $rank, $hover) {
        $redis = RedisCommon::getInstance()->getRedis();
        $guildKey = $this->buildGuildKey($hover);
        $count = $redis->zCard($guildKey);
        if ($hover == 1) {
            if ($rank <= 100) {
                if ($rank == 1) {
                    $lastGuild = $redis->zRevRange($guildKey, $rank+1, $rank+1);
                    if (!empty($lastGuild)) {
                        $lastScore = $redis->zScore($guildKey, $lastGuild[0]);
                        return $score - $lastScore;
                    } else {
                        return $score;
                    }
                } elseif ($rank == 0) {
                    if ($count < 100) {
                        $lastGuild = $redis->zRevRange($guildKey,$count -1,$count - 1);
                    }  else {
                        $lastGuild = $redis->zRevRange($guildKey,99,99);
                    }
                } else {
                    $lastGuild = $redis->zRevRange($guildKey, $rank - 1, $rank -1);
                }
            } else {
                if ($count < 100) {
                    $lastGuild = $redis->zRevRange($guildKey,$count -1,$count - 1);
                } else {
                    $lastGuild = $redis->zRevRange($guildKey,99,99);
                }
            }
        } else {
            if ($rank > 0) {
                if ($rank == 1) {
                    $lastGuild = $redis->zRevRange($guildKey, $rank+1, $rank+1);
                    if ($lastGuild) {
                        $lastScore = $redis->zScore($guildKey, $lastGuild);
                        return $score - $lastScore;
                    } else {
                        return $score;
                    }
                }
                $lastGuild = $redis->zRevRange($guildKey, $rank - 2, $rank - 2);
            } else {
                return 0;
            }
        }
        $lastScore = $redis->zScore($guildKey, $lastGuild);
        return $lastScore - $score;
    }


    /**
     * @throws FQException
     * @array [guildId : YearTicketGuildModel]
     */
    public function loadAll()
    {
        $levelDataAll = YearTicketService::getInstance()->getLevelUpgrade();
        if (empty($levelDataAll)) {
            throw new FQException("config error", 500);
        }
        $allDataMap=[];
        foreach ($levelDataAll as $value) {
            $aid = $value['id'] ?? 0;
//            $localUpgradeData = YearTicketService::getInstance()->getLevelUpgradeForAid($aid);
            $rankData = $this->loadLevelData($aid);
            foreach($rankData as $guildId=>$score){
                $guildModel=ArrayUtil::safeGet($allDataMap,$guildId);
                if ($guildModel===null){
                    $guildModel=new YearTicketGuildModel();
                    $guildModel->guildId=$guildId;
                    $allDataMap[$guildId]=$guildModel;
                }
                $guildModel->levelMap[$aid]=$score;
            }
        }
        return $allDataMap;
    }

    /**
     * @param $dataModelMap
     * @return mixed
     */
    public function sortModelAll($dataModelMap){
        usort($dataModelMap, function($a, $b) {
            $aScore=$a->totalScore();
            $bScore=$b->totalScore();
            if ($aScore < $bScore) {
                return 1;
            } else if ($aScore > $bScore) {
                return -1;
            }
            return 0;
        });
        return $dataModelMap;
    }




    /**
     * @param $aid
     * @param $number
     * @return array [<guilid :score>]
     */
    private function loadLevelData($aid)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $guildKey = $this->buildGuildKey($aid);
        $result = $redis->zRevRange($guildKey, 0, -1, true);
        return $result;
    }


}