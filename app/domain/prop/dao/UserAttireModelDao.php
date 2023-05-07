<?php


namespace app\domain\prop\dao;


use app\core\mysql\ModelDao;

class UserAttireModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_attire_user';
    protected $pk = 'id';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserAttireModelDao();
        }
        return self::$instance;
    }

    /**
     * 加载userId用户所有的道具
     *
     * @param userId: 哪个用户
     * @return: list<PropModel>
     */
    public function loadAllByUserId($userId) {
        return $this->getModel($userId)->where(['user_id' => $userId])->select()->toArray();
    }
}