<?php


namespace app\domain\pay\dao;


use app\core\mysql\ModelDao;
use app\domain\pay\model\PayChannelModel;

class PayChannelModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_paychannel';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PayChannelModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new PayChannelModel();
        $model->id = $data['id'];
        $model->pid = $data['pid'];
        $model->content = $data['content'];
        $model->check = $data['check'];
        $model->type = $data['type'];
        return $model;
    }

    public function findByChannelId($channelId) {
        $data = $this->getModel()->where(['id' => $channelId])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function payChannelList($type = 1) {
        $datas = $this->getModel()->where([
            'check' => 1,
            'type' => $type
        ])->select()->toArray();

        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}