<?php

namespace app\query\room\dao;

use app\core\mysql\ModelDao;
use app\domain\room\model\RoomModel;

class QueryRoomDao extends ModelDao
{
    protected $table = 'zb_languageroom';
    protected $pk = 'id';
    protected $serviceName = 'roomSlave';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryRoomDao();
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
        $ret->tagId = $data['tag_id'];
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
            'tag_id' => $model->tagId,
        ];
        return $ret;
    }

    /**
     * @param $roomId
     * @return RoomModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoom($roomId)
    {
        $data = $this->loadRoomData($roomId);
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
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
        $model = $this->getModel($roomId)->where(['id' => $roomId])->find();
        if ($model === null) {
            return null;
        }
        return $model->toArray();
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


}








