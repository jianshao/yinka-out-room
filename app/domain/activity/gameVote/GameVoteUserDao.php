<?php


namespace app\domain\activity\gameVote;

use app\common\RedisCommon;
use think\facade\Log;

class GameVoteUserDao
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GameVoteUserDao();
        }
        return self::$instance;
    }

    /**
     * @demo halloween_user:1454733_date:20211026
     * @param $userId
     * @param $date
     * @return string
     */
    public function buildKey($userId, $timestamp)
    {
        return sprintf("game_vote_user:%s", $userId);
    }


    public function buildLockKey($userId, $timestamp)
    {
        return sprintf("game_vote_user:%s", $userId);
    }

    /**
     * @param $userId
     * @param $timestamp
     * @return GameVoteUser|null
     */
    public function loadUser($userId, $timestamp)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId, $timestamp);
        $jstr = $redis->hGetAll($key);
        $ret = null;
        if (!empty($jstr)) {
            $ret = new GameVoteUser($userId, $timestamp);
            $ret->fromJson($jstr);
        }
        if ($ret === null) {
            $ret = new GameVoteUser($userId, $timestamp);
        }
        Log::debug(sprintf('GameVoteUserDao::loadUser userId=%d data=%s ret=%s',
            $userId, json_encode($jstr), json_encode($ret->toJson())));
        return $ret;
    }


    /**
     * @param GameVoteUser $model
     * @param $timestamp
     */
    public function saveUser(GameVoteUser $model, $timestamp)
    {
        $jstr = $model->toJson();
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($model->userId, $timestamp);
        foreach ($jstr as $field => $value) {
            $redis->hSet($key, $field, $value);
        }
        Log::debug(sprintf('GameVoteUserDao::saveUser userId=%d data=%s',
            $model->userId, json_encode($jstr)));
    }

}