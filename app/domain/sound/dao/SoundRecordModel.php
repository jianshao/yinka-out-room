<?php

namespace app\domain\sound\dao;

use app\core\mysql\ModelDao;

class SoundRecordModel extends ModelDao
{
    protected $table = 'zb_sound_record';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SoundRecordModel();
        }
        return self::$instance;
    }

}
