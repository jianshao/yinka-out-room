<?php


namespace app\query\user\service;


use app\common\RedisCommon;
use app\query\user\cache\UserModelCache;
use app\query\user\dao\AttentionModelDao;
use app\query\user\dao\FansModelDao;
use app\query\user\dao\FriendModelDao;
use app\query\user\QueryAttention;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;

class AttentionService
{
    protected static $instance;


    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttentionService();
        }
        return self::$instance;
    }

    public function dataToModel($userModel, $createTime, $status=0) {
        $attention = new QueryAttention();
        $attention->userId = $userModel->userId;
        $attention->prettyId = $userModel->prettyId;
        $attention->nickname = $userModel->nickname;
        $attention->intro = $userModel->intro;
        $attention->avatar = $userModel->avatar;
        $attention->sex = $userModel->sex;
        $attention->lvDengji = $userModel->lvDengji;
        $attention->vipLevel = $userModel->vipLevel;
        $attention->dukeLevel = $userModel->dukeLevel;
        $attention->createTime = $createTime;
        $attention->status = $status;
        return $attention;
    }

    public function getUnreadMsgCount($userId) {
        return FansModelDao::getInstance()->getUnreadMsgCount($userId);
    }

    public function loadNewFansModel($userId){
        return FansModelDao::getInstance()->loadNewFansModel($userId);
    }

    /**
     * @param $userId
     * @param $offset
     * @param $count
     * @return array
     */
    public function listAttention($userId, $offset, $count) {

        $total = AttentionModelDao::getInstance()->getAttentionCount($userId);

        $models = AttentionModelDao::getInstance()->getList($userId, $offset, $count);

        $ret = [];
        foreach ($models as $model) {
            $userModel = UserModelCache::getInstance()->getUserInfo($model->attentionId);
            if (empty($userModel)){
                continue;
            }
            $ret[] = $this->dataToModel($userModel, $model->createTime);
        }
        return [$ret, $total];
    }

    /**
     * @param $userId
     * @param $offset
     * @param $count
     * @return array
     */
    public function listFriend($userId, $offset, $count) {
        $models = FriendModelDao::getInstance()->getList($userId, $offset, $count);

        $ret = [];
        foreach ($models as $model) {
            $userModel = UserModelCache::getInstance()->getUserInfo($model->friendId);
            if (empty($userModel)){
                continue;
            }
            $ret[] = $this->dataToModel($userModel, $model->createTime);
        }
        $total = FriendModelDao::getInstance()->getFriendCount($userId);

        return [$ret, $total];
    }

    /**
     * @param $userId
     * @param $offset
     * @param $count
     * @return array
     */
    public function listFans($userId, $offset, $count) {
        $models = FansModelDao::getInstance()->getList($userId, $offset, $count);

        $ret = [];
        foreach ($models as $model) {
            $userModel = UserModelCache::getInstance()->getUserInfo($model->fansId);
            if (empty($userModel)){
                continue;
            }
            $ret[] = $this->dataToModel($userModel, $model->createTime, $model->isRead);
        }
        $total = FansModelDao::getInstance()->getFollowCount($userId);

        return [$ret, $total];
    }

    public function isFocus($userId, $userIdEd) {
        return AttentionModelDao::getInstance()->loadAttention($userId, $userIdEd) != null;
    }


    /**
     * @desc 用户备注的key
     * @param $userId
     * @param $toUserid
     * @return string
     */
    public function getUserRemarkKey($userId, $toUserid)
    {
        return sprintf('user_remark_name_%s_%s', $userId, $toUserid);
    }

    /**
     * @desc 获取用户备注
     * @param $userId
     * @param $toUserid
     * @return false|mixed|string
     */
    public function getUserRemark($userId, $toUserid)
    {
        if ($userId == $toUserid){
            return '';
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getUserRemarkKey($userId, $toUserid);

        return $redis->get($redisKey) ?: '';
    }
}