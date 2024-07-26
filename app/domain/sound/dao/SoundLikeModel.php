<?php

namespace app\domain\sound\dao;

use app\core\mysql\ModelDao;

class SoundLikeModel extends ModelDao
{
    protected $table = 'zb_sound_like';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SoundLikeModel();
        }
        return self::$instance;
    }
}
