<?php


namespace app\query\room\cache;


use app\query\room\model\QueryRoom;

class GuildQueryRoomModelCache
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
            self::$instance = new GuildQueryRoomModelCache();
        }
        return self::$instance;
    }

    public function store(int $id, QueryRoom $model)
    {
        if (empty($id)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = $model->toJson();
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }

    /**
     * @param $roomId
     * @return QueryRoomModel|false
     */
    public function find($roomId)
    {
        if (empty($roomId)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($roomId);
        $cacheData = $this->redis->hGetAll($cacheKey);
        if (empty($cacheData)) {
            return false;
        }
        $model = new QueryRoom();
        $model->fromJson($cacheData);
        return $model;
    }

    private function getCacheKey($id)
    {
        return sprintf("%s:%s", CachePrefix::$GuildQueryRoomModelCache, $id);
    }

//    private function getOnlinePersonRoomsKey($roomType)
//    {
//        return sprintf("%s:%s", CachePrefix::$onlinePersonRooms, $roomType);
//    }


    public function getOnlineGuildRoomsKey($roomType)
    {
        return sprintf("%s:%s", CachePrefix::$onlineGuildRooms, $roomType);
    }


    /**
     * @Info 获取在线的房间用户数统计
     * @param $roomId
     * @return false|int
     */
    public function getOnlineRoomUserCount($roomId)
    {
        return $this->redis->HLEN('go_room_' . $roomId);
    }


    /**
     * @param int $id
     * @return bool
     */
    public function lockRoom(int $id)
    {
        if (empty($id)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        return $this->redis->hMSet($cacheKey, ['lock' => 1]);
    }

    public function unlockRoom(int $id)
    {
        if (empty($id)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        return $this->redis->hMSet($cacheKey, ['lock' => 0]);
    }

    public function getOnlineGuildRoomsAll()
    {
        $roomTypeIds = [20, 21, 22, 23, 24, 25, 26];
        $cacheKeys = [];
        foreach ($roomTypeIds as $roomType) {
            $cacheKeys[] = $this->getOnlineGuildRoomsKey($roomType);
        }
        $roomIds = $this->redis->sUnion(
            $cacheKeys[0],
            $cacheKeys[1],
            $cacheKeys[2],
            $cacheKeys[3],
            $cacheKeys[4],
            $cacheKeys[5],
            $cacheKeys[6]
        );
        return $roomIds;
    }

    public function getOnlineGuildRoomsForRoomType($roomType)
    {
        $cacheKey = $this->getOnlineGuildRoomsKey($roomType);
        return $this->redis->Smembers($cacheKey);
    }

    /**
     * @return string
     */
    private function getMuaRoomKingKongKey()
    {
        return CachePrefix::$muaRoomKingKong;
    }

    /**
     * @info mua 新厅推荐
     * @return string
     */
    private function getMuaRecommendKey()
    {
        return CachePrefix::$muaRecommend;
    }

    /**
     * @info 获取mua金刚位房间id
     * @return array
     */
    public function getMuaRoomKingKongForCache()
    {
        $cacheKey = $this->getMuaRoomKingKongKey();
        return $this->redis->sMembers($cacheKey);
    }

    /**
     * @info 获取mua新人推荐房间id
     * @return array
     */
    public function getMuaRecommendForCache()
    {
        $cacheKey = $this->getMuaRecommendKey();
        return $this->redis->sMembers($cacheKey);
    }
}




















