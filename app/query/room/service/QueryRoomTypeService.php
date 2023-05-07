<?php


namespace app\query\room\service;

use app\domain\room\model\RoomTypeModel;
use app\query\room\cache\RoomTypeModelCache;
use app\query\room\dao\QueryRoomTypeDao;
use app\utils\CommonUtil;

class QueryRoomTypeService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryRoomTypeService();
        }
        return self::$instance;
    }


    /**
     * @info 获取roomtype缓存
     * @param $id
     * @return RoomTypeModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomTypeForCache($id)
    {
        if (empty($id)) {
            return new RoomTypeModel();
        }
        $cacheModel = RoomTypeModelCache::getInstance()->find($id);
        if ($cacheModel !== false) {
            return $cacheModel;
        }
        $model = QueryRoomTypeDao::getInstance()->loadRoomType($id);
        if ($model === null) {
            return new RoomTypeModel();
        }
        RoomTypeModelCache::getInstance()->store($id, $model);
        return $model;
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomTypeForPidOne()
    {
        $roomTypes = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere(['pid', 'in', '1,2'], 1);
        $result = [];
        foreach ($roomTypes as $roomType) {
            $itemData['type_id']=$roomType->id;
            $itemData['room_mode']=$roomType->roomMode;
            $itemData['tab_icon']=CommonUtil::buildImageUrl($roomType->tabIcon);
            $result[] = $itemData;
        }
        return $result;
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomTypeForPidHundred(){
        $roomTypes = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere(['pid', '=', 100], 1);
        $result = [];
        foreach ($roomTypes as $roomType) {
            $itemData['type_id']=$roomType->id;
            $itemData['room_mode']=$roomType->roomMode;
            $itemData['tab_icon']=CommonUtil::buildImageUrl($roomType->tabIcon);
            $result[] = $itemData;
        }
        return $result;
    }

}