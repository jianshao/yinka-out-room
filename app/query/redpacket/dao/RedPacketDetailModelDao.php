<?php


namespace app\query\redpacket\dao;

use app\core\mysql\ModelDao;
use app\domain\redpacket\RedPacketDetailModel;

class RedPacketDetailModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
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