<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;


class RecallSmsInfoDao extends ModelDao
{
    protected $table = 'zb_recall_sms_info_20211111';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RecallSmsInfoDao();
        }
        return self::$instance;
    }


    public function storeData($userId, $platform,$action) {
        $data = [
            'user_id' => $userId,
            'platform' => $platform,
            'action' => $action,
            'create_time'=>time()
        ];
        $this->getModel($userId)->insert($data);
    }

}