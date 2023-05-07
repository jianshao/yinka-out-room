<?php


namespace app\query\room\service;


use app\domain\room\model\RoomBlackModel;
use app\query\room\BlackUser;
use app\query\room\dao\QueryRoomBlackDao;
use app\query\user\cache\UserModelCache;
use app\query\user\QueryUser;

class QueryRoomBlackService
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
     * @param RoomBlackModel $queryRoomBlackModel
     * @param QueryUser $userModel
     * @return BlackUser
     */
    public function joinModel(RoomBlackModel $queryRoomBlackModel, QueryUser $userModel)
    {
        $blackUser = new BlackUser();
        $blackUser->userId = $userModel->userId;
        $blackUser->prettyId = $userModel->prettyId;
        $blackUser->nickname = $userModel->nickname;
        $blackUser->sex = $userModel->sex;
        $blackUser->avatar = $userModel->avatar;
        $blackUser->prettyAvatar = $userModel->prettyAvatar;
        $blackUser->createTime = $queryRoomBlackModel->ctime;
        $blackUser->longTime = $queryRoomBlackModel->longTime;
        $blackUser->type = $queryRoomBlackModel->type;
        return $blackUser;
    }


    /**
     * @param $roomId
     * @param $type
     * @param $offset
     * @param $count
     * @return BlackUser[]|array
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomBlackUserData($roomId, $type, $offset, $count)
    {
        $result = [];
        $queryRoomBlackModelList = QueryRoomBlackDao::getInstance()->loadRoomBlackModelList($roomId, $type, $offset, $count);
        foreach ($queryRoomBlackModelList as $queryRoomBlackModel) {
            $userModel = UserModelCache::getInstance()->getUserInfo($queryRoomBlackModel->userId);
            if (!empty($userModel)){
                $result[] = $this->joinModel($queryRoomBlackModel, $userModel);
            }
        }
        return $result;
    }

}