<?php


namespace app\domain\user\service;


use app\common\RedisCommon;

//访客服务
class VisitorService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VisitorService();
        }
        return self::$instance;
    }

    /**
     * @desc 设置隐身访问
     * @param $userId
     * @param $toUserid // 被特别关心的人
     * @param $type 1：隐身访问  2：取消隐身访问
     * @return bool
     */
    public function setHiddenVisitor($userId, $toUserid, $type)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenVisitorKey($userId);
        if ($type == 1) {
            $time = time();
            $redis->zAdd($redisKey, $time, $toUserid);
        } else if ($type == 2) {
            $redis->zRem($redisKey, $toUserid);
        }

        return true;
    }

    /**
     * @desc 我的隐身访问key
     * @param $userId
     * @return string
     */
    public function getHiddenVisitorKey($userId)
    {
        return sprintf('hidden_visitor_%s', $userId);
    }

    /**
     * @desc 取消用户的所有隐身访问
     * @param $userId
     */
    public function cancelHiddenVisitor($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenVisitorKey($userId);
        $redis->del($redisKey);
    }
}