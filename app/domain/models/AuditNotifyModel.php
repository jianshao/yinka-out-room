<?php

namespace app\domain\models;

use app\core\mysql\ModelDao;

class AuditNotifyModel extends ModelDao
{
    protected $table = 'zb_tencent_audit_notify';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AuditNotifyModel();
        }
        return self::$instance;
    }

}
