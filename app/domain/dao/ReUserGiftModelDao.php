<?php


namespace app\domain\dao;


use app\core\mysql\ModelDao;


class ReUserGiftModelDao extends ModelDao
{
    protected $table = 'yyht_re_user_gift';
    protected $pk = 'id';
    protected $serviceName = 'biMaster';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ReUserGiftModelDao();
        }
        return self::$instance;
    }

    public function getReUserGift($userId, $reType, $maxCount){
        return $this->getModel($userId)->field(
            'id,gift_id'
        )->where([
            'is_obtain' => 1,
            'type' => $reType,
            'user_id' => $userId
        ])->order('created asc')->limit($maxCount)->select()->toArray();
    }

    public function updateGiftStatus($userId, $id){
        return $this->getModel($userId)->where(['id' => $id])->update(
            [
                'is_obtain' => 2,
                'updated' => time(),
                'update_user' => $userId
            ]
        );
    }
}