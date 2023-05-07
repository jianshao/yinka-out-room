<?php

namespace app\domain\room\dao;

use app\core\mysql\ModelDao;
use app\domain\room\model\RoomTypeModel;

class RoomTypeModelDao extends ModelDao
{
    protected $table = 'zb_room_mode';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomTypeModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new RoomTypeModel();
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

    /**
     * @param $id
     * @return RoomTypeModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomType($id)
    {
        $data = $this->getModel($this->shardingId)->where(['id' => $id])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function getGuildTypeIds()
    {
        $where[] = ['pid', '=', 100];
        $where[] = ['status', '=', 1];
        $where[] = ['is_show', '=', 1];
        return $this->getModel($this->shardingId)->where($where)->column("id");
    }

    public function getPersonTypeIds()
    {
        $where[] = ['pid', 'in', '1,2'];
        $where[] = ['status', '=', 1];
        $where[] = ['is_show', '=', 1];
        return $this->getModel($this->shardingId)->where($where)->column("id");
    }


    public function getPidMapForList($ids)
    {
        $where[] = ['id', 'in', $ids];
        return $this->getModel($this->shardingId)->where($where)->column('pid', 'id');
    }

    public function loadPidForId($id)
    {
        $where['id'] = $id;
        return $this->getModel($this->shardingId)->where($where)->value('pid');
    }
}