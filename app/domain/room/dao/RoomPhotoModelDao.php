<?php


namespace app\domain\room\dao;


use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\room\model\RoomPhotoModel;
use think\facade\Log;

class RoomPhotoModelDao extends ModelDao
{
    protected $table = 'zb_room_photo';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomPhotoModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new RoomPhotoModel();
        $ret->photoId = $data['id'];
        $ret->userId = $data['user_id'];
        $ret->roomId = $data['room_id'];
        $ret->image = $data['image'];
        $ret->status = $data['status'];
        $ret->giftId = $data['gift_id'];
        $ret->createTime = $data['create_time'];
        return $ret;
    }

    public function loadPhoto($photoId) {
        $data = $this->getModel($this->shardingId)->where(['id' => $photoId])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function loadNormalPhotos($roomId, $photoId, $length) {
        $where = [
            ['id', '>', $photoId],
            ['room_id', '=', $roomId],
            ['status', '=', RoomPhotoModel::$STATUS_CHECK_PASS]
        ];
        $datas = $this->getModel($this->shardingId)->where($where)->order('create_time acs')->limit($length)->select()->toArray();

        $ret = [];
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function loadAllPhotos($roomId, $photoId, $length) {
        $where = [
            ['id', '>', $photoId],
            ['room_id', '=', $roomId]
        ];
        $datas = $this->getModel($this->shardingId)->where($where)->order('create_time acs')->limit($length)->select()->toArray();

        $ret = [];
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function addRoomPhoto($roomPhoto) {
        $data = [
            'user_id' => $roomPhoto->userId,
            'room_id' => $roomPhoto->roomId,
            'image' => $roomPhoto->image,
            'gift_id' => $roomPhoto->giftId,
            'status' => $roomPhoto->status,
            'create_time' => $roomPhoto->createTime
        ];
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    public function removeRoomPhotos($photoIds) {
        $this->getModel($this->shardingId)->where([['id', 'in', $photoIds]])->delete();
    }

    public function removeAllRoomPhoto($roomId) {
        $where = [
            ['room_id', '=', $roomId],
            ['status', '<>', RoomPhotoModel::$STATUS_CHECKING]
        ];
        $this->getModel($this->shardingId)->where($where)->delete();
    }

    public function updatePhotoStatus($photoId, $status) {
        $this->getModel($this->shardingId)->where(['id' => $photoId])->update([
            'status' => $status
        ]);
    }

    public function getPhotoCount($roomId){
        return $this->getModel($this->shardingId)->where(['room_id' => $roomId])->count();
    }

    public function isUnLockPhoto($roomId, $userId, $photoId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $this->updateUnLockPhoto($redis, $userId);
        return $redis->sIsMember(sprintf('unlock_room_photo_%d_%d', $roomId, $userId), $photoId);
    }

    public function addUnLockPhoto($roomId, $userId, $photoId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $this->updateUnLockPhoto($redis, $userId);
        $redis->sAdd(sprintf('unlock_room_photo_%d_%d', $roomId, $userId),  $photoId);
    }

    public function getUnLockPhotoList($roomId, $userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $this->updateUnLockPhoto($redis, $userId);
        return $redis->sMembers(sprintf('unlock_room_photo_%d_%d', $roomId, $userId));
    }

    private function updateUnLockPhoto($redis, $userId){
        # 把之前存的用户解锁的照片分房间存储
        $photoIds = $redis->sMembers('unlock_room_photo:'.$userId);
        if (empty($photoIds)){
            return;
        }

        $datas = $this->getModel($this->shardingId)->where([['id', 'in', $photoIds]])->select()->toArray();
        Log::info(sprintf('RoomPhotoModelDao::updateUnLockPhoto userId=%d photoIds=%s, datas=%s',
            $userId, json_encode($photoIds), json_encode($datas)));
        foreach ($datas as $data) {
            $redis->sAdd(sprintf('unlock_room_photo_%d_%d', $data['room_id'], $userId),  $data['id']);
        }
        $redis->del('unlock_room_photo:'.$userId);
    }
}