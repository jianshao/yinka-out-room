<?php

namespace app\query\user\cache;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\query\user\dao\UserModelDao;
use app\query\user\QueryUser;
use think\Exception;
use think\facade\Log;

//用户缓存
class UserModelCache
{
    protected static $instance;
    protected $redis = null;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserModelCache();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    public function modelToData($model)
    {
        $data = [
            'id' => $model->userId,
            'pretty_id' => $model->prettyId,
            'username' => $model->username,
            'password' => $model->password,
            'nickname' => $model->nickname,
            'sex' => $model->sex,
            'intro' => $model->intro,
            'avatar' => $model->avatar,
            'pretty_avatar' => $model->prettyAvatar,
            'pretty_avatar_svga' => $model->prettyAvatarSvga,
            'status' => $model->status,
            'role' => $model->role,
            'birthday' => $model->birthday,
            'city' => $model->city,
            'lv_dengji' => $model->lvDengji,
            'mobile' => $model->mobile,
            'accid' => $model->accId,
            'level_exp' => $model->levelExp,
            'vip_exp' => $model->vipExpiresTime,
            'svip_exp' => $model->svipExpiresTime,
            'register_time' => $model->registerTime,
            'register_ip' => $model->registerIp,
            'login_time' => $model->loginTime,
            'login_ip' => $model->loginIp,
            'is_cancel' => $model->isCancel,
            'deviceid' => $model->deviceId,
            'cancel_user_status' => $model->cancelStatus,
            'attestation' => $model->attestation,
            'invitcode' => $model->inviteCode,
            'register_channel' => $model->registerChannel,
            'regist_version' => $model->registerVersion,
            'imei' => $model->imei,
            'idfa' => $model->idfa,
            'qopenid' => $model->qopenid,
            'wxopenid' => $model->wxopenid,
            'wxunionid' => $model->wxunionid,
            'appleid' => $model->appleid,
            'roomnumber' => $model->roomId,
            'zy_uid' => $model->zyUid,
            'duke_id' => $model->dukeLevel,
            'duke_expires' => $model->dukeExpiresTime,
            'push_notice' => $model->pushNotice,
            'unablechat' => $model->unablechat,
            'source' => $model->source,
        ];
        return $data;
    }

    private function getCacheKey($userId)
    {
        return sprintf(CachePrefix::$USER_INFO_CACHE, $userId);
    }

    /**
     * @param $userId
     * @return QueryUser|null
     */
    public function getUserInfo($userId)
    {
        $userInfoCacheKey = $this->getCacheKey($userId);
        $data = $this->redis->hGetAll($userInfoCacheKey);
        if (!empty($data)) {
            try {
                if (!isset($data['userId']) && !isset($data['id'])) {
                    throw new FQException("fatal userid id filed error");
                }
                if (!isset($data['userId'])) {
                    $data['userId'] = $data['id'] ?? 0;
                }
                if (!isset($data['id'])) {
                    $data['id'] = $data['userId'] ?? 0;
                }
                return UserModelDao::getInstance()->dataToModel($data);
            } catch (\Exception $e) {
//            load db
                Log::error(sprintf("load user error %d", $userId));
            }
        }
        $data = UserModelDao::getInstance()->loadUserData($userId);
        if (empty($data)) {
            return null;
        }
        $this->saveUserInfo($data->toArray());
        return UserModelDao::getInstance()->dataToModel($data);
    }


    /**
     * @param $userId
     * @return false
     */
    public function cleanUserCache($userId){
        if (empty($userId)){
            return false;
        }
        $userInfoCacheKey = $this->getCacheKey($userId);
        return $this->redis->del($userInfoCacheKey);
    }

    public function findNicknameByUserId($userId)
    {
        $userModel = $this->getUserInfo($userId);
        return $userModel ? $userModel->nickname : '';
    }

    public function findAvatarByUserId($userId)
    {
        $userModel = $this->getUserInfo($userId);
        return $userModel ? $userModel->avatar : '';
    }

    public function saveUserInfo($data) {
        $userInfoCacheKey = $this->getCacheKey($data['id']);
        $this->redis->hMset($userInfoCacheKey, $data);
        $this->redis->expire($userInfoCacheKey, CachePrefix::$expireTime);
    }

    public function findList($uids)
    {
        if (empty($uids)) {
            return false;
        }
        $data = [];
        foreach ($uids as $uid) {
            if (empty($uid)) {
                continue;
            }
            $userModel = $this->getUserInfo($uid);
            if (empty($userModel)) {
                continue;
            }
            $data[] = $userModel;
        }
        return $data;
    }

    public function findUserModelMapByUserIds($uids)
    {
        if (empty($uids) or !is_array($uids)) {
            return null;
        }
        $data = [];
        foreach ($uids as $uid) {
            if (empty($uid)) {
                continue;
            }
            $userModel = $this->getUserInfo($uid);
            if (empty($userModel)) {
                continue;
            }
            $data[$uid] = $userModel;
        }
        return $data;
    }


    /**
     * @Info 将所有用户id 存入到一个双向链表，方向性和php数组一至，左侧为在线用户,中点为'999999'，右侧为不在线用户
     * @param $cacheBucketKey
     * @param $userIds
     * @param int $mark
     * @return bool
     */
    public function cacheUserBucket($cacheBucketKey, $userIds, $mark = 1)
    {
        try {
            $this->fitCacheUserBucket($cacheBucketKey, $userIds);
        } catch (\Exception $e) {
            $this->redis->discard();
//            $this->cacheUserBucket($cacheBucketKey, $userIds, $mark);
            return false;
        }
        return true;
    }

    private function fitCacheUserBucket($cacheBucketKey, $userIds)
    {
        $this->redis->watch($cacheBucketKey);
        $this->redis->multi();
        $this->redis->del($cacheBucketKey);
        foreach ($userIds as $uid) {
            $this->redis->rPush($cacheBucketKey, $uid);
        }
        $status = $this->redis->exec();
        if (empty($status)) {
            throw new Exception("cache bucketkey error");
        }
    }
}


