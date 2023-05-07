<?php

namespace app\query\weshine\service;

use app\common\RedisCommon;
use app\domain\dao\ImCheckMessageModelDao;
use app\domain\exceptions\FQException;
use app\query\weshine\api\WeShineApi;
use app\query\weshine\cache\ShineBlackKeywordCache;
use app\query\weshine\dao\ShineBlackKeywordModelDao;
use app\query\weshine\model\WeShineModel;
use app\service\LockService;
use app\utils\Error;
use think\facade\Log;

class WeShineService
{
    protected static $instance;
    private $shineHotLookListKey = 'weshine_shineHotLookList';
    private $shineSearchKey = 'weshine_shineHotLookList';
    private $shineHiKey = 'weshine_shineHi';
    private $userHistoryShineKey = 'weshine_user_history';
//    private $filterTextKey = "weshine_search_filter_text";
    private $searchExpUnix = 86400;

    public static $REFRESH_HISTORY_SHINE_SCRIPT = "
        local key = tostring(KEYS[1])
        local shine = tostring(KEYS[2])
        local oldDataMap=redis.call('lrange', key, 0, 4)
        
        for k,v in ipairs(oldDataMap) do
          if v == shine then
            return 0;
          end
        end
        
        redis.call('lpush', key, shine)
        redis.call('ltrim', key, 0,4)
        redis.call('expire', key, 259200)
        return 1
    ";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WeShineService();
        }
        return self::$instance;
    }

    /**
     * @info load所有表情
     * @param null $age
     * @param null $showFileSize
     * @param null $offset
     * @param null $limit
     * @return array
     */
    public function loadAllShineHotLook($age = null, $showFileSize = null, $offset = null, $limit = null)
    {
        $age = !is_null($age) ? $age : "";
        $showFileSize = !is_null($showFileSize) ? $showFileSize : 0;
        $offset = !is_null($offset) ? $offset : null;
        $limit = !is_null($limit) ? $limit : null;
        $data = [
            'age' => $age,
            'showfilesize' => $showFileSize,
            'offset' => $offset,
            'limit' => $limit
        ];
        return WeShineApi::getInstance()->getResponse('shineHotLook', $data);
    }

    /**
     * @param $originData
     * @return array
     */
    private function coveColumnList($originData)
    {
        if (!isset($originData['list'])) {
            return [];
        }
        $originList = array_column($originData['list'], 'origin');
        $result = [];
        foreach ($originList as $k => $originItem) {
            $itemData['src'] = $originItem['gif'] ?? "";
            $itemData['width'] = $originItem['w'] ?? 0;
            $itemData['height'] = $originItem['h'] ?? 0;
            $result[] = $itemData;
        }
        return $result;
    }

    /**
     * @info load用户表情for cache
     * @param $userId
     * @param $page
     * @param $pageSize
     * @return array
     * @throws FQException
     */
    public function getUserShineHotLookListForCache($userId, $offset, $limit)
    {
        if (empty($userId)) {
            throw  new FQException("fatal error userid is empty", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $date = date("Ymd");
        $cacheKey = $this->getShineHotLookUserKey($userId, $date);
        $cacheData = $redis->get($cacheKey);
        if ($cacheData) {
            $originData = json_decode($cacheData, true);
            $sliceData = $this->coveColumnList($originData);
            $lists = array_slice($sliceData, $offset, $limit);
            $pageInfo = $this->makePageInfo($sliceData, $lists, $limit, $offset);
            return [$lists, $pageInfo];
        }
        $originData = $this->loadAllShineHotLook();
        if (empty($originData['list'])) {
            $pageInfo = $this->makePageInfo([], [], $limit, $offset);
            return [[], $pageInfo];
        }
        $redis->setex($cacheKey, 86500, json_encode($originData));
        $sliceData = $this->coveColumnList($originData);
        $lists = array_slice($sliceData, $offset, $limit);
        $pageInfo = $this->makePageInfo($sliceData, $lists, $limit, $offset);
        return [$lists, $pageInfo];
    }

    /**
     * @param $sliceData
     * @param $lists
     * @param $limit
     * @param $offset
     * @return array
     */
    private function makePageInfo($sliceData, $lists, $limit, $offset)
    {
        $count = count($lists);
        $totalCount = count($sliceData);
        return [
            'totalCount' => $totalCount,
            'totalPage' => $limit ? ceil($totalCount / $limit) : 0,
            'count' => $count,
            'offset' => $offset + $count,
        ];
    }

    private function getShineHotLookUserKey($userId, $date)
    {
        return sprintf("%s:%s:%s", $this->shineHotLookListKey, $date, $userId);
    }

    /**
     * @param $keyword
     * @param $date
     * @param $offset
     * @param $limit
     * @return string
     */
    private function getShineSearchKey($keyword, $date, $offset, $limit)
    {
        if (empty($offset)) {
            $offset = 0;
        }
        if (empty($limit)) {
            $limit = 0;
        }
        $hashKeyWord = md5(urlencode($keyword));
        return sprintf("%s_d:%s_k:%s_o:%d_l:%d", $this->shineSearchKey, $date, $hashKeyWord, $offset, $limit);
    }


    /**
     * @param $showFileSize
     * @param $offset
     * @param $limit
     * @param $keyword
     * @throws FQException
     */
    public function loadImShineSearch($keyword = null, $offset = null, $limit = null, $showFileSize = null)
    {
        if ($offset >= 40) {
            $pageInfo = $this->makePageInfo([], [], 0, $offset);
            return [[], $pageInfo];
        }
        $showFileSize = !is_null($showFileSize) ? $showFileSize : 0;
        $offset = !is_null($offset) ? $offset : null;
        $limit = !is_null($limit) ? $limit : null;
        if (is_null($keyword)) {
            throw new FQException('参数错误', 500);
        }
        try {
            $data = [
                'keyword' => $keyword,
                'showfilesize' => $showFileSize,
                'offset' => $offset,
                'limit' => $limit
            ];
            $originData = WeShineApi::getInstance()->getResponse('shineSearch', $data);
            $list = $this->coveColumnList($originData);
            $pageInfo = $originData['pageInfo'];
            return [$list, $pageInfo];
        } catch (\Exception $e) {
            Log::info(sprintf('RoomFollowService::loadImShineSearch ok error code=%d errorMsg=%s', $e->getCode(), $e->getMessage()));
            $pageInfo = $this->makePageInfo([], [], 0, $offset);
            return [[], $pageInfo];
        }
    }

    /**
     * @info 闪萌搜索
     * @param $keyword
     * @param $offset
     * @param $limit
     * @return array
     * @throws FQException
     */
    public function shineSearch($keyword, $offset, $limit)
    {
        try {
            $this->shineSearchFilter($keyword);
            return $this->loadImShineSearchForCache($keyword, $offset, $limit);
        } catch (FQException $e) {
            $pageInfo = $this->makePageInfo([], [], 0, $offset);
            return [[], $pageInfo];
        }
    }

    /**
     * @info 过滤关键词
     * @param $keyword
     * @throws FQException
     */
    private function shineSearchFilter($keyword)
    {
        if (empty($keyword)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $preg = '/^[a-zA-Z\x{4e00}-\x{9fa5}]+$/u';
        if (preg_match($preg, $keyword) !== 1) {
            throw new FQException("符号不能搜索", 500);
        }

        $preg = '/^[a-zA-Z0-9]?$/u';
        if (preg_match($preg, $keyword) === 1) {
            throw new FQException("符号不能搜索lite", 500);
        }

//        loadshanmengcache 过滤黑名单
        $this->shineBlackKeywordFilter($keyword);
    }


    /**
     * @info 过滤闪萌黑名单
     * @param $keyword
     * @return bool
     * @throws FQException
     */
    public function shineBlackKeywordFilter($keyword)
    {
        if (empty($keyword)) {
            return false;
        }
        $cacheModel = ShineBlackKeywordCache::getInstance()->loadModelForKeyword($keyword);
        if ($cacheModel !== false && $cacheModel->id === 0) {
            return true;
        }
        if ($cacheModel !== false) {
            throw new FQException("黑名单关键词不能搜索", 500);
        }
        $lockKey = ShineBlackKeywordCache::getInstance()->getshineBlackLockKey($keyword);
        LockService::getInstance()->lock($lockKey);
        try {
            $model = ShineBlackKeywordModelDao::getInstance()->loadModelForKeyword($keyword);
            if ($model !== null) {
                ShineBlackKeywordCache::getInstance()->store($keyword, $model);
                throw new FQException("黑名单关键词不能搜索", 500);
            } else {
                ShineBlackKeywordCache::getInstance()->storeZero($keyword);
            }
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
        return true;
    }


    /**
     * @param $showFileSize
     * @param $offset
     * @param $limit
     * @param $keyword
     * @throws FQException
     */
    public function loadImShineSearchForCache($keyword = null, $offset = null, $limit = null, $showFileSize = null)
    {
        if ($offset >= 40) {
            $pageInfo = $this->makePageInfo([], [], 0, $offset);
            return [[], $pageInfo];
        }
        $showFileSize = !is_null($showFileSize) ? $showFileSize : 0;
        $offset = !is_null($offset) ? $offset : null;
        $limit = !is_null($limit) ? $limit : null;
        if (is_null($keyword)) {
            throw new FQException('参数错误', 500);
        }
        try {
            $date = date("Ymd");
            $cacheKey = $this->getShineSearchKey($keyword, $date, $offset, $limit);
            $redis = RedisCommon::getInstance()->getRedis();
            $cacheData = $redis->get($cacheKey);
            $originData = json_decode($cacheData, true);
            if ($cacheData && is_array($originData)) {
                $lists = $this->coveColumnList($originData);
                $pageInfo = $originData['pageInfo'] ?? $this->makePageInfo([], [], 0, $offset);;
                return [$lists, $pageInfo];
            }
            $data = [
                'keyword' => $keyword,
                'showfilesize' => $showFileSize,
                'offset' => $offset,
                'limit' => $limit
            ];
            $originData = WeShineApi::getInstance()->getResponse('shineSearch', $data);
            if (empty($originData['list']) || !is_array($originData)) {
                $pageInfo = $this->makePageInfo([], [], $limit, $offset);
                return [[], $pageInfo];
            }
            $redis->setex($cacheKey, $this->searchExpUnix, json_encode($originData));
            $list = $this->coveColumnList($originData);
            $pageInfo = $originData['pageInfo'] ?? $this->makePageInfo([], [], 0, $offset);
            return [$list, $pageInfo];
        } catch (\Exception $e) {
            Log::error(sprintf('RoomFollowService::loadImShineSearchForCache ok error code=%d errorMsg=%s', $e->getCode(), $e->getMessage()));
            $pageInfo = $this->makePageInfo([], [], 0, $offset);
            return [[], $pageInfo];
        }
    }

    /**
     * @info 获取用户最近3天的最近使用表情
     * @param $userId
     * @return array
     * @throws FQException
     */
    public function getHistoryShineForUser($userId)
    {
        if (empty($userId)) {
            throw new FQException("fatal error userid error", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getUserHistoryShineKey($userId);
        $jsonData = $redis->lRange($cacheKey, 0, 4);
        $result = [];
        foreach ($jsonData as $itemJson) {
            $itemData = json_decode($itemJson, true);
            if (!$itemData || !is_array($itemData)) {
                continue;
            }
            $model = new WeShineModel;
            $result[] = $model->fromJson($itemData);
        }
        return $result;
    }

    private function getUserHistoryShineKey($userId)
    {
        return sprintf("%s:%s", $this->userHistoryShineKey, $userId);
    }

    /**
     * @param $userId
     * @param $shine
     * @return mixed
     * @throws FQException
     */
    public function setHistoryShineForUser($userId, WeShineModel $WeShineModel)
    {
        if (empty($userId)) {
            throw new FQException("fatal error userid error", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getUserHistoryShineKey($userId);
        $shineJson = json_encode($WeShineModel->toJson());
        $result = $redis->eval(self::$REFRESH_HISTORY_SHINE_SCRIPT,
            [$cacheKey, $shineJson], 2);
        return $result;
    }

    /**
     * @info 获取打招呼表情
     * @return mixed
     */
    public function getShineHi()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $jsonStr = $redis->get($this->shineHiKey);
        return json_decode($jsonStr, true);
    }

    /**
     * @info 私聊是否展示聊天窗 1 存在聊天 2 没聊过天
     * @param $userId
     * @param $toUid
     * @return int
     * @throws FQException
     */
    public function userTalkStatus($userId, $toUid)
    {
        if (empty($userId) || empty($toUid)) {
            throw new FQException("参数错误", 500);
        }
        $data = ImCheckMessageModelDao::getInstance()->getUserTalkStatus($userId, $toUid);
        if (empty($data)) {
            return 2;
        }
        return 1;
    }

    /**
     * @info 检测是否为闪萌图片
     * @param $message
     * @return bool
     */
    public function checkWeshineImages($message)
    {
        if (empty($message)) {
            return false;
        }
        $preg = "/^http.*?weshineapp\.com.*?/i";
        $result = preg_match($preg, $message);
        if (empty($result)) {
            return false;
        }
        return true;
    }
}