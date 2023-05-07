<?php


namespace app\domain\census\dao;
use app\core\mysql\ModelDao;
use think\Model;

class CensusWholewheatModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_room_wholewheat_census';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CensusWholewheatModelDao();
        }
        return self::$instance;
    }

    public function modelToData($model) {
        return [
            'send_uid'    => $model->sendUid,
            'room_id'     => $model->roomId,
            'gift_id'     => $model->giftId,
            'count'       => $model->count,
            'gift_value'  => $model->giftValue,
            'create_time' => $model->createTime,
            'ext'         => $model->ext,
        ];
    }


    public function saveRecord($model) {
        $data = $this->modelToData($model);
        $this->getModel()->insert($data);
    }
}