<?php


namespace app\domain\room\dao;

use app\core\mysql\ModelDao;


class RoomCheckSwitchDao extends ModelDao
{
    protected $table = 'zb_roomcheck_switch';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomCheckSwitchDao();
        }
        return self::$instance;
    }

    public function isAudit($type)
    {
        $model = $this->getModel()->where(['type' => $type])->find();
        if (empty($model)) {
            return 0;
        }
        return (int)$model->getAttr('is_open');
    }
}
