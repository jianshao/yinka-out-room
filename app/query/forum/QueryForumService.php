<?php


namespace app\query\forum;
use app\domain\duke\DukeSystem;
use app\query\forum\dao\ForumBlackModelDao;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;

class QueryForumService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new QueryForumService();
        }
        return self::$instance;
    }

    public function listBlackUser($userId, $offset, $count) {

        $blackModels = ForumBlackModelDao::getInstance()->getBlackModelsByBlackId($userId, $offset, $count);

        $total = ForumBlackModelDao::getInstance()->getBlackCount($userId);
        $ret = [];
        foreach ($blackModels as $blackModel) {
            $userModel = UserModelCache::getInstance()->getUserInfo($blackModel->toUserId);
            if (empty($userModel)){
                continue;
            }

            $blackUser = new ForumBlackUser();
            $blackUser->userId = $userModel->userId;
            $blackUser->prettyId = $userModel->prettyId;
            $blackUser->nickname = $userModel->nickname;
            $blackUser->sex = $userModel->sex;
            $blackUser->intro = $userModel->intro;
            $blackUser->avatar = $userModel->avatar;
            $blackUser->vipLevel = $userModel->vipLevel;
            $blackUser->lvDengji = $userModel->lvDengji;
            $blackUser->dukeLevel = $userModel->dukeLevel;
            $blackUser->createTime = $blackModel->createTime;
            $ret[] = $blackUser;
        }
        return [$ret, $total];
    }
}