<?php


namespace app\domain\sound\service;


use app\api\view\v1\UserView;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\rewardcontent\RandomContent;
use app\domain\sound\dao\SoundLikeModel;
use app\domain\sound\dao\SoundModel;
use app\domain\sound\dao\SoundRecordModel;
use app\query\user\cache\UserModelCache;
use app\service\MemberRecommendService;
use app\utils\CommonUtil;
use constant\SoundConstant;

class SoundService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SoundService();
        }
        return self::$instance;
    }

    public function insertSound($userID,$voice,$voiceTime) {
        SoundModel::getInstance()->getModel()->where(['user_id'=>$userID])->update([
            'is_default'=> SoundConstant::IS_DEFAULT_FALSE,
            'update_time' => time(),
        ]);
        // 添加录音表
        $sound = [
            'user_id' => $userID,
            'voice_time' => $voiceTime,
            'voice' => $voice,
            'create_time' => time(),
            'update_time' => time(),
        ];
        return SoundModel::getInstance()->getModel()->insert($sound);
    }

    public function getSoundListByIDs($soundIDs) :array
    {
        $soundList = SoundModel::getInstance()->getModel()
            ->where('id','in', $soundIDs)
            ->orderRaw("FIND_IN_SET(id, '" . implode(',', $soundIDs) . "')")
            ->select()
            ->toArray();

        return $this->formatSoundList($soundList);
    }

    public function formatSoundList($soundList) :array
    {
        if (empty($soundList)) {
            return [];
        }
        $userIDs = [];
        foreach ($soundList as $key=>$sound) {
            $userIDs[] = $sound['user_id'] ?? 0;
            $soundList[$key]['voice'] = CommonUtil::buildImageUrl($sound['voice']);
            $soundList[$key]['sound_id '] = $sound['id'];
        }

        $userData = UserModelCache::getInstance()->findUserModelMapByUserIds($userIDs);
        foreach ($soundList as $key=>$sound) {
            if (isset($userData[$sound['user_id']])) {
                $userModel = $userData[$sound['user_id']];
                $soundList[$key]['user_info'] = UserView::viewUser($userModel);
            }
        }

        return $soundList;
    }

    /**
     * 从缓存中获取录音数据
     * @param $userId
     * @param $nextSoundId
     * @param $size
     * @return array|\think\Collection
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSoundListByCache($userId, $nextSoundId, $size)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $soundOrderKey = SoundConstant::REDIS_CACHE_ORDER_IDS;
        $exists = $redis->exists($soundOrderKey);
        if (!$exists) {
            $this->generateSoundCache();
        }

        // 不展示的录音ID 不喜欢3天  喜欢5天
        $likeDays = 5;
        $cancelDays = 3;
        $recordIds = [];
        for ($i = 0; $i < $likeDays; $i++) {
            $date = date("Y-m-d", strtotime("-$i day"));
            $key = SoundConstant::REDIS_SOUND_USER_LIKE_PREFIX . $date . ':' . $userId;
            $recordIds = array_merge($recordIds, $redis->sMembers($key));
        }
        for ($i = 0; $i < $cancelDays; $i++) {
            $date = date("Y-m-d", strtotime("-$i day"));
            $key = SoundConstant::REDIS_SOUND_USER_CANCEL_PREFIX . $date . ':' . $userId;
            $recordIds = array_merge($recordIds, $redis->sMembers($key));
        }
        // 自己的录音也不展示
        $mySoundID = SoundModel::getInstance()->getModel()
            ->where('user_id','=', $userId)
            ->column('id');
        $recordIds = array_merge($recordIds,$mySoundID);
        $ids = $redis->lRange($soundOrderKey, 0, -1);

        // 获取不存在 $recordIds 中的录音ID
        $filteredIds = array_diff($ids, $recordIds); // 使用 array_diff 函数比较两个数组，并返回差集
        $filteredIds = array_slice($filteredIds, 0, $size); // 使用 array_slice 函数截取数组的前 $size 个元素

        $today = date("Y-m-d");
        $this->addSoundMatchUserLeave($userId, $filteredIds, $today);

        return $this->getSoundListByIDs($filteredIds);
    }

    /**
     * 按照优先级生成录音数据
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function generateSoundCache()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $soundOrderKey = SoundConstant::REDIS_CACHE_ORDER_IDS;
        // 定义查询优先级
        $priority = array(
            1 => 'user_online_all_list',
            2 => 'user_online_history_all_list',
            3 => 'zb_sound'
        );

        // 定义要获取的声音 ID 的数量
        $limit = 200;

        // 遍历优先级数组
        $redisExistsSoundIDs = [];
        foreach ($priority as $p => $source) {
            // 检查列表中已经有多少声音 ID
            $count = $redis->lLen($soundOrderKey);
            // 如果列表已满，跳出循环
            if ($count >= $limit) {
                break;
            }
            // 如果源是 Redis zset，从中获取用户 ID
            if ($p == 1 || $p == 2) {
                // 使用 scan 的方式从 zset 中获取用户 ID，按照 score（上线时间）降序排列
                $iterator = null;
                while ($userIDs = $redis->zScan($source, $iterator)) {
                    $soundIDs = SoundModel::getInstance()->getModel()
                        ->where('is_default',SoundConstant::IS_DEFAULT_TRUE)
                        ->whereIn('user_id',$userIDs)
                        ->column('id');
                    if (!empty($soundIDs)) {
                        $redis->rPush($soundOrderKey, $soundIDs);
                        $redisExistsSoundIDs = array_merge($redisExistsSoundIDs,$soundIDs);
                        // 检查列表是否已满，跳出循环
                        if ($redis->lLen($soundOrderKey) >= $limit) {
                            break;
                        }
                    }
                }
            }
            // 如果源是 MySQL 表，从中获取声音 ID
            if ($p == 3) {
                $soundList = SoundModel::getInstance()->getModel()
                    ->where(['is_default'=>SoundConstant::IS_DEFAULT_TRUE])
                    ->whereNotIn('id', $redisExistsSoundIDs)
                    ->order('create_time','desc')
                    ->limit($limit)
                    ->select();
                // 遍历声音 ID 并将其推入 Redis 列表
                if ($soundList) {
                    $soundList = $soundList->toArray();
                    foreach ($soundList as $s){
                        $redis->rPush($soundOrderKey, $s['id']);
                    }
                }
            }
        }
        $redis->expire($soundOrderKey,120);
    }


    public function soundLike($userID, $soundID)
    {
        try {
            $soundLike = SoundLikeModel::getInstance()->getModel()
                ->where('sound_id',$soundID)
                ->where('user_id',$userID)
                ->find();
            if ($soundLike != null) {
                return true;
            }

            return Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use($userID, $soundID) {
                SoundModel::getInstance()->getModel()->where(['id'=>$soundID])->inc('like_num')->update();

                // 添加录音表
                $time = time();
                $soundLike = [
                    'user_id' => $userID,
                    'sound_id' => $soundID,
                    'create_time' => $time,
                    'update_time' => $time,
                ];

                // 打招呼
                $soundInfo = SoundModel::getInstance()->getModel()->where(['id'=>$soundID])->find();
                $sendUserID = $soundInfo['user_id'] ?? '';
                if ($sendUserID != $userID) {
                    MemberRecommendService::getInstance()->greet($userID, [$sendUserID], 1);
                }

                // 获取当前日期
                $today = date("Y-m-d");
                $this->addSoundLikeList($userID, $soundID, $today);
                $this->removeSoundMatchUserLeave($userID, $soundID, $today);

                return SoundLikeModel::getInstance()->getModel()->insert($soundLike);
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function soundCancel($userID, $soundID)
    {
        try {
            // 1. like   cancel 写入当天队列中
            // 2. 查询 3天内、5天内的用户录音
            // 3. 不展示这些录音
            // 获取当前日期
            $today = date("Y-m-d");
            $this->addSoundCancelList($userID, $soundID, $today);
            $this->removeSoundMatchUserLeave($userID, $soundID, $today);
        } catch (\Exception $e) {
            throw $e;
        }
    }


    // 添加用户匹配后剩余录音记录
    public function getSoundMatchUserLeave($userId, $today): array
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_LEAVE_PREFIX.$today . ':' . $userId;

        return $redis->sMembers($key);
    }

    // 添加用户匹配后剩余录音记录
    public function addSoundMatchUserLeave($userId, $soundIDs, $today): int
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_LEAVE_PREFIX.$today . ':' . $userId;

        // 先删除之前的数据、在添加记录
        $redis->del($key);
        $res = $redis->sAdd($key, ...$soundIDs);
        $redis->expire($key, 86400);

        return $res;
    }

    // 添加用户匹配后剩余录音记录
    public function removeSoundMatchUserLeave($userId, $soundID, $today)
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_LEAVE_PREFIX.$today . ':' . $userId;
        if ($redis->sCard($key)> 1 )  {
            $res = $redis->sRem($key, $soundID);
            $redis->expire($key, 86400);
        }
    }

    // 添加用户喜欢队列
    public function addSoundLikeList($userId, $soundID, $today): int
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_LIKE_PREFIX.$today . ':' . $userId;
        return $redis->sAdd($key,$soundID);
    }

    public function getSoundLikeList($userId, $today): array
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_LIKE_PREFIX.$today . ':' . $userId;
        return $redis->sMembers($key);
    }

    // 添加用户喜欢队列
    public function addSoundCancelList($userId, $soundID, $today): int
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_CANCEL_PREFIX.$today . ':' . $userId;
        return $redis->sAdd($key,$soundID);
    }

    public function getSoundCancelList($userId, $today): array
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_SOUND_USER_CANCEL_PREFIX.$today . ':' . $userId;
        return $redis->sMembers($key);
    }

    // 获取当前用户今天已经匹配的次数
    public function getMatchedTimes($userId, $today): int
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_MATCH_TIMES_PREFIX.$today . ':' . $userId;
        $matchedTimes = $redis->get($key);
        if ($matchedTimes === false) {
            $matchedTimes = 0;
        }
        $redis->close();
        return intval($matchedTimes);
    }

    // 更新当前用户今天已经匹配的次数
    public function updateMatchedTimes($userId, $today, $matchedTimes): bool
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $key = SoundConstant::REDIS_MATCH_TIMES_PREFIX.$today . ':' . $userId;
        return $redis->set($key, $matchedTimes, 86400);
    }
}