<?php


namespace app\domain\imresource\service;


use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\prop\PropSystem;

class ImBubbleService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @desc 装扮消息气泡
     * @param $userId
     * @param $bubbleId
     */
    public function setImBubble($userId, $bubbleId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getImBubbleKey($userId);

        // 取消装扮
        if ($bubbleId == 0) {
            return $redis->del($redisKey);
        }

        $model = PropSystem::getInstance()->findPropKind($bubbleId);
        if ($model == null) {
            throw new FQException('当前资源不存在', 500);
        }

        // 设置气泡
        return $redis->set($redisKey, $bubbleId);
    }

    /**
     * @desc im气泡定义的redis key
     * @param $userId
     * @return string
     */
    public function getImBubbleKey($userId)
    {
        return sprintf('im_bubble_%s', $userId);
    }

    /**
     * @desc 获取用户使用的聊天气泡
     * @param $userId
     * @return false|mixed|string
     */
    public function getUseImBubble($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $backgroundKey = $this->getImBubbleKey($userId);

        return $redis->get($backgroundKey);
    }
}