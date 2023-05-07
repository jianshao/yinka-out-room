<?php

namespace app\domain\room\model;

class RoomTypeCacheModel extends RoomTypeModel
{
    private static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomTypeCacheModel();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new RoomTypeCacheModel();
        $model->id = $data['id'];
        $model->pid = $data['pid'];
        $model->roomMode = $data['room_mode'];
        $model->createTime = $data['creat_time'];
        $model->modeType = $data['mode_type'];
        $model->status = $data['status'];
        $model->isSort = $data['is_sort'];
        $model->micCount = $data['micnum'];
        $model->tabIcon = $data['tab_icon'];
        $model->isShow = $data['is_show'];
        $model->type = $data['type'];
        return $model;
    }


    public function modelToData($model)
    {
        $data = [
            'id' => $model->id,
            'pid' => $model->pid,
            'room_mode' => $model->roomMode,
            'create_time' => $model->createTime,
            'mode_type' => $model->modeType,
            'status' => $model->status,
            'is_sort' => $model->isSort,
            'mic_count' => $model->micCount,
            'tab_icon' => $model->tabIcon,
            'is_show' => $model->isShow,
            'type' => $model->type,
        ];

        return $data;
    }
}