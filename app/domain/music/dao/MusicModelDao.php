<?php


namespace app\domain\music\dao;


use app\core\mysql\ModelDao;
use app\domain\music\model\MusicModel;

class MusicModelDao extends ModelDao {
    protected $table = 'zb_member_song';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';


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

    public function findByMusicId($musicId) {
        $data = $this->getModel()->where(['id' => $musicId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
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

    public function listMusic($where, $offset, $count, $order) {

        $datas = $this->getModel()->where($where)
            ->order($order)
            ->limit($offset, $count)
            ->select()
            ->toArray();
        $count = $this->getModel()->where($where)->count();
        $ret = [];
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return [$ret, $count];
    }

    public function incPlayNum($musicId) {
        $this->getModel()->where([
            'id' => $musicId
        ])->inc('download_num')->update();
    }

    public function saveSong($data){
        return $this->getModel()->save($data);
    }

    public function updateSong($music_id, $user_id, $data){
        $where[] = ['id','in',$music_id];
        $where[] = ['user_id','=',$user_id];
        $this->getModel()->where($where)->update($data);
    }
}