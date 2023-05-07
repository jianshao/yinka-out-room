<?php


namespace app\query\room;


use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\model\RoomManagerModel;
use app\query\room\cache\RoomModelCache;
use app\query\room\dao\QueryRoomDao;
use app\query\room\dao\QueryRoomManagerDao;
use app\query\room\service\QueryRoomService;
use app\query\user\cache\UserModelCache;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;

class QueryManagerService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new QueryManagerService();
        }
        return self::$instance;
    }

    public function dataToModel($userModel, $managerType) {
        $model = new QueryManager();
        $model->userId = $userModel->userId;
        $model->prettyId = $userModel->prettyId;
        $model->nickname = $userModel->nickname;
        $model->avatar = $userModel->avatar;
        $model->prettyAvatar = $userModel->prettyAvatar;
        $model->sex = $userModel->sex;
        $model->vipLevel =$userModel->vipLevel;
        $model->lvDengji = $userModel->lvDengji;
        $model->managerType = ArrayUtil::safeGet(RoomManagerModel::$viewType, $managerType, 0);
        $model->isManager = true;
        return $model;
    }

    public function searchManager($roomId, $search) {
        $roomData = QueryRoomDao::getInstance()->loadRoom($roomId);
        if (empty($roomData)) {
            throw new FQException('此房间不存在', 500);
        }

        list($queryUsers, $total) = QueryUserService::getInstance()->matchUsers($search);

        $managerMap = [];
        $managerList = QueryRoomManagerDao::getInstance()->loadAllManager($roomId);
        foreach ($managerList as $manager) {
            $managerMap[$manager->userId] = $manager;
        }

        $ret = [];
        foreach ($queryUsers as $queryUser) {
            $manager = new QueryManager();
            $manager->userId = $queryUser->userId;
            $manager->prettyId = $queryUser->prettyId;
            $manager->nickname = $queryUser->nickname;
            $manager->avatar = $queryUser->avatar;
            $manager->sex = $queryUser->sex;
            $manager->vipLevel = $queryUser->vipLevel;
            $manager->lvDengji = $queryUser->lvDengji;
            $manager->isManager = array_key_exists($queryUser->userId, $managerMap);
            $manager->managerType = $this->coveManagerType($queryUser->userId, $managerMap);
            $ret[] = $manager;
        }
        return [$ret, $total];
    }

    private function coveManagerType($userId, $managerMap)
    {
        if (ArrayUtil::safeGet($managerMap, $userId)){
            $model = $managerMap[$userId];
            return ArrayUtil::safeGet($model::$viewType, $model->type, 0);
        }
        return 0;
    }

    public function listManagers($roomId, $offset, $count) {
        $ret = [];
        $managers = QueryRoomManagerDao::getInstance()->loadManagers($roomId, $offset, $count);
        foreach ($managers as $manager) {
            $userModel = UserModelCache::getInstance()->getUserInfo($manager->userId);
            if (!empty($userModel)){
                $ret[] = $this->dataToModel($userModel, $manager->type);
            }
        }

        $total = QueryRoomManagerDao::getInstance()->getManagerCount($roomId);
        return [$ret, $total];
    }
}