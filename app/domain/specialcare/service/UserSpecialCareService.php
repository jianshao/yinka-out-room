<?php


namespace app\domain\specialcare\service;

use app\common\GetuiCommon;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\specialcare\queue\AmpQueue;
use app\domain\user\service\UserInfoService;
use app\query\user\QueryUserService;
use app\query\user\service\AttentionService;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc 特别关心
 * Class UserSpecialCareService
 * @package app\domain\specialcare\service
 */
class UserSpecialCareService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserSpecialCareService();
        }
        return self::$instance;
    }

    /**
     * @desc 设置特别关心
     * @param $userId
     * @param $toUserid // 被特别关心的人
     * @param $type 1：添加  2：取消
     */
    public function setSpecialCare($userId, $toUserid, $type)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getSpecialCareKey($userId);
        $redisQuiltKey = $this->getQuiltSpecialCareKey($toUserid);
        if ($type == 1) {
            $time = time();
            $redis->zAdd($redisKey, $time, $toUserid);
            $redis->zAdd($redisQuiltKey, $time, $userId);
        } else if ($type == 2) {
            $redis->zRem($redisKey, $toUserid);
            $redis->zRem($redisQuiltKey, $userId);
        }
        return true;
    }

    /**
     * @desc 取消用户的所有特别关心
     * @param $userId
     */
    public function cancelSpecialCare($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getSpecialCareKey($userId);
        $specialCare = $redis->ZRANGE($redisKey, 0, -1);
        if (!empty($specialCare)) {
            // 删除被特别关系列表
            foreach ($specialCare as $toUserid) {
                $redisQuiltKey = $this->getQuiltSpecialCareKey($toUserid);
                $redis->zRem($redisQuiltKey, $toUserid, $userId);
            }
            // 删除我的特别关心
            $redis->del($redisKey);
        }
    }

    /**
     * @desc 我的特别关心key
     * @param $userId
     * @return string
     */
    public function getSpecialCareKey($userId)
    {
        return sprintf('special_care_my_%s', $userId);
    }

    /**
     * @desc 谁对我设置了特别关注key
     * @param $userId
     * @return string
     */
    public function getQuiltSpecialCareKey($userId)
    {
        return sprintf('special_care_passive_%s', $userId);
    }

    /**
     * @desc 是否是特别关注
     * @param $userId
     * @param $toUserid
     */
    public function isSpecialCare($userId, $toUserid)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getSpecialCareKey($userId);
        $score = $redis->zScore($redisKey, $toUserid);
        return (bool)$score;
    }

    /**
     * @desc 获取特别关心列表
     * @param $userId
     * @param $page
     * @param $pageNum
     * @param $isPage // 是否分页，不分页返回全部数据
     * @return array
     */
    public function getSpecialCareList($userId, $page, $pageNum, $isPage)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getSpecialCareKey($userId);

        $limitStart = 0;
        $limitEnd = -1;
        if ($isPage) {
            $limitStart = ($page - 1) * $pageNum;
            $limitEnd = ($limitStart + $pageNum) - 1;
        }
        $count = $redis->zCard($redisKey); //统计ScoreSet总数
        // 分页读取关注列表
        $specialCare = $redis->ZRANGE($redisKey, $limitStart, $limitEnd, true);
        $specialCareList = $this->formatSpecialCare($specialCare);

        return [$count, $specialCareList];
    }

    /**
     * @desc 定义存储到redis的结构
     * @param $specialCare
     * @return array
     */
    public function formatSpecialCare($specialCare)
    {
        $specialCareList = [];
        foreach ($specialCare as $userId => $careTime) {
            $specialCareList[] = [
                'user_id' => $userId,
                'care_time' => ceil($careTime),
            ];
        }
        return $specialCareList;
    }

    /**
     * @desc 特别关心消息队列
     * @param $userId
     * @param $type
     * @param $pushTime
     * @return bool
     * @throws \Exception
     */
    public function createMessageQueue($userId, $type, $pushTime)
    {
        $messageData = [
            'user_id' => $userId,
            'type' => $type,
            'push_time' => $pushTime,
        ];
        $strData = json_encode($messageData);
        Log::info(sprintf("UserSpecialCareService entry createQueue:%s", $strData));
        return AmpQueue::getInstance()->publisher($strData);
    }

    /**
     * @info 过滤重复提交的数据
     * @param $msgBody
     * @param $count
     * @throws FQException
     */
    private function filterComsumerReload($msgBody, $count, $ttl)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $filterKey = md5($msgBody);
        if ($redis->incr($filterKey) > $count) {
            throw new FQException(sprintf("fatal entry msg more times key=%s", $filterKey), 513);
        }
        $redis->expire($filterKey, $ttl);
    }

    /**
     * @desc 消费消息
     * @param $msg
     */
    public function pushConsumer($msg)
    {
        try {
            $msgBody = $msg->body;
            // 60s 重复数据只推送一次
            $this->filterComsumerReload($msgBody, 1, 60);
            $parseRes = json_decode($msgBody, true);
            $result = 0;
            if ($parseRes) {
                $userId = ArrayUtil::safeGet($parseRes, 'user_id');
                $type = ArrayUtil::safeGet($parseRes, 'type');
                $result = $this->handleSpecialCarePush($userId, $type);
            }
            Log::info(sprintf("AmpQueue Consumer commindName=%s success body=%s result=%s", "UserPushConsumer", $msgBody, json_encode($result)));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } catch (\Throwable $e) {
            Log::error(sprintf("AmpQueue Consumer error commondName=%s err=%d errmsg=%s strace=%s file=%s lens=%d", "UserPushConsumer", $e->getCode(), $e->getMessage(), $e->getTraceAsString(), $e->getFile(), $e->getLine()));
            if ($e instanceof FQException && $e->getCode() === 513) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                return;
            }
        }
    }

    /**
     * @desc 特别关心用户发生行为推送PUSH文案
     * @param $userId
     * @param $type
     * @return int
     */
    public function handleSpecialCarePush($userId, $type)
    {
        // 隐藏在线状态不推送
        if ($type == 1) {
            $isHiddenOnline = UserInfoService::getInstance()->isHiddenOnline($userId);
            if ($isHiddenOnline) {
                return 1;
            }
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $redisQuiltKey = $this->getQuiltSpecialCareKey($userId);
        $specialCare = $redis->ZRANGE($redisQuiltKey, 0, -1);
        $sendUserIds = [];
        if (!empty($specialCare)) {
            foreach ($specialCare as $toUserid) {
                $sendUserIds[] = (int)$toUserid;
            }
        }
        // 不存在关注的用户
        if (empty($sendUserIds)) {
            return 0;
        }
        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $userId, 0);
        if ($userModel == null) {
            Log::error(sprintf("UserSpecialCareService handleSpecialCarePush userModel not exists error %s  %s", $userId, $type));
            return 0;
        }

        $pushList = [];
        // 如果设置了备注用备注，否则用昵称
        $nickname = $userModel->nickname;
        foreach ($sendUserIds as $toUserId) {
            $name = AttentionService::getInstance()->getUserRemark($toUserId, $userId);
            if ($name) {
                $nickname = $name;
            }
            $title = "特别关心";
            $content = sprintf("%s上线了", $nickname);
            if ($type == 2) {
                $content = sprintf("%s刚刚发布了一条动态", $nickname);
            }
            $pushList[] = [
                'cid' => $toUserId,
                'title' => $title,
                'content' => $content
            ];
        }
        try {
            Log::info(sprintf("UserSpecialCareService handleSpecialCarePush pushlist=%s user_id=%s type=%s", json_encode($pushList), $userId, $type));
            GetuiCommon::getInstance()->pushMessageToSingleBatch($pushList);
        } catch (\Throwable $e) {
            Log::error(sprintf("UserSpecialCareService handleSpecialCarePush error commondName=%s err=%d errmsg=%s strace=%s file=%s lens=%d", "taskConsumer", $e->getCode(), $e->getMessage(), $e->getTraceAsString(), $e->getFile(), $e->getLine()));
        }
        return 1;
    }
}