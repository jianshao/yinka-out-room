<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomBlackModel;

class RoomBlackModelDao extends ModelDao
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
     * @param RoomBlackModel $model
     * @return int|string
     */
    public function storeModel(RoomBlackModel $model)
    {
        if (empty($model->roomId)) {
            return 0;
        }
        $data = $this->modelToData($model);
        return $this->getModel($model->roomId)->insertGetId($data);
    }

    /**
     * @param $roomId
     * @param $userId
     * @param $type
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function existsForRoomUserType($roomId, $userId, $type)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['user_id', '=', $userId];
        $where[] = ['type', '=', $type];
        return $this->getModel($roomId)->where($where)->column('id');
    }

    /**
     * @param $roomId
     * @param $userId
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function removeForRoomUserType($roomId, $userId, $type)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['user_id', '=', $userId];
        $where[] = ['type', '=', $type];
        return $this->getModel($roomId)->where($where)->delete();
    }

    /**
     * @param $roomId
     * @param $userId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function removeForRoomUser($roomId, $userId)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['user_id', '=', $userId];
        return $this->getModel($roomId)->where($where)->delete();
    }

    /**
     * @param $userId
     * @param $roomId
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadDataForUserIdRoomId($userId, $roomId)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['user_id', '=', $userId];
        $object = $this->getModel($roomId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }

    /**
     * @param $userId
     * @param $roomId
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadDataForUserIdsRoomIdList($userIds, $roomId)
    {
        $where[] = ['room_id', '=', $roomId];
        $where[] = ['user_id', 'in', $userIds];
        $object = $this->getModel($roomId)->where($where)->select();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }

    /**
     * @return RoomBlackModel[]|array
     * @throws \app\domain\exceptions\FQException
     */
    public function loadAllTempModelList()
    {
        $models = $this->getServiceModels();
        $result = [];
        $where[] = ['longtime', '<>', -1];
        foreach ($models as $model) {
            $itemModel = $model->where($where)->select();
            if ($itemModel !== null) {
                foreach ($itemModel->toArray() as $itemData) {
                    $model = $this->dataToModel($itemData);
                    $result[] = $model;
                }
            }
        }
        return $result;
    }

}