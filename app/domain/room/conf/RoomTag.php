<?php

namespace app\domain\room\conf;

use app\domain\Config;
use app\domain\room\model\RoomTagModel;
use app\utils\ArrayUtil;
use think\facade\Log;


/**
 * @info room_mode配置数据配置项
 * Class RoomTag(zb_languageroom.tag_image)
 * @package app\domain\room
 */
class RoomTag
{
    protected static $instance;
    private $dataMap = [];

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomTag();
            self::$instance->loadFromJson();
            Log::info(sprintf('RoomTagLoaded count=%d', count(self::$instance->dataMap)));
        }
        return self::$instance;
    }


    /**
     * @info 根据Id查找
     * @param $modeId : 类型ID
     * @param $modeId
     * @return RoomTagModel|mixed
     */
    public function findRoomTag($modeId)
    {
        $data = ArrayUtil::safeGet($this->dataMap, $modeId);
        if (!empty($data)) {
            return $data;
        }
        return new RoomTagModel();
    }

    /**
     * 获取所有mode数据
     *
     * @return array <modeId, RoomTagModel>
     */
    public function getDataMap()
    {
        return $this->dataMap;
    }


    /**
     * @param $tagId
     * @param string $source
     * @return string
     */
    public function getSourceImageForId($tagId,$source=""){
        $tagData = $this->findRoomTag($tagId);
        switch ($source){
            case "mua":
                return $tagData->imageMua;
            case "yinlian":
                return $tagData->imageYinlian;
            default:
                return $tagData->imageMua;
        }
    }

    private function loadFromJson()
    {
        $jsonObj = Config::getInstance()->getRoomTagConf();
        $dataMap = [];
        foreach ($jsonObj as $modeConf) {
            $mode = new RoomTagModel();
            $mode->fromJson($modeConf);
            $dataMap[$mode->id] = $mode;
        }
        $this->dataMap = $dataMap;
    }
}


