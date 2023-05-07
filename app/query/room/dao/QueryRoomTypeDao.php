<?php


namespace app\query\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomTypeModel;

class QueryRoomTypeDao extends ModelDao
{
    protected $table = 'zb_room_mode';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryRoomTypeDao();
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

    /**
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomTypeByPidWhere($pidWhere, $isSort=0){
        $where[] = $pidWhere;
        $where[] = ['status', '=', 1];
        $where[] = ['is_show', '=', 1];
        if ($isSort){
            $model = $this->getModel($this->shardingId)->where($where)->order('is_sort desc')->select();
        }else{
            $model = $this->getModel($this->shardingId)->where($where)->select();
        }

        if ($model === null) {
            return null;
        }
        $datas = $model->toArray();
        $result = [];
        foreach ($datas as $data) {
            $result[] = $this->dataToModel($data);
        }
        return $result;
    }

    /**
     * @param $roomInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomTypeGuild($roomType)
    {
        $tmp = [];
        $where_type[] = ['pid', 'notIn', '1,2'];
        $where_type[] = ['status', '=', 1];
        $where_type[] = ['is_show', '=', 1];
        $roomTypeData = $this->getModel($this->shardingId)->field('id as type_id,room_mode as type_name')->where($where_type)->select()->toArray();
        foreach ($roomTypeData as $key => $value) {
            if ($roomType == $value['type_id']) {
                $tmp['type_id'] = $value['type_id'];
                $tmp['type_name'] = $value['type_name'];
                $tmp['is_use'] = 1;
                unset($roomTypeData[$key]);
                unset($value);
            } else {
                $roomTypeData[$key]['is_use'] = 0;
            }
        }
        if ($tmp) {
            array_unshift($roomTypeData, $tmp);
        }
        return $roomTypeData;
    }

    /**
     * @param $roomType
     * @return array
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomTypePerson($roomType)
    {
        $tmp = [];
        $where_type[] = ['pid', 'in', '1,2'];
        $where_type[] = ['status', '=', 1];
        $where_type[] = ['is_show', '=', 1];
        $roomTypeData = $this->getModel($this->shardingId)->field('id as type_id,room_mode as type_name')->where($where_type)->select()->toArray();
        foreach ($roomTypeData as $key => $value) {
            if ($roomType == $value['type_id']) {
                $tmp['type_id'] = $value['type_id'];
                $tmp['type_name'] = $value['type_name'];
                $tmp['is_use'] = 1;
                unset($roomTypeData[$key]);
                unset($value);
            } else {
                $roomTypeData[$key]['is_use'] = 0;
            }
        }
        if ($tmp) {
            array_unshift($roomTypeData, $tmp);
        }
        return $roomTypeData;
    }

    public function roomtypeForPid($room_type)
    {
        return $this->getModel($this->shardingId)->where(['id' => $room_type])->value('pid');
    }

}