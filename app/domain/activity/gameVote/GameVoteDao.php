<?php


namespace app\domain\activity\gameVote;

use app\common\RedisCommon;
use think\facade\Log;

class GameVoteDao
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GameVoteDao();
        }
        return self::$instance;
    }

    /**
     * @demo halloween_user:1454733_date:20211026
     * @param $userId
     * @param $date
     * @return string
     */
    public function buildKey()
    {
        return sprintf("game_vote_list");
    }


    public function buildLockKey($userId, $timestamp)
    {
        return sprintf("game_vote_user:%s", $userId);
    }

    /**
     * @param $timestamp
     * @return array
     */
    public function loadData()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        return $redis->hGetAll($key);
    }


    /**
     * @param GameVoteUser $model
     * @param $timestamp
     */
    public function incrData($id, $number)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        $redis->hIncrBy($key, $id, $number);
        Log::debug(sprintf('GameVoteDao::incrData id=%d number=%d', $id, $number));
    }

}