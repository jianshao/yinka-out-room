<?php

namespace app\domain\room\dao;

use app\core\mysql\ModelDao;
use app\domain\room\model\PhotoWallModel;

class PhotoWallModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_photo_wall';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PhotoWallModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $data
     */
    public function dataToModel($data)
    {
        $ret = new PhotoWallModel();
        $ret->id = $data['id'];
        $ret->image = $data['image'];
        $ret->roomId = $data['room_id'];
        $ret->failureTime = $data['failure_time'];
        $ret->isVip = $data['is_vip'];
        return $ret;
    }

    /**
     * @param PhotoWallModel $model
     * @return array
     */
    public function modelToData(PhotoWallModel $model)
    {
        return [
            'id' => $model->id,
            'image' => $model->image,
            'room_id' => $model->roomId,
            'failure_time' => $model->failureTime,
            'is_vip' => $model->isVip,
        ];
    }

    /**
     * @param PhotoWallModel $model
     * @return int|string
     * @throws \app\domain\exceptions\FQException
     */
    public function storeModel(PhotoWallModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }

    public function getlistForByMode($room_mode)
    {
        if (empty($room_mode)) {
            return [];
        }
        $where[] = ['status', '=', 2];
        $where[] = ['is_del', '=', 1];
        $where[] = ['room_mode', '=', $room_mode];
        $datas = $this->getModel()->field('id,image,is_vip')->where($where)->order('is_vip desc')->select()->toArray();
        $ret = [];
        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[] = $model;
        }
        return $ret;
    }

    /**
     * @param $id
     * @return PhotoWallModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForId($id){
        if (empty($id)) {
            return null;
        }
        $object = PhotoWallModelDao::getInstance()->getModel()->where(array('id' => $id))->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param $pid
     * @param $status
     * @param $start
     * @return PhotoWallModel
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelOneWithPidStatusStart($pid, $status, $start)
    {
        $object = PhotoWallModelDao::getInstance()->getModel()->where(array('room_mode' => $pid, 'status' => $status, 'start' => $start))->find();
        if ($object === null) {
            return new PhotoWallModel;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param $pid
     * @param $status
     * @param $start
     * @return PhotoWallModel
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelOneWithPidStatusStartDesc($pid, $status, $start)
    {
        $object = PhotoWallModelDao::getInstance()->getModel()->where(array('room_mode' => $pid, 'status' => $status, 'start' => $start))->order("id desc")->find();
        if ($object === null) {
            return new PhotoWallModel;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param $pid
     * @param $isVip
     * @param $status
     * @return mixed
     * @throws \app\domain\exceptions\FQException
     */
    public function loadDefaultImageWithPidStatus($pid,$isVip,$status){
        return PhotoWallModelDao::getInstance()->getModel()->where(['room_mode' => $pid, 'is_vip' => $isVip, 'status' => $status])->value('image');
    }
}