<?php


namespace app\domain\music\dao;

use app\core\mysql\ModelDao;

class UserMusicModelDao extends ModelDao
{
    protected $table = 'zb_member_music';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserMusicModelDao();
        }
        return self::$instance;
    }

    /**
     * 新增音乐
     * @param $userId
     * @param $datas
     */
    public function addUserMusic($userId, $datas){
        $this->getModel($userId)->insertAll($datas);
    }

    /**
     * 删除音乐
     * @param $userId
     * @param $musicId
     * @throws \Exception
     */
    public function delUserMusic($userId,$musicId){
        $this->getModel($userId)->where([
            'user_id' => $userId,
            'music_id' => $musicId
        ])->delete();
    }

    public function userMusicCount($userId)
    {
        return $this->getModel($userId)->where(['user_id' => $userId])->count();
    }
}