<?php


namespace app\query\room\cache;


use app\domain\room\model\RoomTypeCacheModel;
use app\domain\room\model\RoomTypeModel;

class RoomTypeModelCache
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
            self::$instance = new RoomTypeModelCache();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new RoomTypeModel();
        $model->id = (int)$data['id'];
        $model->pid = (int)$data['pid'];
        $model->roomMode = $data['room_mode'];
        $model->createTime = (int)$data['create_time'];
        $model->modeType = (int)$data['mode_type'];
        $model->status = (int)$data['status'];
        $model->isSort = (int)$data['is_sort'];
        $model->micCount = (int)$data['mic_count'];
        $model->tabIcon = $data['tab_icon'];
        $model->isShow = (int)$data['is_show'];
        $model->type = (int)$data['type'];
        return $model;
    }


    private function getCacheKey($id)
    {
        return sprintf("%sid:%s", CachePrefix::$roomTypeModelPrefix, $id);
    }


    public function store(int $id, RoomTypeModel $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = RoomTypeCacheModel::getInstance()->modelToData($data);
        $this->redis->multi();
        $this->redis->del($cacheKey);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        $this->redis->exec();
        return $re;
    }


//storeZero
    public function storeZero(int $id, RoomTypeModel $data)
    {
        if (empty($id) || empty($data)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($id);
        $arrData = RoomTypeCacheModel::getInstance()->modelToData($data);
        $this->redis->del($cacheKey);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, 60);
        return $re;
    }


    public function find(int $id)
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


}