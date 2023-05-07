<?php


namespace app\query\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\FansModel;

class FansModelDao extends ModelDao
{
    protected $table = 'zb_user_fans';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

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
     * load最新的一条粉丝关注
     * return AttentionModel
     * */
    public function loadNewFansModel($userId) {
        $data = $this->getModel($userId)->where(array("user_id" => $userId))->limit(1)->order('create_time desc')->find();
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

    public function getFollowCount($userId) {
        return $this->getModel($userId)->where([
            'user_id' => $userId,
        ])->count();
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
}