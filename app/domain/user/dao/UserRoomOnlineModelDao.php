<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\UserRoomOnlineModel;

class UserRoomOnlineModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_online_room_census';
    protected $pk = 'id';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserRoomOnlineModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        return new UserRoomOnlineModel($data['id'],$data['user_id'], $data['room_id'], $data['date'], $data['online_second']);
    }

    public function loadRoomOnline($userId, $roomId, $date, $type = 1)
    {
        $where = ['user_id' => $userId, 'room_id' => $roomId, 'date' => $date];
        $data = $this->getModel($userId)->where($where)->find();
        if (empty($data)) {
            return null;
        }
        if ($type == 2) {
            return $data;
        }
        return $this->dataToModel($data);
    }

    public function addData($model)
    {
        $data['user_id'] = $model->userId;
        $data['room_id'] = $model->roomId;
        $data['date'] = $model->date;
        $data['online_second'] = $model->onlineSecond;
        $model->id = $this->getModel($model->userId)->insertGetId($data);
    }

    public function updateOnlineSecond($userId, $id, $onlineSecond)
    {
        assert($onlineSecond >= 0);
        $where = ['id' => $id];
        $this->getModel($userId)->where($where)->update(['online_second' => $onlineSecond]);
    }
}