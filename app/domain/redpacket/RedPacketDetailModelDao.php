<?php


namespace app\domain\redpacket;


use app\core\mysql\ModelDao;

class RedPacketDetailModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_redpackets_detail';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketDetailModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new RedPacketDetailModel();
        $model->id = $data['id'];
        $model->redPacketId = $data['red_id'];
        $model->getUserId = $data['get_uid'];
        $model->getTime = $data['get_time'];
        $model->beanCount = $data['get_coin'];
        $model->isGet = $data['is_get'];
        $model->createTime = $data['created_time'];
        $model->updateTime = $data['updated_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'red_id' => $model->redPacketId,
            'get_uid' => $model->getUserId,
            'get_time' => $model->getTime,
            'get_coin' => $model->beanCount,
            'is_get' => $model->isGet,
            'created_time' => $model->createTime,
            'updated_time' => $model->updateTime
        ];
    }

    public function findById($id) {
        $data = $this->getModel()->where(['id' => $id])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function createModel($model) {
        $data = $this->modelToData($model);
        $id = $this->getModel()->insertGetId($data);
        $model->id = $id;
    }

    public function updateModelForGet($model) {
        $res = $this->getModel()->where(['id' => $model->id, 'is_get' => 0])->update([
            'get_uid' => $model->getUserId,
            'get_time' => $model->getTime,
            'is_get' => 1,
            'updated_time' => $model->updateTime
        ]);
        if (empty($res)) {
            return false;
        }
        return true;
    }

    # 红包领取人信息列表
    public function getRedDetailModels($redId) {
        $ret = [];
        $datas = $this->getModel()->where(['red_id' => $redId])->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data){
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}