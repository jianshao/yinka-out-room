<?php


namespace app\query\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\AttentionModel;

class AttentionModelDao extends ModelDao
{
    protected $table = 'zb_user_attention';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AttentionModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new AttentionModel();
        $model->userId = $data['user_id'];
        $model->attentionId = $data['attention_id'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    /**
     * @param $userId
     * @param $attentionId
     * @return AttentionModel|null
     */
    public function loadAttention($userId, $attentionId) {
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'attention_id' => $attentionId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 获取关注数量
     *
     * @param $userId
     * @return int
     */
    public function getAttentionCount($userId) {
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

    public function findMapByAttentionIds($userId, $userIds)
    {
        $ret = [];
        $datas = $this->getModel($userId)->where([['user_id', '=', $userId]])->select()->toArray();
        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[$model->attentionId] = $model;
        };
        return $ret;
    }
}