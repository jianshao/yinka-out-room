<?php


namespace app\service;


use app\common\RedisCommon;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\query\user\dao\MemberDetailAuditDao;

class CommonCacheService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new CommonCacheService();
        }
        return self::$instance;
    }

    /**
     * @param $userId
     * @return int
     */
    public function getUserCurrentRoom($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $roomId = $redis->hget('user_current_room', $userId);
        if (!empty($roomId)) {
            return intval($roomId);
        }
        return 0;
    }

    /**
     * @info 获取用户是否在麦上
     * @param $userId
     * @return bool  false 不在 true 在
     */
    public function getUserOnMic($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $roomId = $this->getUserCurrentRoom($userId);
        if ($roomId === 0) {
            return false;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf("mic_online_users_%d", $roomId);
        $result = $redis->zRank($cacheKey, $userId);
        if ($result === false) {
            return false;
        }
        return true;
    }


    /**
     * @info 获取用户是否在直播
     * @param $roomId
     * @return int  0 不在 1 在
     */
    public function getRoomIsLive($roomId)
    {
        if (empty($roomId)) {
            return 0;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf("mic_online_users_%d", $roomId);
        $data = $redis->ZREVRANGE($cacheKey, 0, 1);
        if (empty($data)) {
            return 0;
        }
        return 1;
    }


    public function getNewVisitor($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $visitor = $redis->get('new_visit_num_' . $userId);
        return $visitor ? $visitor : 0;
    }

    public function getVisitorCount($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $totalCountKey = 'new_visit_total_count_'.$userId;
        if (!$redis->exists($totalCountKey)) {
            $count = $redis->zCard('new_visit_user_' . $userId);
            $redis->set($totalCountKey, $count);
        } else {
            $count = $redis->get($totalCountKey);
        }
        return $count ? $count : 0;
    }

    public function getUserAlbum($queryUserId, $userId)
    {
        $userKey = 'userinfo_' . $queryUserId;
        $redis = RedisCommon::getInstance()->getRedis();
        $album = $redis->hget($userKey, 'album');

        if ((int)$queryUserId === $userId) {
            $wallModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($queryUserId, MemberDetailAuditActionModel::$wall);
            $album = $wallModel->content ? $wallModel->content : $album;
        }

        if ($album) {
            return explode(',', $album);
        }
        return [];
    }

    public function getUserNicknameAvatar($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = 'userinfo_' . $userId;
        $data = $redis->hMGet($key, ['nickname', 'avatar']);
        return [$data['nickname'], $data['avatar']];
    }

    public function getUserThirdInfo($userId, $thirdId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $tmp = md5($thirdId);
        $info = $redis->get('thirdIdTmp_' . $tmp);

        if (empty($info)) {
            $info = $redis->get('thirdIdTmpUid_' . $userId);
        }

        if (!empty($info)) {
            return json_decode($info, true);
        }

        return [];
    }

    public function setUserPwdLayer($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->hSetNx('RegistUidPwd', $userId, 1);
    }

    public function randomTaskRoomId()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $roomId = $redis->sRandMember('task_room_id');
        return $roomId ? intval($roomId) : 0;
    }
}