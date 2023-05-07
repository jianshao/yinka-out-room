<?php


namespace app\query\room\cache;


use app\domain\room\dao\RoomTypeModelDao;

class PersonQueryRoomModelCache
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
            self::$instance = new PersonQueryRoomModelCache();
        }
        return self::$instance;
    }

    private function getOnlinePersonRoomsKey($roomType)
    {
        return sprintf("%s:%s", CachePrefix::$onlinePersonRooms, $roomType);
    }

    public function getOnlinePersonRoomsForRoomType($roomType)
    {
        $cacheKey = $this->getOnlinePersonRoomsKey($roomType);
        return $this->redis->Smembers($cacheKey);
    }


    public function getOnlinePersonRoomsAll()
    {
        $roomTypeIds = RoomTypeModelDao::getInstance()->getPersonTypeIds();
        $cacheKeys = [];
        foreach ($roomTypeIds as $roomType) {
            $cacheKeys[] = $this->getOnlinePersonRoomsKey($roomType);
        }
        $roomIds = $this->redis->sUnion(
            $cacheKeys[0],
            ...$cacheKeys
        );
        return $roomIds;
    }


}




















