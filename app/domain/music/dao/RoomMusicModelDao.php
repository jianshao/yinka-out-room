<?php


namespace app\domain\music\dao;


use app\core\mysql\ModelDao;


class RoomMusicModelDao extends ModelDao
{
    protected $table = 'zb_room_music';
    protected $pk = 'id';
    protected $serviceName = 'roomMaster';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomMusicModelDao();
        }
        return self::$instance;
    }

    public function getRoomMusicList($roomId){
        $data=$this->getModel($roomId)->where(['room_id' => $roomId])->column('music_id');
        return array_values($data);
    }

    public function getRoomMusicCount($roomId){
        return $this->getModel($roomId)->where(['room_id' => $roomId])->count();
    }

    public function addRoomMusic($roomId, $musicId, $timestamp){
        $datas[] = [
            'music_id' => $musicId,
            'room_id' => $roomId,
            'ctime' => $timestamp
        ];
        $this->getModel($roomId)->insert($datas);
    }

    public function delRoomMusic($roomId, $musicId){
        return $this::getInstance()->getModel($roomId)->where([
            'room_id' => $roomId,
            'music_id' => $musicId
        ])->delete();
    }
}