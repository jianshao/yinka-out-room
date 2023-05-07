<?php


namespace app\query\music\dao;

use app\core\mysql\ModelDao;

class UserMusicModelDao extends ModelDao
{
    protected $table = 'zb_member_music';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserMusicModelDao();
        }
        return self::$instance;
    }

    public function getMusicIdsByUserId($userId)
    {
        $datas = $this->getModel($userId)->field('music_id')->where([['user_id', '=', $userId]])->order('ctime desc')->select()->toArray();
        $musicIds = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $musicIds[] = $data['music_id'];
            }
        }

        return $musicIds;
    }

    public function checkLikes($userId, $musicIds)
    {
        $where = [
            ['user_id', '=', $userId],
            ['music_id', 'in', $musicIds]
        ];
        $datas = $this->getModel($userId)->field('music_id')->where($where)->select()->toArray();
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