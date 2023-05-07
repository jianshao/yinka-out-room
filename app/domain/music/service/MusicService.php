<?php


namespace app\domain\music\service;


use app\domain\exceptions\FQException;
use app\domain\music\dao\MusicModelDao;
use app\domain\music\dao\RoomMusicModelDao;
use app\domain\music\dao\UserMusicModelDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\UserModelDao;
use think\facade\Log;

class MusicService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MusicService();
        }
        return self::$instance;
    }

    public function getMusicList($offset, $count)
    {
        $where = [
            'status' => 1
        ];
        return MusicModelDao::getInstance()->listMusic($where, $offset, $count, 'download_num desc');
    }

    public function addMusicToUser($userId, $musicIds)
    {
        if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
            throw new FQException('此用户不存在', 500);
        }

        $songModels = MusicModelDao::getInstance()->findSongModelsByMusicIds($musicIds);
        if (empty($songModels)) {
            throw new FQException('当前音乐不存在', 500);
        }

        $count = UserMusicModelDao::getInstance()->userMusicCount($userId);

        if ($count >= 100) {
            throw new FQException('收藏最多100首', 500);
        }

        $datas = [];
        $timestamp = time();
        foreach ($songModels as $songModel) {
            $datas[] = [
                'music_id' => $songModel->id,
                'user_id' => $userId,
                'ctime' => $timestamp
            ];
        }
        UserMusicModelDao::getInstance()->addUserMusic($userId, $datas);

        Log::info(sprintf('MusicService::addMusicToUser ok userId=%d musicIds=%s',
            $userId, implode(',', $musicIds)));
    }

    public function removeMusicFromUser($userId, $musicId)
    {
        //查询当前用户ID是否存在
        if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
            throw new FQException('此用户不存在', 500);
        }

        UserMusicModelDao::getInstance()->delUserMusic($userId,$musicId);

        Log::info(sprintf('MusicService::removeMusicFromUser ok userId=%d musicId=%d',
            $userId, $musicId));
    }

    public function addPlayCount($musicId)
    {
        $model = MusicModelDao::getInstance()->findByMusicId($musicId);
        if ($model == null) {
            throw new FQException('当前音乐不存在', 500);
        }

        MusicModelDao::getInstance()->incPlayNum($musicId);

        Log::info(sprintf('MusicService::addPlayCount ok musicId=%d', $musicId));
    }

    public function searchMusic($search, $offset, $count)
    {
        $where = [
            ['song_name', 'like', "%$search%"],
            ['status', '=', 1]
        ];
        return MusicModelDao::getInstance()->listMusic($where, $offset, $count, 'download_num desc');
    }

    public function addMusicToRoom($roomId, $musicIds)
    {
        //查询当前房间ID是否存在
        if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
            throw new FQException('此房间不存在', 500);
        }

        $songModels = MusicModelDao::getInstance()->findSongModelsByMusicIds($musicIds);
        if (empty($songModels)) {
            throw new FQException('当前音乐不存在', 500);
        }

        $count = RoomMusicModelDao::getInstance()->getRoomMusicCount($roomId);

        if ($count >= 100) {
            throw new FQException('收藏最多100首', 500);
        }

        $timestamp = time();
        foreach ($songModels as $songModel) {
            RoomMusicModelDao::getInstance()->addRoomMusic($roomId, $songModel->id, $timestamp);
        }

        Log::info(sprintf('MusicService::addMusicToRoom ok roomId=%d musicIds=%s',
            $roomId, json_encode($musicIds)));
    }

    public function removeMusicFromRoom($userId, $roomId, $musicId)
    {
        //查询当前房间ID是否存在
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('此房间不存在', 500);
        }

        $manager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
        if ($manager == null && $userId != $roomModel->userId) {
            throw new FQException('用户权限不足', 500);
        }

        $re = RoomMusicModelDao::getInstance()->delRoomMusic($roomId, $musicId);
        if (empty($re)) {
            throw new FQException('操作失败', 500);
        }

        Log::info(sprintf('MusicService::removeMusicFromRoom ok userId=%d roomId=%d musicId=%d',
            $userId, $roomId, $musicId));
    }
}