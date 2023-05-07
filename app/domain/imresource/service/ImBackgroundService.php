<?php


namespace app\domain\imresource\service;


use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\imresource\ImBackgroundSystem;
use app\utils\CommonUtil;

class ImBackgroundService
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
     * @desc 获取Im背景列表
     * @return array
     */
    public function getImBackgroundList()
    {
        $imBackground = ImBackgroundSystem::getInstance();
        $imBackgroundList = [];
        foreach ($imBackground->map as $imResourceTypeBackground) {
            $imBackgroundList[] = $this->formatImBackground($imResourceTypeBackground);
        }
        return $imBackgroundList;
    }

    public function formatImBackground($imResourceTypeBackground)
    {
        return [
            'id' => $imResourceTypeBackground->id,
            'title' => $imResourceTypeBackground->title,
            'image' => CommonUtil::buildImageUrl($imResourceTypeBackground->image),
        ];
    }

    /**
     * @desc 装扮背景
     * @param $userId
     * @param $backgroundId
     * @param $toUserId
     */
    public function setImBackground($userId, $backgroundId, $toUserId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $backgroundKey = $this->getImBackgroundKey($userId);

        // 取消装扮
        if ($backgroundId == 0) {
            return $redis->hDel($backgroundKey, $toUserId);
        }

        $model = ImBackgroundSystem::getInstance()->findKind($backgroundId);
        if ($model == null) {
            throw new FQException('当前资源不存在', 500);
        }

        // 设置聊天背景
        return $redis->hset($backgroundKey, $toUserId, $backgroundId);
    }

    /**
     * @desc 删除背景
     * @param $userId
     */
    public function delImBackground($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $backgroundKey = $this->getImBackgroundKey($userId);

        return $redis->del($backgroundKey);
    }

    /**
     * @desc im背景定义的redis key
     * @param $userId
     * @return string
     */
    public function getImBackgroundKey($userId)
    {
        return sprintf('im_background_%s', $userId);
    }

    /**
     * @desc 获取用户使用的背景图
     * @param $userId
     * @param $toUserId
     * @return false|mixed|string
     */
    public function getUseImBackground($userId, $toUserId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $backgroundKey = $this->getImBackgroundKey($userId);

        return $redis->hget($backgroundKey, $toUserId);
    }
}