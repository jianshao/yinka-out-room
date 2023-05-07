<?php


namespace app\query\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\FriendModel;

class FriendModelDao extends ModelDao
{
    protected $table = 'zb_user_friend';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new FriendModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new FriendModel();
        $model->userId = $data['user_id'];
        $model->friendId = $data['friend_id'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    /**
     * @param $userId
     * @param $userIdEd
     * @return FriendModel|null
     */
    public function loadFriendModel($userId, $friendId) {
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'friend_id' => $friendId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 获取好友数量
     *
     * @param $userId
     * @return int
     *
     */
    public function getFriendCount($userId) {
        $where = ['user_id' => $userId];
        return $this->getModel($userId)->where($where)->count();
    }

    public function getList($userId, $offset, $count) {
        $datas = $this->getModel($userId)
            ->where(['user_id' => $userId])
            ->order('create_time desc')
            ->limit($offset, $count)
            ->select()
            ->toArray();

        $ret = [];
        if (!empty($datas)){
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }

        return $ret;
    }

    public function findMapByFriendIds($userId, $userIds)
    {
        $ret = [];
        $datas = $this->getModel($userId)->where([['friend_id', 'in', $userIds]])->select()->toArray();
        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[$model->friendId] = $model;
        };
        return $ret;
    }
}