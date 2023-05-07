<?php


namespace app\query\music\dao;


use app\core\mysql\ModelDao;


class RoomMusicModelDao extends ModelDao
{
    protected $table = 'zb_room_music';
    protected $pk = 'id';
    protected $serviceName = 'roomSlave';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomMusicModelDao();
        }
        return self::$instance;
    }

    public function getMusicIdsByRoomId($roomId)
    {
        $datas = $this->getModel($roomId)->field('music_id')->where(['room_id', '=', $roomId])->order('ctime desc')->select()->toArray();
        $musicIds = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $musicIds[] = $data['music_id'];
            }
        }

        return $musicIds;
    }

    public function checkLikes($roomId, $musicIds)
    {
        $where = [
            ['room_id', '=', $roomId],
            ['music_id', 'in', $musicIds]
        ];
        $datas = $this->getModel($roomId)->field('music_id')->where($where)->select()->toArray();
        $ret = [];

        if (!empty($datas)) {
            foreach ($datas as $data) {
                $musicId = $data['music_id'];
                $ret[$musicId] = $musicId;
            }
        }
        return $ret;
    }
}