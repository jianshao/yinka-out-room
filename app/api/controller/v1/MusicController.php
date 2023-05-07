<?php
/*
 * 房间管理类
 */
namespace app\api\controller\v1;
use app\domain\exceptions\FQException;
use app\query\music\dao\RoomMusicModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\query\music\dao\MusicModelDao;
use app\query\music\dao\UserMusicModelDao;
use app\domain\music\service\MusicService;
use app\domain\user\dao\AccountMapDao;
use app\utils\CommonUtil;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use function Composer\Autoload\includeFile;

require "../vendor/james-heinrich/getid3/getid3/getid3.php";

class MusicController extends ApiBaseController
{
    public function viewMusic($musicModel, $getID3, $star=null) {
        $songUrl = substr($musicModel->songUrl, strpos($musicModel->songUrl,'/'));
        $ThisFileInfo = @$getID3->analyze($musicModel->songUrl);
        $playtimeSeconds = @$ThisFileInfo['playtime_seconds'] ? @$ThisFileInfo['playtime_seconds'] : 0;
        $ret = [
            'id' => $musicModel->id,
            'songer' => $musicModel->songer,
            'song_name' => $musicModel->songName,
            'song_url' => CommonUtil::buildImageUrl($songUrl),
            'download_num' => $musicModel->downloadNum,
            'song_time' => (int)$playtimeSeconds,
            'type' => $musicModel->type > 1 ? '原唱' : '伴奏',
            'upname' => $musicModel->upname
        ];
        if ($star != null) {
            $ret['star'] = $star;
        }
        return $ret;
    }

    public function viewMusicNew($musicModel, $getID3, $star=null) {
        $ret = $this->viewMusic($musicModel, $getID3, $star);
        $ret['size'] = $musicModel->size;
        $ret['sizeMb'] = (string)empty($musicModel->size) ? '3.8MB' : $musicModel->size . 'MB';
        return $ret;
    }

    /**
     * 用户音乐收藏列表
     * @param $token   token值
     */
    public function likeList()
    {
        //获取数据
        $userId = intval(Request::param('user_id'));
        if (!$userId) {
            return rjson([], 500, '参数错误');
        }

        try {
            $musics = [];
            if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
                throw new FQException('此用户不存在', 500);
            }
            $musicIds = UserMusicModelDao::getInstance()->getMusicIdsByUserId($userId);
            if (!empty($musicIds)){
                $musicModels = MusicModelDao::getInstance()->findSongModelsByMusicIds($musicIds);

                $getID3 = new \getID3(); //实例化类
                foreach ($musicModels as $musicModel) {
                    $musics[] = $this->viewMusic($musicModel, $getID3);
                }
            }

            return rjson($musics);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户音乐上传列表
     */
    public function getList()
    {
        //获取数据
        $userId = intval(Request::param('user_id'));
        $page = intval(Request::param('page'));
        if (!$userId || !$page) {
            return rjson([], 500, '参数错误');
        }

        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;

        try {
            list($musicModels, $total) = MusicService::getInstance()->getMusicList($offset, $pageNum);
            // 查询
            $musicIds = [];
            foreach ($musicModels as $musicModel) {
                $musicIds[] = $musicModel->id;
            }
            $likeMusicIdMap = UserMusicModelDao::getInstance()->checkLikes($userId, $musicIds);
            $musics = [];
            $getID3 = new \getID3(); //实例化类
            foreach ($musicModels as $musicModel) {
                $star = array_key_exists($musicModel->id, $likeMusicIdMap) ? 1 : 0;
                $musics[] = $this->viewMusic($musicModel, $getID3, $star);
            }
            //分页数据
            $totalPage = ceil($total / $pageNum);
            return rjson([
                'list' => $musics,
                'pageInfo' => [
                    'page' => $page,
                    'pagNum' => $pageNum,
                    'totalPage' => $totalPage
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**添加音乐操作
     * @param $token    token值
     * @param $music_id    音乐id
     * @param $user_id  用户id
     */
    public function addMusic()
    {
        //获取数据
        $musicIdStr = Request::param('music_id');
        $userId = Request::param('user_id');

        if (!$musicIdStr || !$userId) {
            return rjson([], 500, '参数错误');
        }
        try {
            $musicIds = explode(',', $musicIdStr);
            MusicService::getInstance()->addMusicToUser($userId, $musicIds);
            return rjson([],200,'添加成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**移除音乐操作
     * @param $token    token值
     * @param $user_id  用户id
     */
    public function delMusic()
    {
        //获取数据
        $musicId = Request::param('music_id');
        $userId = Request::param('user_id');
        if (!$musicId || !$userId) {
            return rjson([], 500, '参数错误');
        }

        try {
            MusicService::getInstance()->removeMusicFromUser($userId, $musicId);
            return rjson([],200,'移除成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**搜索音乐管理
     * @param $token    token值
     * @param $search   搜索值
     * @param $page     分页值
     */
    public function searchMusic()
    {
        //获取数据
        $userId = intval(Request::param('user_id'));
        $search = Request::param('search');
        $page = intval(Request::param('page'));
        if (!$userId || !$search || !$page) {
            return rjson([], 500, '参数错误');
        }
        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;

        try {
            list($musicModels, $total) = MusicService::getInstance()->searchMusic($search, $offset, $pageNum);
            // 查询
            $musicIds = [];
            foreach ($musicModels as $musicModel) {
                $musicIds[] = $musicModel->id;
            }
            $likeMusicIdMap = UserMusicModelDao::getInstance()->checkLikes($userId, $musicIds);
            $musics = [];
            $getID3 = new \getID3(); //实例化类
            foreach ($musicModels as $musicModel) {
                $star = array_key_exists($musicModel->id, $likeMusicIdMap) ? 1 : 0;
                $musics[] = $this->viewMusic($musicModel, $getID3, $star);
            }
            //分页数据
            $totalPage = ceil($total / $pageNum);
            return rjson([
                'list' => $musics,
                'pageInfo' => [
                    'page' => $page,
                    'pagNum' => $pageNum,
                    'totalPage' => $totalPage
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 音乐播放量
     */
    public function addPlay()
    {
        //获取数据
        $musicId = Request::param('music_id');
        if (!$musicId) {
            return rjson([], 500, '参数错误');
        }

        try {
            MusicService::getInstance()->addPlayCount($musicId);
            rjson([], 200, '操作成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新版房间添加音乐
     * @return mixed
     */
    public function newAddMusic() {
        //获取数据
        $musicIdStr = Request::param('music_id');
        $roomId = Request::param('room_id');

        if (!$musicIdStr || !$roomId) {
            return rjson([], 500, '参数错误');
        }
        $roomId = intval($roomId);
        try {
            $musicIds = explode(',', $musicIdStr);
            MusicService::getInstance()->addMusicToRoom($roomId, $musicIds);
            return rjson([], 200, '添加成功');
        } catch (FQException $e) {
            Log::error(sprintf('MusicController::newAddMusic roomId=%d'));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新版房间删除音乐
     */
    public function newDelMusic() {
        //获取数据
        $musicId = Request::param('music_id');
        $roomId = Request::param('room_id');
        $userId = intval($this->headUid);

        if (!$musicId || !$roomId) {
            return rjson([], 500, '参数错误');
        }

        try {
            MusicService::getInstance()->removeMusicFromRoom($userId, $roomId, $musicId);
            return rjson([], 200, '移除成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新版用户音乐收藏列表
     * @return mixed
     */
    public function newLikeList() {
        //获取数据
        $roomId = intval(Request::param('room_id'));

        try {
            $musics = [];
            if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
                throw new FQException('此房间不存在', 500);
            }

            $musicIds = RoomMusicModelDao::getInstance()->getMusicIdsByRoomId($roomId);
            $musicModels = MusicModelDao::getInstance()->findSongModelsByMusicIds($musicIds);

            $getID3 = new \getID3(); //实例化类
            foreach ($musicModels as $musicModel) {
                $musics[] = $this->viewMusicNew($musicModel, $getID3);
            }
            return rjson($musics);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新版用户音乐上传列表
     * @return mixed
     */
    public function newGetList() {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $page = intval(Request::param('page'));
        if (!$roomId || !$page) {
            return rjson([], 500, '参数错误');
        }

        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;

        try {
            list($musicModels, $total) = MusicService::getInstance()->getMusicList($offset, $pageNum);
            // 查询
            $musicIds = [];
            foreach ($musicModels as $musicModel) {
                $musicIds[] = $musicModel->id;
            }
            $likeMusicIdMap = RoomMusicModelDao::getInstance()->checkLikes($roomId, $musicIds);
            $musics = [];
            $getID3 = new \getID3(); //实例化类
            foreach ($musicModels as $musicModel) {
                $star = array_key_exists($musicModel->id, $likeMusicIdMap) ? 1 : 0;
                $musics[] = $this->viewMusicNew($musicModel, $getID3, $star);
            }
            //分页数据
            $totalPage = ceil($total / $pageNum);
            return rjson([
                'list' => $musics,
                'pageInfo' => [
                    'page' => $page,
                    'pagNum' => $pageNum,
                    'totalPage' => $totalPage
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 新版搜索音乐管理
     * @return mixed
     */
    public function newSearchMusic()
    {
        $roomId = intval(Request::param('room_id'));
        $search = Request::param('search');
        $page = intval(Request::param('page'));
        if (!$roomId || !$search || !$page) {
            return rjson([], 500, '参数错误');
        }

        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;

        try {
            list($musicModels, $total) = MusicService::getInstance()->searchMusic($search, $offset, $pageNum);
            // 查询
            $musicIds = [];
            foreach ($musicModels as $musicModel) {
                $musicIds[] = $musicModel->id;
            }
            $likeMusicIdMap = RoomMusicModelDao::getInstance()->checkLikes($roomId, $musicIds);
            $musics = [];
            $getID3 = new \getID3(); //实例化类
            foreach ($musicModels as $musicModel) {
                $star = array_key_exists($musicModel->id, $likeMusicIdMap) ? 1 : 0;
                $musics[] = $this->viewMusicNew($musicModel, $getID3, $star);
            }
            //分页数据
            $totalPage = ceil($total / $pageNum);
            return rjson([
                'list' => $musics,
                'pageInfo' => [
                    'page' => $page,
                    'pagNum' => $pageNum,
                    'totalPage' => $totalPage
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}