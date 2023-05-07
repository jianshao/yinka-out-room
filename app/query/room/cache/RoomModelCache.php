<?php

namespace app\query\room\cache;

use app\domain\room\model\RoomModel;
use app\common\RedisCommon;

class RoomModelCache
{
    protected $pk = 'id';
    protected static $instance;


    public function __construct(array $data = [])
    {
        $this->redis = RedisCommon::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomModelCache();
        }
        return self::$instance;
    }

    /**
     * @param $data
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
        $ret->tags = $data['tags'];
        $ret->mode = $data['mode'];
        $ret->password = $data['password'];
        $ret->lock = $data['lock'];
        $ret->createTime = $data['createTime'];
        $ret->isFreeMic = $data['isFreeMic'];
        $ret->isOpenHeartValue = $data['isOpenHeartValue'];
        $ret->fansNotices = $data['fansNotices'];
        $ret->liveType = $data['liveType'];
        $ret->visitorNumber = $data['visitorNumber'];
        $ret->socitayId = $data['socitayId'];
        $ret->visitorExternNumber = $data['visitorExternNumber'];
        $ret->hxRoom = $data['hxRoom'];
        $ret->swRoom = $data['swRoom'];
        $ret->visitorUsers = $data['visitorUsers'];
        $ret->roomChannel = $data['roomChannel'];
        $ret->backgroundImage = $data['backgroundImage'];
        $ret->isHot = $data['isHot'];
        $ret->isWheat = $data['isWheat'];
        $ret->tagImage = $data['tagImage'];
        $ret->guildIndexId = $data['guildIndexId'];
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
            'tags' => $model->tags,
            'mode' => $model->mode,
            'password' => $model->password,
            'lock' => $model->lock,
            'createTime' => $model->createTime,
            'isFreeMic' => $model->isFreeMic,
            'isOpenHeartValue' => $model->isOpenHeartValue,
            'fansNotices' => $model->fansNotices,
            'liveType' => $model->liveType,
            'visitorNumber' => $model->visitorNumber,
            'socitayId' => $model->socitayId,
            'visitorExternNumber' => $model->visitorExternNumber,
            'hxRoom' => $model->hxRoom,
            'swRoom' => $model->swRoom,
            'visitorUsers' => $model->visitorUsers,
            'roomChannel' => $model->roomChannel,
            'backgroundImage' => $model->backgroundImage,
            'isHot' => $model->isHot,
            'isWheat' => $model->isWheat,
            'tagImage' => $model->tagImage,
            'guildIndexId' => $model->guildIndexId,
        ];
        return $ret;
    }


    private function getCacheKey($id)
    {
        return sprintf("%sid:%s", CachePrefix::$roomModelCache, $id);
    }


    public function store(int $id, RoomModel $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = $this->modelToData($data);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }

    public function storeZero(int $id, RoomModel $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = $this->modelToData($data);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, 60);
        return $re;
    }

    public function findRoom(int $id)
    {
        if (empty($id)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $data = $this->redis->hGetAll($cacheKey);
        if (empty($data)) {
            return false;
        }
        return $this->dataToModel($data);
    }


    public function findRoomTypeByRoomId($id)
    {
        if (empty($id)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $data = $this->redis->hGetAll($cacheKey);
        if (empty($data)) {
            return false;
        }
        return $this->dataToModel($data);
    }


    public function clearCache($roomId){
        $cacheKey=$this->getCacheKey($roomId);
        return $this->redis->del($cacheKey);
    }

}