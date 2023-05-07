<?php

namespace app\query\gift\dao;

use app\core\mysql\ModelDao;
use app\domain\gift\model\GiftModel;

class GiftWallModelDao extends ModelDao
{
    protected $serviceName = 'userSlave';
    protected $table = 'zb_user_gift_wall';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GiftWallModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new GiftModel();
        $model->kindId = $data['giftid'];
        $model->count = $data['count'];
        return $model;
    }

    /**
     * 加载userId用户的礼物墙
     * 
     * @param userId: 哪个用户
     * @return: map<giftId, GiftModel>
     */
    public function loadGiftWallByUserId($userId) {
        $ret = [];
        $datas = $this->getModel($userId)->where(['uid' => $userId])->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $model = $this->dataToModel($data);
                $ret[$model->kindId] = $model;
            }
        }
        return $ret;
    }
}


