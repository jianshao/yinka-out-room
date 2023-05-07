<?php


namespace app\domain\room\cache;


use app\common\CacheRedis;
use app\query\room\cache\CachePrefix;
use app\query\room\model\QueryRoom;

class GuildQueryRoomModelCache
{
    protected $pk = 'id';
    protected static $instance;


    public function __construct(array $data = [])
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GuildQueryRoomModelCache();
        }
        return self::$instance;
    }

    public function store(int $id, QueryRoom $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = $data->toJson();
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }

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

}




















