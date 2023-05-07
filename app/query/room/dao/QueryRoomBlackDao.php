<?php


namespace app\query\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomBlackModel;

class QueryRoomBlackDao extends ModelDao
{
    protected $table = 'zb_room_black';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'roomMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $ret = new RoomBlackModel();
        $ret->id = $data['id'] ?? 0;
        $ret->userId = $data['user_id'];
        $ret->roomId = $data['room_id'];
        $ret->ctime = $data['ctime'];
        $ret->longTime = $data['longtime'];
        $ret->type = $data['type'];
        return $ret;
    }

    public function modelToData(RoomBlackModel $model)
    {
        return [
            'user_id' => $model->userId,
            'room_id' => $model->roomId,
            'ctime' => $model->ctime,
            'longtime' => $model->longTime,
            'type' => $model->type,
        ];
    }

    /**
     * @param $roomId
     * @param $type
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function getTotalForRooomIdType($roomId, $type)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['type', '=', $type];
        return $this->getModel($roomId)->where($where)->count();
    }


    /**
     * @param $roomId
     * @param $type
     * @param $offset
     * @param $count
     * @return RoomBlackModel[]|array
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomBlackModelList($roomId, $type, $offset, $count)
    {
        $order = 'ctime desc';
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['type', '=', $type];
        $object=$this->getModel($roomId)->where($where)->order($order)
            ->limit($offset, $count)
            ->select();
        if ($object===null){
            return [];
        }
        $datas=$object->toArray();
        $result=[];
        foreach($datas as $data){
            $result[]=$this->dataToModel($data);
        }
        return $result;
    }

}