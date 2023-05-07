<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\FansModel;

class FansModelDao extends ModelDao
{
    protected $table = 'zb_user_fans';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new FansModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new FansModel();
        $model->userId = $data['user_id'];
        $model->fansId = $data['fans_id'];
        $model->createTime = $data['create_time'];
        $model->isRead = $data['is_read'];
        return $model;
    }

    /**
     * @param $userId
     * @param $userIdEd
     * @return FansModel|null
     */
    public function loadFansModel($userId, $fansId) {
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'fans_id' => $fansId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 获取未读信息数量
     */
    public function getUnreadMsgCount($userId) {
        return $this->getModel($userId)->where(['user_id' => $userId, 'is_read' => 0])->count();
    }

    public function addFans($userId, $fansId, $createTime, $isRead=0) {
        $data['user_id'] = $userId;
        $data['fans_id'] = $fansId;
        $data['create_time'] = $createTime;
        $data['is_read'] = $isRead;
        $this->getModel($userId)->save($data);
    }

    public function delFans($userId, $fansId) {
        $this->getModel($userId)->where(['user_id' => $userId, 'fans_id'=>$fansId])->delete();
    }

    /**
     * 所有未读信息重置成已读
     */
    public function updateAllUnreadMsgStatus($userId) {
        $data['is_read'] = 1;
        $this->getModel($userId)->where(array("user_id" => $userId))->update($data);
    }

    /**
     * 未读信息重置成已读
     */
    public function updateUnreadMsgStatus($userId, $fansId) {
        $data['is_read'] = 1;
        $this->getModel($userId)->where(array("user_id" => $userId,"fans_id"=>$fansId))->update($data);
    }
}