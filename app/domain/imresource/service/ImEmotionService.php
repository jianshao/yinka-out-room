<?php


namespace app\domain\imresource\service;


use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\imresource\ImEmotionSystem;

class ImEmotionService
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
    public function getImEmotionList($userId)
    {
        $imEmotion = ImEmotionSystem::getInstance();
        $useEmotionIds = $this->getUseImEmotionIds($userId);
        $imEmotionList = [];
        foreach ($imEmotion->map as $imResourceTypeEmotion) {
            if (!empty($useEmotionIds) && in_array($imResourceTypeEmotion->id, $useEmotionIds)) {
                $imResourceTypeEmotion->status = 1;
            }
            $imEmotionList[] = $this->formatImEmotion($imResourceTypeEmotion);
        }
        return $imEmotionList;
    }

    public function formatImEmotion($imResourceTypeEmotion)
    {
        $imEmotionDesc = [];
        foreach ($imResourceTypeEmotion->emotionList as $imResourceTypeEmotionDesc) {
            $imEmotionDesc[] = [
                'emotion_id' => $imResourceTypeEmotionDesc->emotionId,
                'emotion_name' => $imResourceTypeEmotionDesc->emotionName,
                'emotion_url' => $imResourceTypeEmotionDesc->emotionUrl,
                'width' => $imResourceTypeEmotionDesc->width,
                'height' => $imResourceTypeEmotionDesc->height,
            ];
        }
        return [
            'id' => $imResourceTypeEmotion->id,
            'title' => $imResourceTypeEmotion->title,
            'status' => $imResourceTypeEmotion->status,
            'emotion_list' => $imEmotionDesc,
        ];
    }

    /**
     * @desc 装扮消息气泡
     * @param $userId
     * @param $action // add remove remove_all
     * @param $emotionId
     */
    public function setImEmotion($userId, $action, $emotionId = 0)
    {
        if (!in_array($action, ['add', 'remove', 'remove_all'])) {
            throw new FQException('参数错误', 500);
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getImEmotionKey($userId);

        // 删除全部表情包
        if ($action == 'remove_all') {
            return $redis->del($redisKey);
        }

        // 查询当前用户表情包
        $model = ImEmotionSystem::getInstance()->findKind($emotionId);
        if ($model == null) {
            throw new FQException('当前资源不存在', 500);
        }

        // 添加表情包
        if ($action == 'add') {
            $emotionCount = $redis->hLen($redisKey);
            if ($emotionCount && $emotionCount >= 10) {
                throw new FQException('添加已达到上限', 500);
            }
            $time = time();
            $emotionInfo = $this->formatImEmotionRedis($emotionId, $time);
            return $redis->hSet($redisKey, $emotionId, json_encode($emotionInfo));
        }
        // 删除表情包
        if ($action == 'remove') {
            return $redis->hDel($redisKey, $emotionId);
        }
    }

    /**
     * @desc 定义存储到redis的结构
     * @param $emotionId
     * @param $timestamp
     * @return array
     */
    public function formatImEmotionRedis($emotionId, $timestamp)
    {
        return [
            'emotion_group_id' => $emotionId,
            'add_time' => $timestamp,
        ];
    }

    /**
     * @desc im气泡定义的redis key
     * @param $userId
     * @return string
     */
    public function getImEmotionKey($userId)
    {
        return sprintf('im_emotion_%s', $userId);
    }

    /**
     * @desc 获取用户使用的表情包
     * @param $userId
     * @return false|mixed|string
     */
    public function getUseImEmotion($userId)
    {
        $emotions = [];
        $useEmotionIds = $this->getUseImEmotionIds($userId);
        if (!empty($useEmotionIds)) {
            foreach ($useEmotionIds as $id) {
                $imResourceTypeEmotion = ImEmotionSystem::getInstance()->findKind($id);
                if ($imResourceTypeEmotion){
                    $imResourceTypeEmotion->status = 1;
                    $emotions[] = $this->formatImEmotion($imResourceTypeEmotion);
                }
            }
        }
        return $emotions;
    }

    /**
     * @desc 获取当前用户表情包ids
     * @param $userId
     * @return array
     */
    public function getUseImEmotionIds($userId)
    {
        $useEmotionIds = [];
        $redis = RedisCommon::getInstance()->getRedis();
        $emotionKey = $this->getImEmotionKey($userId);
        $emotionList = $redis->hGetAll($emotionKey);
        if ($emotionList){
            $useEmotionIds = array_keys($emotionList);
        }
        return $useEmotionIds;
    }
}