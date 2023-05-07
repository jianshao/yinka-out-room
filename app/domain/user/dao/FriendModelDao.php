<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\FriendModel;

class FriendModelDao extends ModelDao
{
    protected $table = 'zb_user_friend';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

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

    public function addFriend($userId, $friendId, $time) {
        $data['user_id'] = $userId;
        $data['friend_id'] = $friendId;
        $data['create_time'] = $time;
        $this->getModel($userId)->save($data);
    }

    public function delFriend($userId, $friendId) {
        $this->getModel($userId)->where(['user_id' => $userId, 'friend_id'=>$friendId])->delete();
    }
}