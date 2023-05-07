<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

/**
 * im私聊逻辑埋点
 */
class ImChatPointDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_im_point';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ImChatPointDao();
        }
        return self::$instance;
    }

    public function insertData($data) {
        return $this->getModel()->insert($data);
    }
}


