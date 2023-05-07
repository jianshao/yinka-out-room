<?php

namespace app\domain\room\dao;

use app\core\mysql\ModelDao;
use app\domain\room\model\RoomModel;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\cache\PersonQueryRoomModelCache;
use app\query\room\cache\RoomModelCache;

class RoomModelDao extends ModelDao
{
    protected $table = 'zb_languageroom';
    protected $pk = 'id';
    protected $serviceName = 'roomMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return RoomModel
     */
    public function dataToModel($data)
    {
        $ret = new RoomModel();
        $ret->roomId = $data['id'];
        $ret->guildId = $data['guild_id'];
        $ret->prettyRoomId = $data['pretty_room_id'];
        $ret->userId = $data['user_id'];
        $ret->name = $data['room_name'];
        $ret->desc = $data['room_desc'];
        $ret->image = $data['room_image'];
        $ret->welcomes = $data['room_welcomes'];
        $ret->roomType = $data['room_type'];
        $ret->tags = $data['room_tags'];
        $ret->mode = $data['room_mode'];
        $ret->password = $data['room_password'];
        $ret->lock = $data['room_lock'];
        $ret->createTime = $data['room_createtime'];
        $ret->isFreeMic = $data['is_freemai'];
        $ret->isOpenHeartValue = $data['isopen_heart_value'];
        $ret->fansNotices = $data['fans_notices'];
        $ret->liveType = $data['is_live'];
        $ret->visitorNumber = $data['visitor_number'];
        $ret->socitayId = $data['socitay_id'];
        $ret->visitorExternNumber = $data['visitor_externnumber'];
        $ret->hxRoom = $data['hx_room'];
        $ret->swRoom = $data['sw_room'];
        $ret->visitorUsers = $data['visitor_users'];
        $ret->roomChannel = $data['room_channel'];
        $ret->backgroundImage = $data['background_image'];
        $ret->isHot = $data['is_hot'];
        $ret->isWheat = $data['is_wheat'];
        $ret->tagImage = $data['tag_image'];
        $ret->guildIndexId = $data['guild_index_id'];
        $ret->type = $data['type'];
        $ret->isHide = $data['is_hide'];
        $ret->isBlock = $data['is_block'];
        return $ret;
    }

    public function modelToData($model)
    {
        $ret = [
            'id' => $model->roomId,
            'guild_id' => $model->guildId,
            'pretty_room_id' => $model->prettyRoomId,
            'user_id' => $model->userId,
            'room_name' => $model->name,
            'room_desc' => $model->desc,
            'room_image' => $model->image,
            'room_createtime' => $model->createTime,
            'room_welcomes' => $model->welcomes,
            'room_type' => $model->roomType,
            'room_tags' => $model->tags,
            'room_mode' => $model->mode,
            'room_password' => $model->password,
            'room_lock' => $model->lock,
            'is_freemai' => $model->isFreeMic,
            'isopen_heart_value' => $model->isOpenHeartValue,
            'fans_notices' => $model->fansNotices,
            'is_live' => $model->liveType,
            'visitor_number' => $model->visitorNumber,
            'socitay_id' => $model->socitayId,
            'visitor_externnumber' => $model->visitorExternNumber,
            'hx_room' => $model->hxRoom,
            'sw_room' => $model->swRoom,
            'visitor_users' => $model->visitorUsers,
            'room_channel' => $model->roomChannel,
            'background_image' => $model->backgroundImage,
            'is_hot' => $model->isHot,
            'is_wheat' => $model->isWheat,
            'tag_image' => $model->tagImage,
            'guild_index_id' => $model->guildIndexId,
            'type' => $model->type,
            'is_hide' => $model->isHide,
            'is_block' => $model->isBlock,
        ];
        return $ret;
    }


    /**
     * @param $model
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function saveRoomModel(RoomModel $model)
    {
        RoomInfoMapDao::getInstance()->addByGuildId($model->guildId, $model->roomId);
        RoomInfoMapDao::getInstance()->addByPretty($model->prettyRoomId, $model->roomId);
        RoomInfoMapDao::getInstance()->addByUserId($model->userId, $model->roomId);
        return true;
    }

    /**
     * @param $roomIds
     * @return array
     */
    public function loadModelForRoomIds($roomIds)
    {
        if (empty($roomIds) || !is_array($roomIds)) {
            return [];
        }
        $models = $this->getModels($roomIds);
        $result = [];
        foreach ($models as $model) {
            $itemModel = $model->model->where([['id', 'in', $model->list]])->select();
            if ($itemModel !== null) {
                foreach ($itemModel->toArray() as $itemArr) {
                    $resultItemModel = $this->dataToModel($itemArr);
                    $result[] = $resultItemModel;
                }
            }
        }
        return $result;
    }

    /**
     * @param $roomIds
     * @return array
     */
    public function findRoomModelsMap($roomIds)
    {
        if (empty($roomIds)) {
            return [];
        }
        $models = $this->getModels($roomIds);
        $result=[];
        foreach($models as $model) {
            $itemModel = $model->model->where([['id', 'in', $model->list]])->select();
            if ($itemModel!==null){
                foreach($itemModel->toArray() as $itemData){
                    $id = $itemData['id'] ?? 0;
                    $result[$id] = $itemData;
                }
            }
        }
        return $result;
    }

    public function findRoomTypeByRoomId($roomId)
    {
        return $this->getModel($roomId)->field('room_type')->where(['id' => $roomId])->find();
    }

    /**
     * @param $userId
     * @return RoomModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomByUserId($userId)
    {
        $roomId = RoomInfoMapDao::getInstance()->getRoomIdByUserId($userId);
        return $this->loadRoom($roomId);
    }

    /**
     * @param RoomModel $roomModel
     * @throws \app\domain\exceptions\FQException
     */
    public function createRoom(RoomModel $roomModel)
    {
        $data = $this->modelToData($roomModel);
        $this->getModel($roomModel->roomId)->save($data);
    }

    /**
     * @param $roomId
     * @param $data
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function saveRoomForData($roomId,$data){
        if (empty($roomId) || empty($data)){
            return false;
        }
        return $this->getModel($roomId)->where('id', $roomId)->save($data);
    }


    /**
     * @param $roomId
     * @param $datas
     * @return \app\core\model\BaseModel
     * @throws \app\domain\exceptions\FQException
     */
    public function updateDatas($roomId, $datas)
    {
        $re = $this->getModel($roomId)->where(['id' => $roomId])->update($datas);
        RoomModelCache::getInstance()->clearCache($roomId);
        return $re;
    }

    /**
     * @param $roomId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isRoomExists($roomId)
    {
        $data = $this->getModel($roomId)->field('id')->where(['id' => $roomId])->find();
        return !empty($data);
    }

    /**
     * @param $roomId
     * @return RoomModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomWithLock($roomId)
    {
        return $this->loadRoomImpl($roomId, true);
    }

    /**
     * @param $roomId
     * @return RoomModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoom($roomId)
    {
        return $this->loadRoomImpl($roomId, false);
    }

    /**
     * @param $roomId
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomData($roomId)
    {
        if (empty($roomId)) {
            return null;
        }
        $object = $this->getModel($roomId)->where(['id' => $roomId])->find();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }


    /**
     * @param $roomId
     * @param $field
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomDataField($roomId, $field)
    {
        if (empty($roomId) || empty($field)) {
            return null;
        }
        $object = $this->getModel($roomId)->where(['id' => $roomId])->field($field)->find();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }


    /**
     * @param $roomId
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function loadRoomTypeForId($roomId)
    {
        if (empty($roomId)) {
            return 0;
        }
        $room_type = $this->getModel($roomId)->where('id', $roomId)->value('room_type');
        return (int)$room_type;
    }

    /**
     * @param $roomId
     * @param bool $lock
     * @return RoomModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function loadRoomImpl($roomId, $lock = true)
    {
        if ($lock) {
            $data = $this->getModel($roomId)->lock(true)->where(['id' => $roomId])->find();
        } else {
            $data = $this->getModel($roomId)->where(['id' => $roomId])->find();
        }
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @info 获取有效的公会房间ids
     * @return array
     */
    public function getOnlineGuildRoomIds()
    {
        $guildId = 0;
        return RoomInfoMapDao::getInstance()->getRoomIdByNotGuildId($guildId);
    }

    /**
     * @param $roomId
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomName($roomId)
    {
        $data = $this->getModel($roomId)->where(['id' => $roomId])->field('room_name')->find();
        return empty($data) ? '' : $data['room_name'];
    }

    /**
     * @param $roomId
     * @return mixed
     */
    public function getOwnerUserId($roomId)
    {
        return $this->getModel($roomId)->where(array("id" => $roomId))->value("user_id");
    }


    /**
     * @info 获取有效的公会房间ids for roomType
     * @param $roomType int [25 26 24 60] 60是所有热门房间不限制房间类型
     * @return array
     */
    public function getOnlineGuildRoomIdsForRoomType($roomType)
    {
        $guildId = 0;
        $roomIds = RoomInfoMapDao::getInstance()->getRoomIdByNotGuildId($guildId);
        if ($roomType != 60) {
            return $this->getShowRoomidsForRoomType($roomIds, $roomType);
        }
        return $this->getShowRoomids($roomIds);
    }

    /**
     * @info 获取有效的个人房间ids for roomType
     * @return array
     */
    public function getOnlinePersonRoomIdsForRoomType($roomType)
    {
        if ($roomType == 9999) {
            return PersonQueryRoomModelCache::getInstance()->getOnlinePersonRoomsAll();
        }
        return PersonQueryRoomModelCache::getInstance()->getOnlinePersonRoomsForRoomType($roomType);
    }


    /**
     * @param $roomId
     * @return int
     */
    public function getRoomGuildStatus($roomId)
    {
        if (empty($roomId)) {
            return 0;
        }
        $where['id'] = $roomId;
        $model = $this->getModel($roomId)->where($where)->field('guild_id')->find();
        if (empty($model)) {
            return 0;
        }
        $data = $model->toArray();
        return isset($data['guild_id']) ? $data['guild_id'] : 0;
    }

    /**
     * @info 获取mua金刚位房间id
     * @return array
     */
    public function getMuaRoomKingKongRoomIds()
    {
        return GuildQueryRoomModelCache::getInstance()->getMuaRoomKingKongForCache();
    }

    /**
     * @info get RefreshRoomId
     * @return array
     */
    public function getMuaRefreshRoomIds()
    {
        $result = [];
        $kingkong = GuildQueryRoomModelCache::getInstance()->getMuaRoomKingKongForCache();
        $recommend = GuildQueryRoomModelCache::getInstance()->getMuaRecommendForCache();
        $result = array_merge($result, $kingkong);
        $result = array_merge($result, $recommend);
        $result = array_unique(array_filter($result));
        return $result;
    }

    /**
     * @info 过滤不展示的roomid
     * @param $roomIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShowRoomidsForRoomType($roomIds, $roomType)
    {
        if (empty($roomIds)) {
            return [];
        }
        $models = $this->getModels($roomIds);
        $result = [];
        foreach ($models as $model) {
            $where = [];
            $where[] = ['id', 'in', $model->list];
            $where[] = ['is_show', '=', 1];
            $where[] = ['is_hide', '=', 0];
            $where[] = ['is_block', '=', 0];
            $where[] = ['room_type', '=', $roomType];
            $itemData = $model->model->where($where)->column('id');
            if (!empty($itemData)) {
                foreach ($itemData as $key => $value) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }


    /**
     * @info 过滤不展示的roomid
     * @param $roomIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShowRoomids($roomIds)
    {
        if (empty($roomIds)) {
            return [];
        }
        $models = $this->getModels($roomIds);
        $result=[];
        foreach($models as $model) {
            $where=[];
            $where[]=['id', 'in', $model->list];
            $where[] = ['is_show', '=', 1];
            $where[] = ['is_hide', '=', 0];
            $where[] = ['is_block', '=', 0];
            $itemData = $model->model->where($where)->column('id');
            if (!empty($itemData)){
                foreach($itemData as $key=>$value){
                    $result[]=$value;
                }
            }
        }
        return $result;
    }

    /**
     * @param $guildId
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function getGuildRoomCount($guildId)
    {
        $roomInfoData = RoomInfoMapDao::getInstance()->getRoomIdByGuildId($guildId);
        return count($roomInfoData);
    }


}








