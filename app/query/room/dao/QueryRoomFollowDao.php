<?php


namespace app\query\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomFollowModel;

class QueryRoomFollowDao extends ModelDao
{
    protected $table = 'zb_room_follow';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new RoomFollowModel();
        $ret->userId = $data['user_id'];
        $ret->roomId = $data['room_id'];
        $ret->createTime = $data['creat_time'];
        return $ret;
    }

    /**
     * @param $userId
     * @param $offset
     * @param $length
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomIdsForUserId($userId,$offset,$length){
        $where[]=['user_id','=', $userId];
        $result = $this->getModel($userId)
            ->where($where)
            ->limit($offset, $length)
            ->order('creat_time desc')
            ->column("room_id");
        if (empty($result)){
            return null;
        }
        return $result;
    }


    /**
     * @param $userId
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function getRoomIdsCountForUserId($userId){
        return $this->getModel($userId)->where(['user_id' => $userId])->count();
    }


}