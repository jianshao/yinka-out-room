<?php

namespace app\query\dao;

use app\core\mysql\ModelDao;
use app\domain\gift\model\GiftModel;
use app\utils\TimeUtil;


class GiftModelDao extends ModelDao
{
    protected $table = 'zb_pack';
    protected $pk = 'id';
    protected $serviceName = 'userSlave';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GiftModelDao();
        }
        return self::$instance;
    }

    public function modelToData($model) {
        return [
            'gift_id' => $model->kindId,
            'pack_num' => $model->count,
            'update_time' => TimeUtil::timeToStr($model->updateTime),
            'createtime' => $model->createTime,
        ];
    }

    public function dataToModel($data) {
        $model = new GiftModel();
        $model->kindId = $data['gift_id'];      //礼物id
        $model->count = $data['pack_num'];      //背包数量
        $model->updateTime = TimeUtil::strToTime($data['update_time']);  //更新时间
        $model->createTime = $data['createtime'];       //创建时间
        return $model;
    }

    /**
     * 加载userId用户所有的礼物
     * 
     * @param userId: 哪个用户
     * @return: list<GiftModel>
     */
    public function loadAllGiftByUserId($userId) {
        $ret = [];
        $datas = $this->getModel($userId)->where(['user_id' => $userId])->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}


