<?php


namespace app\query\user\service;


use app\common\RedisCommon;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserInfoService;
use app\query\user\cache\UserModelCache;
use app\query\user\dao\AttentionModelDao;
use app\event\VisitEvent;
use app\query\visitor\QueryVisitor;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

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
     * @Info  用户来访
     * @param $userId
     * @param $offset
     * @param $count
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryVisitor($userId, $offset, $count)
    {
        $key = 'new_visit_user_' . $userId;   //访客键值
        $redis = RedisCommon::getInstance()->getRedis();
        $rankList = $redis->zRevRange($key, $offset, $count, true);
        // 统计访客总数
        $totalCountKey = UserInfoService::getInstance()->getNewVisitTotalCountKey($userId);
        if (!$redis->exists($totalCountKey)) {
            $count = $redis->zCard($key);
            $redis->set($totalCountKey, $count);
        } else {
            $count = $redis->get($totalCountKey);
        }
        $ret = [];
        if (!empty($rankList)) {
            $rankUserIds = array_keys($rankList);
            $attentionMap = AttentionModelDao::getInstance()->findMapByAttentionIds($userId, $rankUserIds);
            $userModels = UserModelCache::getInstance()->findList($rankUserIds);
            $visitorMap = [];
            $timestamp = time();
            foreach ($userModels as $userModel) {
                $visitor = new QueryVisitor();
                $visitor->userId = $userModel->userId;
                $visitor->nickname = $userModel->nickname;
                $visitor->avatar = $userModel->avatar;
                $visitor->intro = $userModel->intro;
                $visitor->sex = $userModel->sex;
                $visitor->lvDengji = $userModel->lvDengji;
                $visitor->vipLevel = $userModel->vipLevel;
                $visitor->isAttention = array_key_exists($visitor->userId, $attentionMap);
                $visitor->dukeLevel = $userModel->dukeLevel;
                $countKey = UserInfoService::getInstance()->getNewVisitCountKey($visitor->userId, $userId);
                $visitor->visitorCount = $redis->get($countKey) ?: 1;
                $visitorMap[$visitor->userId] = $visitor;
            }
            $isCleanOldVisit = false;
            $toDayDate = date('Y-m-d', time());   //2022-05-26
            $miniTime = strtotime("$toDayDate -3 month");
            //清除访客统计
            $new_visit_num = 'new_visit_num_' . $userId;
            $redis->set($new_visit_num, 0);
            foreach ($rankList as $visitorUserId => $visitorTime) {
                // 访问的时间小于三个月之前
                if ($visitorTime < $miniTime){
                    $isCleanOldVisit = true;
                }
                $visitor = ArrayUtil::safeGet($visitorMap, $visitorUserId);
                if ($visitor != null) {
                    $visitor->visitorTime = $visitorTime;
                    $ret[] = $visitor;
                }
            }
            // 清理三个月之前的数据
            if ($isCleanOldVisit) {
                $redis->ZREMRANGEBYSCORE($key, 0, $miniTime);
            }
            event(new VisitEvent($userId, $timestamp));
        }
        return [$ret, (int)$count];
    }

    /**
     * @desc 根据时间获取拜访id
     * @param $userId
     * @param $startTime
     * @param $endTime
     */
    public function getVisitorUserByTime($userId, $startTime, $endTime)
    {
        $key = 'new_visit_user_' . $userId;   //访客键值
        $redis = RedisCommon::getInstance()->getRedis();

        $rankList = $redis->zRevRangeByScore($key, $endTime, $startTime);

        return count($rankList);
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
     * @desc 是否是隐身访问
     * @param $userId
     * @param $toUserid
     */
    public function isHiddenVisitor($userId, $toUserid)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenVisitorKey($userId);
        $score = $redis->zScore($redisKey, $toUserid);
        return (bool)$score;
    }

    /**
     * @desc 获取隐身访问用户列表
     * @param $userId
     * @param $page
     * @param $pageNum
     * @return array
     */
    public function getHiddenVisitorList($userId, $page, $pageNum)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenVisitorKey($userId);

        $limitStart = ($page - 1) * $pageNum;
        $limitEnd = ($limitStart + $pageNum) - 1;

        $count = $redis->zCard($redisKey); //统计ScoreSet总数
        // 分页读取隐身访问列表
        $hiddenVisitor = $redis->ZRANGE($redisKey, $limitStart, $limitEnd, true);
        $hiddenVisitorList = $this->formatHiddenVisitor($hiddenVisitor);

        return [$count, $hiddenVisitorList];
    }

    /**
     * @desc 隐身访问数据
     * @param $hiddenVisitor
     * @return array
     */
    public function formatHiddenVisitor($hiddenVisitor)
    {
        $hiddenVisitorList = [];
        if (!empty($hiddenVisitor)) {
            $userIds = array_keys($hiddenVisitor);
            $userList = UserModelDao::getInstance()->findUserModelMapByUserIds($userIds);
            foreach ($hiddenVisitor as $userId => $time) {
                $userModel = $userList[$userId];
                $hiddenVisitorList[] = [
                    'user_id' => $userId,
                    'nickname' => $userModel->nickname,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'hidden_time' => ceil($time),
                ];
            }
        }
        return $hiddenVisitorList;
    }
}