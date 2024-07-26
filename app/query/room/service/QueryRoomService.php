<?php


namespace app\query\room\service;

use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomHotValueDao;
use app\domain\room\dao\RoomInfoMapDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\model\RoomModel;
use app\domain\room\model\RoomTypeModel;
use app\domain\user\model\UserModel;
use app\domain\version\cache\VersionCheckCache;
use app\query\redpacket\dao\RedPacketDetailModelDao;
use app\query\redpacket\dao\RedPacketModelDao;
use app\query\room\cache\QueryRoomCache;
use app\query\room\cache\RedisCommon;
use app\query\room\cache\RoomModelCache;
use app\query\room\dao\QueryRoomDao;
use app\query\room\dao\QueryRoomFollowDao;
use app\query\room\dao\QueryRoomManagerDao;
use app\query\room\elastic\RoomModelElasticDao;
use app\query\room\model\QueryRoom;
use app\query\user\cache\UserModelCache;
use app\service\LockService;

class QueryRoomService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryRoomService();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new QueryRoom();
        $model->roomId = $data['id'];
        $model->guildId = $data['guild_id'];
        $model->prettyRoomId = $data['pretty_room_id'];
        $model->roomName = $data['room_name'];
        $model->roomType = $data['room_type'];
        $model->roomMode = $data['room_mode'];
        $model->isHot = $data['is_hot'];
        $model->roomTypeName = $data['room_mode'];
        $model->lock = $data['room_lock'];
        $model->visitorNumber = RoomHotValueDao::getInstance()->getRoomHotValue($data['id']);
        $model->visitorExternNumber = $data['visitor_externnumber'];
        $model->visitorUsers = $data['visitor_users'];
        $model->isLive = $data['is_live'];
        $model->hxRoom = $data['hx_room'];
        $model->image = $data['room_image'];
        $model->tagImage = $data['tag_image'];
        $model->tabIcon = $data['tab_icon'];
        $model->ownerUserId = $data['user_id'];
        $model->ownerNickname = $data['nickname'];
        $model->ownerAvatar = $data['avatar'];
        return $model;
    }

    /**
     * @param QueryRoom $model
     * @return array
     */
    public function modelToData(QueryRoom $model)
    {
        $data['id'] = $model->roomId;
        $data['guild_id'] = $model->guildId;
        $data['pretty_room_id'] = $model->prettyRoomId;
        $data['room_name'] = $model->roomName;
        $data['room_type'] = $model->roomType;
        $data['room_mode'] = $model->roomMode;
        $data['is_hot'] = $model->isHot;
        $data['room_mode'] = $model->roomTypeName;
        $data['room_lock'] = $model->lock;
        $data['visitor_number'] = $model->visitorNumber;
        $data['visitor_externnumber'] = $model->visitorExternNumber;
        $data['visitor_users'] = $model->visitorUsers;
        $data['is_live'] = $model->isLive;
        $data['hx_room'] = $model->hxRoom;
        $data['room_image'] = $model->image;
        $data['tag_image'] = $model->tagImage;
        $data['tab_icon'] = $model->tabIcon;
        $data['user_id'] = $model->ownerUserId;
        $data['nickname'] = $model->ownerNickname;
        $data['avatar'] = $model->ownerAvatar;
        return $data;
    }

    /**
     * @param RoomModel $roomModel
     * @param RoomTypeModel $roomTypeModel
     * @param UserModel $userModel
     * @return QueryRoom
     */
    public function joinDataToModel(RoomModel $roomModel, RoomTypeModel $roomTypeModel, UserModel $userModel)
    {
        $model = new QueryRoom();
        $model->roomId = $roomModel->roomId;
        $model->guildId = $roomModel->guildId;
        $model->prettyRoomId = $roomModel->prettyRoomId;
        $model->roomName = $roomModel->name;
        $model->roomType = $roomModel->roomType;
        $model->roomMode = $roomTypeModel->roomMode;
        $model->isHot = $roomModel->isHot;
        $model->roomTypeName = $roomModel->roomType;
        $model->lock = $roomModel->lock;
        $model->visitorNumber = RoomHotValueDao::getInstance()->getRoomHotValue($roomModel->roomId);
        $model->visitorExternNumber = $roomModel->visitorExternNumber;
        $model->visitorUsers = $roomModel->visitorUsers;
        $model->isLive = $roomModel->liveType;
        $model->hxRoom = $roomModel->hxRoom;
        $model->image = $roomModel->image ?? "";
        $model->tagImage = $roomModel->tagImage;
        $model->tagId = $roomModel->tagId;
        $model->tabIcon = $roomTypeModel->tabIcon;
        $model->ownerUserId = $roomModel->userId;
        $model->ownerNickname = $userModel->nickname;
        $model->ownerAvatar = $userModel->avatar;
        return $model;
    }


    public function searchRoomForElasticDb($search, $offset, $count)
    {
        if (empty($search)) {
            return [[], 0];
        }
        if (is_numeric($search)) {
            list($roomModelList, $total) = RoomModelElasticDao::getInstance()->searchRoomForId($search, $offset, $count);
        } else {
            list($roomModelList, $total) = RoomModelElasticDao::getInstance()->searchRoomForRoomName($search, $offset, $count);
        }
        if (empty($roomModelList)) {
            return [[], 0];
        }
        return [$this->encodeRoomsData($roomModelList), $total];
    }

    /**
     * @info 搜索房间
     * @param $search
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchRoomForElastic($search, $offset, $count)
    {
        if (empty($search)) {
            return [[], 0];
        }
//        $cacheData = QueryRoomCache::getInstance()->getSearchRoomForElasticCache($search);
//        if ($cacheData !== false) {
//            return [$cacheData, count($cacheData)];
//        }
//        $lockKey = QueryRoomCache::getInstance()->getSearchRoomForElasticLockKey($search);
//        LockService::getInstance()->lock($lockKey);
//        try {
//            list($list, $total) = $this->searchRoomForElasticDb($search, $offset, $count);
////            搜索权重sort
//            if (empty($list)) {
//                QueryRoomCache::getInstance()->searchRoomForElasticStoreZero($search);
//            } else {
//                QueryRoomCache::getInstance()->searchRoomForElasticStoreModel($search, $list);
//            }
//        } finally {
//            LockService::getInstance()->unlock($lockKey);
//        }
        list($list, $total) = $this->searchRoomForElasticDb($search, $offset, $count);

        return [$list, $total];
    }

    /**
     * @info 提审中
     * @param $search
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchVersionRoom($search, $offset = 0, $count = 10)
    {
        list($roomModelList, $_) = RoomModelElasticDao::getInstance()->searchRoomForId($search, $offset, $count);
        return $this->encodeRoomsData($roomModelList);
    }

    /**
     * @param $userId
     * @return QueryRoom|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryMyRoom($userId)
    {
        $roomId = RoomInfoMapDao::getInstance()->getRoomIdByUserId($userId);
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            return null;
        }
        $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomModel->roomType);
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        return $userModel ? $this->joinDataToModel($roomModel, $roomTypeModel, $userModel) : null;
    }

    /**
     * @param $userId
     * @return array
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryMyManagerRoom($userId)
    {
        $roomIds = QueryRoomManagerDao::getInstance()->loadRoomIdsByUserId($userId);
        $roomModels = QueryRoomDao::getInstance()->loadModelForRoomIds($roomIds);
        return $this->encodeRoomsData($roomModels);
    }

    /**
     * @param $offset
     * @param $count
     * @return array
     */
    public function queryHotRooms($offset, $count)
    {
        list($roomModelList, $_) = RoomModelElasticDao::getInstance()->queryHotRooms($offset, $count);
        if (empty($roomModelList)) {
            return [];
        }
        return $this->encodeRoomsData($roomModelList);
    }

    /**
     * 查询提审中 展示的热门房间
     * @param $count
     * @return array
     */
    public function queryTsHotRooms($count)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $roomIds = $redis->sRandMember(VersionCheckCache::$hotRoomListKey,$count);
        list($roomModelList,$_) = RoomModelElasticDao::getInstance()->queryRoomsInfo($roomIds);
        if (empty($roomModelList)) {
            return [];
        }
        return $this->encodeRoomsData($roomModelList);
    }

    /**
     * @param $userId
     * @param $offset
     * @param $count
     * @return array
     */
    public function queryFollowRooms($userId, $offset, $count)
    {
        $roomIds = QueryRoomFollowDao::getInstance()->getRoomIdsForUserId($userId, $offset, $count);
        if (empty($roomIds)) {
            return [[], 0];
        }
        $roomModels = QueryRoomDao::getInstance()->loadModelForRoomIds($roomIds);
        $dataList = $this->encodeRoomsData($roomModels);
        $total = QueryRoomFollowDao::getInstance()->getRoomIdsCountForUserId($userId);
        return [$dataList, $total];
    }

    /**
     * @param $roomId
     * @return RoomModel|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findRoomTypeByRoomIdForCache($roomId)
    {
        if (empty($roomId)) {
            return false;
        }
        $cacheData = RoomModelCache::getInstance()->findRoomTypeByRoomId($roomId);
        if (!empty($cacheData)) return $cacheData;
        $model = QueryRoomDao::getInstance()->loadRoom($roomId);
        if ($model === null) {
            $model = new RoomModel();
            RoomModelCache::getInstance()->storeZero($roomId, $model);
            return $model;
        }
        RoomModelCache::getInstance()->store($roomId, $model);
        return $model;
    }


    /**
     * @param $roomId
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryRoomInfo($roomId)
    {
        $roomModel = QueryRoomDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间信息异常", 500);
        }
        $managerList = QueryRoomManagerDao::getInstance()->loadAllManager($roomId);
        $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomModel->roomType);
        return [$roomModel, $managerList, $roomTypeModel];
    }


    /**
     * @param $search
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchRoomInfo($search)
    {
        $roomModel = $this->loadRoomModelForIdAndPretty($search);
        if ($roomModel === null) {
            throw new FQException("房间信息异常", 500);
        }
        $managerList = QueryRoomManagerDao::getInstance()->loadAllManager($search);
        $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomModel->roomType);
        return [$roomModel, $managerList, $roomTypeModel];
    }

    /**
     * @param $searchId
     * @return RoomModel|null
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function loadRoomModelForIdAndPretty($searchId)
    {
        $roomId = RoomInfoMapDao::getInstance()->getRoomIdByPretty($searchId);
        if (empty($roomId)) {
            $roomModel = RoomModelDao::getInstance()->loadRoom($searchId);
        } else {
            $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        }
        return $roomModel;
    }


    /**
     * @param $roomId
     * @return QueryRoom|null
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryRoomsImpl($roomId)
    {
        $roomModel = QueryRoomDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            return null;
        }

        $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomModel->roomType);
        $userModel = UserModelCache::getInstance()->getUserInfo($roomModel->userId);
        return $userModel ? $this->joinDataToModel($roomModel, $roomTypeModel, $userModel) : null;
    }

    /**
     * @param $roomId
     * @param $userId
     * @return int
     * @throws FQException
     */
    public function hasRedPacket($roomId, $userId)
    {
        $haveRed = RedPacketModelDao::getInstance()->getModel()->where([
                'room_id' => $roomId,
                'status' => 1]
        )->column('id');
        if (!empty($haveRed)) {
            $isget = RedPacketDetailModelDao::getInstance()->getModel()->where([
                ['red_id', 'in', $haveRed],
                ['get_uid', '=', $userId]
            ])->column('id');

            if (count($haveRed) != count($isget)) {
                return 1;
            }
        }
        return 0;
    }

    private function encodeRoomsData($roomModels)
    {
        $result = [];
        foreach ($roomModels as $roomModel) {
            $userModel = UserModelCache::getInstance()->getUserInfo($roomModel->userId);
            if (!empty($userModel)) {
                $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomModel->roomType);
                $result[] = $this->joinDataToModel($roomModel, $roomTypeModel, $userModel);
            }
        }
        return $result;
    }

    public function initRoomData($roomIds)
    {
        if (empty($roomIds)) {
            return [];
        }
        $data = [];
        foreach ($roomIds as $roomId => $hot) {
            $item = QueryRoomDao::getInstance()->loadRoom($roomId);
            if ($item == null) {
                continue;
            }
            $data[] = $item;
        }
        return $data;
    }

}