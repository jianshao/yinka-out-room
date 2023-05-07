<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomManagerModel;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;

class RoomManagerModelDao extends ModelDao
{
    protected $table = 'zb_room_manager';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomManagerModelDao();
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

    public function modelToData(RoomManagerModel $model)
    {
        return [
            'rooms_id' => $model->roomId,
            'user_id' => $model->userId,
            'creattime' => TimeUtil::timeToStr($model->createTime),
            'type' => $model->type
        ];
    }

    /**
     * @param RoomManagerModel $model
     * @throws \app\domain\exceptions\FQException
     */
    public function saveModel(RoomManagerModel $model)
    {
        $data = $this->modelToData($model);
        $this->getModel($this->shardingId)->save($data);
    }

    /**
     * @param RoomManagerModel $model
     * @throws \app\domain\exceptions\FQException
     */
    public function updateModel(RoomManagerModel $model)
    {
        $data = $this->modelToData($model);
        $where = [
            'rooms_id' => $model->roomId,
            'user_id' => $model->userId,
        ];
        $this->getModel($this->shardingId)->update($data, $where);

    }

    /**
     * @param $roomId
     * @param $userId
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function removeManager($roomId, $userId)
    {
        $where = [
            'rooms_id' => $roomId,
            'user_id' => $userId
        ];
        return $this->getModel($this->shardingId)->where($where)->delete();
    }

    /**
     * @param $roomId
     * @return array
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

    /**
     * @param $roomId
     * @param $userId
     * @return RoomManagerModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findManagerByUserId($roomId, $userId) {
        $object = $this->getModel($this->shardingId)->where([
            'rooms_id' => $roomId,
            'user_id' => $userId
        ])->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param $roomId
     * @param $userId
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function viewUserIdentity($roomId, $userId) {
        if (RoomModelDao::getInstance()->getOwnerUserId($roomId) == $userId) {
            return 2;
        }
        $managerModel = $this->findManagerByUserId($roomId, $userId);
        $userIdentity = 0;
        if ($managerModel != null) {
            $userIdentity = ArrayUtil::safeGet(RoomManagerModel::$viewType, $managerModel->type, 0);
        }
        return $userIdentity;
    }

    /**
     * @param $roomId
     * @throws \app\domain\exceptions\FQException
     */
    public function getTotalForRoomId($roomId)
    {
        return $this->getModel($this->shardingId)->where([
            'rooms_id' => $roomId
        ])->count();
    }

}