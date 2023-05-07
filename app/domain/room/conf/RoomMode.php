<?php

namespace app\domain\room\conf;

use app\domain\Config;
use app\domain\room\model\RoomModeModel;
use app\utils\ArrayUtil;
use think\facade\Log;


/**
 * @info room_mode配置数据配置项
 * Class RoomMode(roomType)
 * @package app\domain\room
 */
class RoomMode
{
    protected static $instance;
    private $dataMap = [];

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomMode();
            self::$instance->loadFromJson();
            Log::info(sprintf('RoomModeLoaded count=%d', count(self::$instance->dataMap)));
        }
        return self::$instance;
    }


    /**
     * @info 根据Id查找
     * @param $modeId : 类型ID
     * @param $modeId
     * @return RoomModeModel|mixed
     */
    public function findRoomMode($modeId)
    {
        $data = ArrayUtil::safeGet($this->dataMap, $modeId);
        if (!empty($data)) {
            return $data;
        }
        return new RoomModeModel();
    }


    /**
     * @param $tagId
     * @param string $source
     * @return string
     */
    public function getSourceImageForId($typeId,$source=""){
        $typeId=intval($typeId);
        $tagData = $this->findRoomMode($typeId);
        switch ($source){
            case "mua":
                return $tagData->imageMua;
            case "yinlian":
                return $tagData->imageYinlian;
            default:
                return $tagData->imageMua;
        }
    }


    /**
     * 获取所有mode数据
     *
     * @return array <ModeId, RoomModeModel>
     */
    public function getDataMap()
    {
        return $this->dataMap;
    }

    private function loadFromJson()
    {
        $jsonObj = Config::getInstance()->getRoomModeConf();
        $dataMap = [];
        foreach ($jsonObj as $modeConf) {
            $mode = new RoomModeModel();
            $mode->fromJson($modeConf);
            $dataMap[$mode->id] = $mode;
        }
        $this->dataMap = $dataMap;
    }
}


