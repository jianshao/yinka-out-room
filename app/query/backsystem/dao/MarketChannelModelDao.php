<?php


namespace app\query\backsystem\dao;


use app\core\mysql\ModelDao;

class MarketChannelModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
    protected $table = 'zb_market_channel';
    public static $instance;
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MarketChannelModelDao();
        }
        return self::$instance;
    }

    public function getOne($where) {
        return $this->getModel()->where($where)->find();
    }

    public function getRoomIdByInviteCode($inviteCode) {
        $where = [];
        $where['invitcode'] = $inviteCode;
        $where['status'] = 1;
        return $this->getModel()->where($where)->value('room_id');
    }
}