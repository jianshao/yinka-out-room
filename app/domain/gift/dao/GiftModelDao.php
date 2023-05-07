<?php

namespace app\domain\gift\dao;

use app\core\mysql\ModelDao;
use app\domain\gift\model\GiftModel;
use app\utils\TimeUtil;


class GiftModelDao extends ModelDao
{
    protected $table = 'zb_pack';
    protected $pk = 'id';
    protected $serviceName = 'userMaster';

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

    /**
     * 保存用户的道具
     * @param userId: 哪个用户
     * @param giftModel: 礼物对象
     */
    public function createGift($userId, $model) {
        $data = $this->modelToData($model);
        $data['user_id'] = $userId;
        $this->getModel($userId)->insert([
            'user_id' => $userId,
            'gift_id' => $model->kindId,
            'pack_num' => $model->count,
            'update_time' => TimeUtil::timeToStr($model->updateTime),
            'createtime' => $model->createTime,
        ]);
    }

    public function incGift($userId, $kindId, $count, $timestamp) {
        assert($count >= 0);
        return $this->getModel($userId)->where(['user_id' => $userId, 'gift_id' => $kindId])->inc('pack_num', $count)->update();
    }

    public function decGift($userId, $kindId, $count, $timestamp) {
        assert($count >= 0);
        $whereStr = sprintf('user_id=%d and gift_id=%d and pack_num >= %d', $userId, $kindId, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->dec('pack_num', $count)->update();
    }
}


