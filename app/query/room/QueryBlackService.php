<?php


namespace app\query\room;


use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomBlackModelDao;
use app\domain\room\dao\RoomModelDao;
use app\query\room\dao\QueryRoomBlackDao;
use app\query\room\service\QueryRoomBlackService;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;

class QueryBlackService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryBlackService();
        }
        return self::$instance;
    }

    public function searchBlackUser($userId, $roomId, $search)
    {
        $ret = [];
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException('此房间不存在', 500);
        }

        $excludeUserIds = [$userId];
        if ($roomModel->userId != $userId) {
            $excludeUserIds[] = $roomModel->userId;
        }
        list($queryUsers, $total) = QueryUserService::getInstance()->matchUsers($search, $excludeUserIds);
        $userIds = [];
        foreach ($queryUsers as $queryUser) {
            $userIds[] = $queryUser->userId;
        }
        $blackList = RoomBlackModelDao::getInstance()->loadDataForUserIdsRoomIdList($userIds, $roomId);
        $blackMap = [];
        foreach ($blackList as $black) {
            $blackType = ArrayUtil::safeGet($blackMap, $black['user_id'], 0);
            $blackMap[$black['user_id']] = $blackType | $black['type'];
        }
        foreach ($queryUsers as $queryUser) {
            $blackUser = new BlackUser();
            $blackUser->userId = $queryUser->userId;
            $blackUser->prettyId = $queryUser->prettyId;
            $blackUser->nickname = $queryUser->nickname;
            $blackUser->sex = $queryUser->sex;
            $blackUser->avatar = $queryUser->avatar;
            $blackUser->prettyAvatar = $queryUser->prettyAvatar;
            $blackUser->createTime = 0;
            $blackUser->longTime = 0;
            $blackUser->type = ArrayUtil::safeGet($blackMap, $blackUser->userId);
            $ret[] = $blackUser;
        }
        return [$ret, $total];
    }

    /**
     * @param $roomId
     * @param $type
     * @param $offset
     * @param $count
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function listBlackUser($roomId, $type, $offset, $count)
    {
        $total = QueryRoomBlackDao::getInstance()->getTotalForRooomIdType($roomId, $type);
        $ret = QueryRoomBlackService::getInstance()->loadRoomBlackUserData($roomId, $type, $offset, $count);
        return [$ret, $total];
    }
}