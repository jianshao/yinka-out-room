<?php

namespace app\domain\guild\cache;

use app\common\RedisCommon;
use app\domain\guild\model\HomeHotRoomModel;
use app\domain\guild\model\RecreationHotRoomModel;
use app\domain\room\dao\RecreationHotModelDao;
use app\domain\room\service\RoomService;

//娱乐页人气推荐位
class RecreationHotRoomCache
{
    // 房间id
    private $roomid = 0;
    private $roomHot = null;

    private $isLock = 0;
    private $hotSum = 0;

    public function __construct($roomid = null)
    {
        if ($roomid !== null) {
            $this->setRoomId($roomid);
            $this->initRoomHot();
        }
    }


    public function modelToData()
    {
        $roomHotData=$this->roomHot->modelToData();
        return [
            'room_id' => $this->roomid,
            'room_hot' => $roomHotData,
            'is_lock' => $this->isLock,
            'hot_sum' => $this->hotSum,
        ];
    }

    /**
     * @return int
     */
    public function getLock()
    {
        return $this->isLock;
    }

    public function getRoomId()
    {
        return $this->roomid;
    }

    public function getHotSum()
    {
        return strval($this->hotSum);
    }

    public function getHotSumTpl()
    {
        return $this->hotSum;
    }

    public function getHotSumTplStr()
    {
        return formatNumber($this->hotSum);
    }

    public function setRoomId($roomid)
    {
        $this->roomid = $roomid;
    }


    /**
     * @info 初始化房间热度值
     */
    public function initRoomHot()
    {
        $data = RecreationHotModelDao::getInstance()->getHotValueAll($this->roomid);
        $this->roomHot = new RecreationHotRoomModel();
        $this->roomHot->dataToModel($data);
        $this->hotSum = $this->roomHot->getSumHot();
    }

    /**
     * @return HomeHotRoomModel|null
     */
    public function getRoomHot()
    {
        return $this->roomHot;
    }


//    private function initOriginHot()
//    {
//        $model = RoomModelDao::getInstance()->findRoomTypeByRoomIdForCache($this->roomid);
//        return $model->visitorExternNumber;
//    }

    /**
     * @info 初始化该房间是否锁房
     */
    public function initLockRoom()
    {
        $redis_connect = RedisCommon::getInstance()->getRedis();
        $roomLockKey = $this->getRoomLockKey();
        $this->isLock = $redis_connect->sIsMember($roomLockKey, $this->roomid);
    }


    private function getRoomLockKey()
    {
        return CachePrefix::$roomLock; //锁房key
    }


    /**
     * @info 房间是否为空
     * @return bool  //true 空   fasle 非空
     */
    public function isEmptyRoom()
    {
        return RoomService::getInstance()->isEmptyRoom($this->roomid);
    }


}


