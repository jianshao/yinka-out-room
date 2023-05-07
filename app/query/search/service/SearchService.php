<?php


namespace app\query\search\service;

use app\common\RedisCommon;
use app\domain\dao\AnchorCpDao;
use app\domain\dao\BiAnchorCpPromotionDao;
use app\domain\guild\cache\CachePrefix;
use app\domain\room\service\RoomService;
use app\domain\version\cache\VersionCheckCache;
use app\query\room\model\QueryRoom;
use app\query\room\service\QueryRoomService;
use app\query\search\cache\HotAnchorCache;
use app\query\user\QueryUser;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;
use think\facade\Log;

class SearchService
{
    protected static $instance;
    protected $zeroType = 60; //首页热门房间类型

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SearchService();
        }
        return self::$instance;
    }

    public function addSearchLog($userId, $search, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->zAdd('search_log_' . $userId, $timestamp, $search);
        // 保留50
        $redis->zRemRangeByRank('search_log_' . $userId, 0, -51);

        Log::info(sprintf('SearchService::addSearchLog ok userId=%d search=%s', $userId, $search));
    }

    public function getSearchLog($userId, $offset, $count) {
        $redis = RedisCommon::getInstance()->getRedis();
        $logs = $redis->zRevRange('search_log_' . $userId, $offset, $count);
        if (empty($logs)) {
            return [];
        }
        return $logs;
    }

    public function clearSearchLog($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del('search_log_' . $userId);

        Log::info(sprintf('SearchService::clearSearchLog ok userId=%d', $userId));
    }

    public function getCache($search) {
        $rooms = [];
        $roomTotal = 0;
        $users = [];
        $userTotal = 0;

        $redis = RedisCommon::getInstance()->getRedis();
        $key = 'search.cache:' . md5($search);
        $datas = $redis->get($key);
        if (!empty($datas)) {
            $cache = json_decode($datas, true);
            $roomsData = ArrayUtil::safeGet($cache, 'rooms');
            if ($roomsData != null) {
                $roomTotal = ArrayUtil::safeGet($roomsData, 'total', 0);
                $rooms = QueryRoom::fromJsonList(ArrayUtil::safeGet($roomsData, 'list', []));
            }
            $usersData = ArrayUtil::safeGet($cache, 'users');
            if ($usersData != null) {
                $userTotal = ArrayUtil::safeGet($usersData, 'total', 0);
                $users = QueryUser::fromJsonList(ArrayUtil::safeGet($usersData, 'list', []));
            }
        }
        return [
            [$rooms, $roomTotal],
            [$users, $userTotal],
        ];
    }

    public function setCache($search, $rooms, $roomTotal, $users, $userTotal) {
        $cache = [
            'rooms' => [
                'total' => $roomTotal,
                'list' => QueryRoom::toJsonList($rooms)
            ],
            'users' => [
                'total' => $userTotal,
                'list' => QueryUser::toJsonList($users)
            ]
        ];
        $cacheData = json_encode($cache);
        $key = 'search.cache:' . md5($search);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->setex($key, 600, $cacheData);

        Log::info(sprintf('SearchService::setCache search=%s', $search));
    }

    public function searchRoomAndUser($userId, $search, $more,$setLog=true) {
        $timestamp = time();
        list($rooms, $roomTotal) = QueryRoomService::getInstance()->searchRoomForElastic($search, 0, 50);
        list($users, $userTotal) = QueryUserService::getInstance()->searchUsersForElastic($search, 0, 50);
        if ($setLog){
            $this->addSearchLog($userId, $search, $timestamp);
        }
        $this->setCache($search, $rooms, $roomTotal, $users, $userTotal);
        return [
            [$rooms, $roomTotal],
            [$users, $userTotal],
        ];
    }


    //提审中
    public function searchVersionRoomAndUser($search) {

        if(!is_numeric($search)) {
            return [[],[]];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $inRoomList = $redis->Sismember(VersionCheckCache::$roomListKey,$search);
        $rooms = $inRoomList?QueryRoomService::getInstance()->searchVersionRoom($search):[];
        $inUserList = $redis->Sismember(VersionCheckCache::$userListKey,$search);
        $users = $inUserList?QueryUserService::getInstance()->searchVersionUsers($search):[];
        return [$rooms,$users];

    }

    public function cpBindImpl($userId, $searchUsers) {
        $redis = RedisCommon::getInstance()->getRedis();
        $searchedCount = $redis->hExists('userinfo_' . $userId,'searchedCount');
        if (!$searchedCount) {
            $searchCount = count($searchUsers);
            if ($searchCount == 1) {
                $searchedUser = current($searchUsers);
                $isCpAnchor = AnchorCpDao::getInstance()->getAnchorStatus($searchedUser->userId);
                $isBind = BiAnchorCpPromotionDao::getInstance()->findOne(['user_id' => $userId, 'status' => 1]);
                if ($isCpAnchor && empty($isBind)) {
                    $data = [
                        'anchor_id' => $searchedUser->userId,
                        'user_id'   => $userId,
                        'bind_date' => time()
                    ];
                    $res = BiAnchorCpPromotionDao::getInstance()->insertData($data);
                    if ($res) {
                        $redis->hIncrBy('userinfo_' . $userId, 'searchedCount', 1);
                    }
                }
            }
        }
    }

    /**
     * @info 热搜主播
     * @return array
     */
    public function loadHotAnchorUserList()
    {
        $uids = HotAnchorCache::getInstance()->loadAllModelList();
        if (empty($uids)) {
            return [];
        }
        return QueryUserService::getInstance()->loadUserForUids($uids);
    }

    /**
     * @info 猜你喜欢
     * “猜你喜欢”模块共20个推荐位，显示当前开播中热度最高的20个房间，若不足20个则只显示已在线的
     * @return array
     */
    public function loadGuessLike()
    {
        $roomType = $this->zeroType;
        $page = 1;
        $pageNum = 20;
        $redis_connect = RedisCommon::getInstance()->getRedis();
        list($roomIds, $totalPage) = RoomService::getInstance()->getPartyRoomListIds($roomType, $pageNum, $page);
        if (empty($roomIds)) {
            return [];
        }
        $unlockRoomIds = [];
        foreach ($roomIds as $roomId => $hot) {
            $isLock = $redis_connect->sIsMember(CachePrefix::$roomLock, $roomId);
            if ($isLock === false) {
                $unlockRoomIds[$roomId] = $hot;
            }
        }
        return RoomService::getInstance()->initRoomData($unlockRoomIds);
    }


}