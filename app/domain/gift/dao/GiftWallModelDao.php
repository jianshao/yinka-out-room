<?php

namespace app\domain\gift\dao;

use app\core\mysql\ModelDao;
use app\domain\gift\model\GiftModel;

class GiftWallModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
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
     * 礼物墙增加
     * @param $userId
     * @param $kindId
     * @param $count
     */
    public function incGift($userId, $kindId, $count) {
        assert($count >= 0);

        if($this->hasGift($userId, $kindId)){
            $this->getModel($userId)->where(['uid' => $userId, 'giftid' => $kindId])->inc('count', $count)->update();
        }else{
            $data = [
                "uid" => $userId,
                "giftid" => $kindId,
                "count" => $count
            ];
            $this->getModel($userId)->insert($data);
        }
    }

    /**
     * 礼物墙是否有该礼物
     * @param $userId
     * @param $kindId
     */
    public function hasGift($userId, $kindId) {
        $gifts =  $this->getModel($userId)->where(['uid' => $userId, 'giftid' => $kindId])->select()->toArray();
        return !empty($gifts);
    }
}


