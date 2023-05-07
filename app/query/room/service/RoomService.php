<?php


namespace app\query\room\service;


use app\query\redpacket\dao\RedPacketDetailModelDao;
use app\query\redpacket\dao\RedPacketModelDao;

class RoomService
{
    protected static $instance;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomService();
        }
        return self::$instance;
    }

    public function hasRedPacket($roomId, $userId)
    {
        $haveRed = RedPacketModelDao::getInstance()->findByRoomId($roomId);
        if (!empty($haveRed)) {
            $isget = RedPacketDetailModelDao::getInstance()->getModel()->where([
                ['red_id', 'in', $haveRed],
                ['get_uid', '=', $userId]
            ])->column('id');

            if (count($haveRed) != count($isget)) {
                return 1;
            }
        }
        return 0;
    }
}