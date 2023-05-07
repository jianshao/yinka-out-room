<?php


namespace app\query\music\dao;


use app\core\mysql\ModelDao;
use app\domain\music\model\MusicModel;

class MusicModelDao extends ModelDao {
    protected $table = 'zb_member_song';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MusicModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new MusicModel();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->songer = $data['songer'];
        $model->songName = $data['song_name'];
        $model->status = $data['status'];
        $model->songTime = $data['song_time'];
        $model->createTime = $data['create_time'];
        $model->songUrl = $data['song_url'];
        $model->examineUid = $data['examine_uid'];
        $model->examineTime = $data['examine_time'];
        $model->downloadNum = $data['download_num'];
        $model->type = $data['type'];
        $model->sale = $data['sale'];
        $model->upname = $data['upname'];
        $model->size = $data['size'];
        return $model;
    }

    public function findSongModelsByMusicIds($musicIds) {
        $ret = [];
        $datas = $this->getModel()->where([['id', 'in', $musicIds]])->select()->toArray();
        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[] = $model;
        }
        return $ret;
    }

    public function userSongCount($userId){
        $where[] = ['user_id','=',$userId];
        return $this->getModel()->where($where)->count();
    }

    public function userSongList($userId, $page, $pagesize){
        $where[] = ['user_id','=',$userId];
        return $this->getModel()->where($where)->order('id', 'desc')->page($page, $pagesize)->select();
    }
}