<?php


namespace app\query\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomManagerModel;
use app\utils\TimeUtil;

class QueryRoomManagerDao extends ModelDao
{
    protected $table = 'zb_room_manager';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';
    protected $shardingId = 0;

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
        $ret = new RoomManagerModel();
        $ret->roomId = $data['rooms_id'];
        $ret->userId = $data['user_id'];
        $ret->createTime = TimeUtil::strToTime($data['creattime']);
        $ret->type = $data['type'];
        return $ret;
    }

    /**
     * @param RoomManagerModel $model
     * @return array
     */
    public function modelToData(RoomManagerModel $model)
    {
        return [
            'rooms_id'=>$model->roomId,
            'user_id'=>$model->userId,
            'creattime'=>$model->createTime,
            'type'=>$model->type
        ];
    }

    /**
     * @param $roomId
     * @return RoomManagerModel[]|array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadAllManager($roomId)
    {
        $ret = [];
        $datas = $this->getModel($this->shardingId)->where(['rooms_id' => $roomId])->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function loadManagers($roomId, $offset, $count)
    {
        $ret = [];
        $datas = $this->getModel($this->shardingId)->where(['rooms_id' => $roomId])->limit($offset, $count)->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function getManagerCount($roomId)
    {
        return $this->getModel($this->shardingId)->where(['rooms_id' => $roomId])->count();
    }

    /**
     * @param $roomId
     * @param $userId
     * @return RoomManagerModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findManagerByUserId($roomId, $userId)
    {
        $data = $this->getModel($this->shardingId)->where([
            'rooms_id' => $roomId,
            'user_id' => $userId
        ])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }


    /**
     * @param $userId
     * @return array
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRoomIdsByUserId($userId)
    {
        $where[] = ['user_id', '=', $userId];
        $object = $this->getModel($this->shardingId)->where($where)->column('rooms_id');
        if (empty($object)) {
            return [];
        }
        return $object;
    }
}