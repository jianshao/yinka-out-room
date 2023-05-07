<?php


namespace app\domain\room\dao;

use app\core\mysql\ModelDao;

class RoomReportModelDao extends ModelDao {

    protected $table = 'zb_report';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomReportModelDao();
        }
        return self::$instance;
    }

    public function loadRoomReport($userId, $roomId){
        return $this->getModel($userId)->where([
            'userid' => $userId,
            'report_roomid' => $roomId,
            'status' => '0'
        ])->find();
    }

    public function addRoomReport($userId, $roomId, $content, $time){
        return $this->getModel($userId)->insert([
            'userid' => $userId,
            'report_roomid' => $roomId,
            'report_content' => $content,
            'report_time' => $time
        ]);
    }
}