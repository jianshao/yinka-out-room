<?php
/**
 * 房间推荐
 * yond
 *
 */

namespace app\service;

use app\api\view\v1\RoomView;
use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\domain\exceptions\FQException;
use app\domain\guild\cache\GuildRoomBucket;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use app\domain\user\dao\UserLastInfoDao;
use app\domain\user\model\UserModel;
use app\domain\version\cache\VersionCheckCache;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\site\service\SiteService;
use app\query\user\cache\UserModelCache;
use app\query\user\elastic\UserModelElasticDao;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use Exception;
use think\facade\Log;

class MemberRecommendService
{
    protected $userRecommendKey = 'user_recommend_cp_';
    protected $userGreetKey = 'user_greet_times_';
    protected $userChangeKey = 'user_change_times_';
    protected static $instance;

    private $MAX_GREET_TIMES = 1;
    private $MAX_CHANGE_TIMES = 2;

    private $unSendMsgModel = [
        'iPhone7,2', //6
        'iPhone8,1', //6s
        'iPhone8,2', //6sp
        'iPhone9,1', //7
        'iPhone9,2', //7p
        'iPhone9,3', //7
        'iPhone9,4', //7p
        'iPhone10,1',   //8
        'iPhone10,2',   //8p
        'iPhone10,4',   //8
        'iPhone10,5',   //8p
    ];

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberRecommendService();
        }
        return self::$instance;
    }

    /**
     * cp匹配
     * $filterSex 1男 2女
     * @return mixed
     */
    public function recommendUser($userId, $filterSex)
    {
        try {
            $limit = 6;
            $changeTimes = $this->getChangeTimes($userId);
            $greetTimes = $this->getGreetTimes($userId);
            if ($greetTimes == 0 || $changeTimes == 0) {
                $recommendAllUserModels = $this->getLastRecommend($userId, $limit);
            } else {
                if (empty($filterSex)) {
                    $userModel = UserModelCache::getInstance()->getUserInfo($userId);
                    $filterSex = $userModel->sex == 2 ? 1 : 2;  //默认显示女性
                }
                //获取用户匹配的用户
                $redis = RedisCommon::getInstance()->getRedis();
                $recommendAllUserModels = $this->getRecommendUser($filterSex, $userId, $limit);
                if (count($recommendAllUserModels) < $limit) {  //如果查询结果小于6则删除缓存重新匹配
                    $redis->del($this->userRecommendKey . $userId);
                    $recommendAllUserModels = $this->getRecommendUser($filterSex, $userId, $limit);
                }

                //保存本次推荐的id
                foreach ($recommendAllUserModels as $userModel) {
                    $redis->rPush($this->userRecommendKey . $userId, $userModel->userId);
                }
            }
            $this->incrChangeTimes($userId);
            return array($greetTimes, $recommendAllUserModels);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @info 版本审核中 推荐的用户 cp匹配
     * @param $userId
     * @return mixed
     * @throws
     */
    public function versionRecommendUser($userId)
    {
        try {
            $limit = 6;
            $redis = RedisCommon::getInstance()->getRedis();
            $greetTimes = $this->getGreetTimes($userId);
            $changeTimes = $this->getChangeTimes($userId);
            if ($greetTimes == 0 || $changeTimes == 0) {
                $recommendAllUserModels = $this->getLastRecommend($userId, $limit);
            }else{
                $recommendAllUserModels = $this->getVersionRecommendUser($limit);
                //保存本次推荐的id
                foreach ($recommendAllUserModels as $userModel) {
                    $redis->rPush($this->userRecommendKey . $userId, $userModel->userId);
                }
                $this->incrChangeTimes($userId);
            }
            return array($greetTimes, $recommendAllUserModels);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取最后一次匹配的用户id
     * @param $userId
     * @param $redis
     */
    private function getLastRecommend($userId, $limit)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $lastRecommend = $redis->lrange($this->userRecommendKey . $userId, -$limit, -1);
        $userModels = UserModelCache::getInstance()->findList($lastRecommend);
        return array_slice($userModels, 0, $limit);
    }

    /**
     * 准备推荐的用户 // todo 逻辑更改
     * @param $recommendIds
     * @param $filterSex
     * @param $userId
     * @return array
     */
    private function getRecommendUser($filterSex, $userId, $limit)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        //获取已经推荐过的id
        $recommendIds = $redis->lrange($this->userRecommendKey . $userId, 0, -1);

        $filterArr = [101, 102, 103, 104, 1000004, $userId];
        $recommendIds = array_merge($recommendIds, $filterArr);
        //获取在线用户列表
        $redisKey = sprintf('user_online_history_%s_list', $filterSex);
        $allData = $redis->zRevRange($redisKey, 0, 500, true);
        if (!empty($allData)) {
            $allOnlineArr = array_keys($allData);
            $canRecommendArr = array_diff($allOnlineArr, $recommendIds);
            $canRecommendArr = array_slice($canRecommendArr, 0, $limit);
            return UserModelCache::getInstance()->findList($canRecommendArr);
        } else {
            return UserModelElasticDao::getInstance()->loadUserModelForNotMatch($recommendIds);
        }
    }

    /**
     * 准备 app审核中 推荐的cp用户
     * @param $recommendIds
     * @param $filterSex
     * @param $userId
     * @return array
     */
    private function getVersionRecommendUser($limit)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $recommendUser = $redis->SRANDMEMBER(VersionCheckCache::$userListKey, $limit);
        if (empty($recommendUser)) {
            return [];
        }
        $userModels = UserModelCache::getInstance()->findList($recommendUser);
        return array_slice($userModels, 0, $limit);

    }

    /**
     * 首页cp匹配的三张图
     */
    public function getCpImage($userId)
    {
        try {
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            $filterSex = $userModel->sex == 2 ? 'boy' : 'girl';  //默认显示女性
            //获取图片
            $redis = RedisCommon::getInstance()->getRedis();
            $recommendImage = $redis->get('cp_recommend_image_list');
            $recommendImageArr = json_decode($recommendImage, true);
            if (empty($recommendImageArr)) {
                $siteConf = SiteService::getInstance()->getSiteConf(1);
                $recommendImageArr = json_decode($siteConf['cp_recommend_images'], true);
                $redis->set('cp_recommend_image_list', $siteConf['cp_recommend_images'], 86400);
            }
            shuffle($recommendImageArr[$filterSex]);
            $imageArr = array_slice($recommendImageArr[$filterSex], 0, 5);
            return $imageArr;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 打招呼
     * $greetType 1匹配打招呼 2：新用户推荐打招呼
     * @return mixed
     */
    public function greet($userId, $greetUsers, $greetType)
    {
        if (empty($greetUsers)) {
            throw new FQException('参数错误', 500);
        }

//        $timestamp = time();
        $redis = RedisCommon::getInstance()->getRedis();
        $pokeWords = $redis->get('greetmessage_cache');
        $greetMessage = json_decode($pokeWords, true);
        if (empty($greetMessage)) {
            $siteConf = SiteService::getInstance()->getSiteConf(1);
            $greetMessage = json_decode($siteConf['greet_message'], true);
            $redis->set('greetmessage_cache', $siteConf['greet_message']);
        }

        //1级女性且无工会 || 1级女性且无实名
        $userInfo = UserModelCache::getInstance()->getUserInfo($userId);
        if (!empty($userInfo)) {
            if (($userInfo->sex == 2 && $userInfo->guildId == 0 && $userInfo->lvDengji == 1) && $userInfo->attestation == 0) {
                return rjson();
            }
        }

        if ($greetType == 1) {
            //获取用户剩余打招呼次数
            $greetTimes = $this->incrGreetTimes($userId);
            if ($greetTimes > $this->MAX_GREET_TIMES) {
                throw new FQException('今日次数已用完，去其他地方看看吧', 500);
            }

            $userLastInfo = UserLastInfoDao::getInstance()->getUserInfo($userId);
            if (!empty($userLastInfo)) {
                if ($userLastInfo['channel'] == 'appStore' && in_array($userLastInfo['device'], $this->unSendMsgModel)) {
                    Log::info('ios un send msg');
                    return rjson();
                }
            }

            foreach ($greetUsers as $value) {
                $this->greetToUser($userId, $value, $greetMessage);
//                event(new UserGreetEvent($userId, $value, $timestamp));
            }

            //$this->setGreetTimes($userId, $greetTimes);
        } else {
            foreach ($greetUsers as $value) {
                $this->greetToUser($userId, $value, $greetMessage);
//                event(new UserGreetEvent($userId, $value, $timestamp));
            }
        }
        return rjson();
    }

    private function greetToUser($userId, $toUserId, $greetMessage)
    {
        //获取打招呼文案
        $rand = rand(0, count($greetMessage) - 1);
        $msg = ["msg" => $greetMessage[$rand]];
        YunxinCommon::getInstance()->sendMsg($userId, 0, $toUserId, 0, $msg);
    }

    private function initRoomData($roomIds)
    {
        if (empty($roomIds)) {
            return [];
        }
        $data = [];
        foreach ($roomIds as $roomId => $hot) {
            $item = GuildQueryRoomModelCache::getInstance()->find($roomId);
            if (empty($item) || $item->roomId === 0) {
                continue;
            }
            $data[] = $item;
        }
        return $data;
    }

    private function joinViewData($roomData,$source)
    {
        $roomList = [];
        foreach ($roomData as $partyRoom) {
            $roomInfo = RoomView::viewHotRoomLiteData($partyRoom,$source);
            $roomList[] = $roomInfo;
        }
        return $roomList;
    }


    /**
     * 处理在线列表数据(新版本)
     * @param $where
     * @param $limit
     * @param int $dataFrom
     * @return array
     */
    protected function dealOnlineUserList($userModels, $dataFrom)
    {
        try {
            if (empty($userModels)) {
                return [];
            }

            $allOnlineList = [];
            foreach ($userModels as $userModel) {
                $roomId = CommonCacheService::getInstance()->getUserCurrentRoom($userModel->userId);
                $roomType = RoomModelDao::getInstance()->findRoomTypeByRoomId($roomId);
                $roomTypeModel = RoomTypeModelDao::getInstance()->loadRoomType($roomType);
                $userData = $this->viewUserData($roomId, $userModel, $roomTypeModel);
                $userData['hotRoomList'] = [];
                $userData['type'] = 1;
                if ($dataFrom == 1) {
                    $userData['onlineType'] = empty($roomId) ? 1 : 2;
                } else {
                    $userData['onlineType'] = !empty($roomId) ? 2 : 0;  //onlineType 0:不在线 1：在线 2：在房间
                }
                $allOnlineList[] = $userData;

            }

            shuffle($allOnlineList);
            $index = rand(9, 19);
            $redis = RedisCommon::getInstance()->getRedis();
            $hot_room_list = $redis->GET('hot_recommend_room');
            if (empty(json_decode($hot_room_list, true))) {
                $hot_room_list = $this->hotRoomList();
            } else {
                $hot_room_list = json_decode($hot_room_list, true);
            }
            $arr['hotRoomList'] = $hot_room_list;
            $arr['type'] = 2;
            $newAllOnlineList = [];
            if ($index > count($allOnlineList)) {
                array_push($allOnlineList, $arr);
                $newAllOnlineList = $allOnlineList;
            } else {
                foreach ($allOnlineList as $k => $v) {
                    $newAllOnlineList[] = $v;
                    if ($index == $k) {
                        $arr['hotRoomList'] = $hot_room_list;
                        $arr['type'] = 2;
                        $newAllOnlineList[] = $arr;
                    }
                }
            }

            return $newAllOnlineList;
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 获取一页的数据
     * @param $redisKey
     * @param $start
     * @param $end
     * @return array
     */
    protected function getOnePageData($redisKey, $start, $end): array
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $data['listUser'] = $redis->zRange($redisKey, $start, $end);
        $data['count'] = $redis->zCard($redisKey);
        return $data;
    }

    protected function hotRoomList()
    {
        try {
            $field = "lm.id as room_id,lm.visitor_number,lm.visitor_externnumber,lm.visitor_users,lm.room_name,lm.room_mode,lm.user_id,lm.room_lock,lm.tag_image,m.nickname,m.avatar as room_image,t.room_mode as room_type,t.room_mode as room_tags,t.tab_icon,lm.guild_id";
            $where[] = ['room_lock', '<>', 1];
            $where[] = ['is_hot', '=', 1];
            $order = "lm.visitor_number+lm.visitor_externnumber desc,lm.id desc";
            $limit = [0, 3];
            $res = RoomModelDao::getInstance()->alias('lm')
                ->field($field)
                ->join('zb_member m', 'lm.user_id = m.id')
                ->join('zb_room_mode t', 'lm.room_type = t.id')
                ->where($where)
                ->orderRaw($order)
                ->limit($limit[0], $limit[1])
                ->select();
            $list = [];
            if ($res) {
                $list = $res->toArray();
                $image_url = config('config.APP_URL_image');
                foreach ($list as $key => $value) {
                    $list[$key]['visitor_number'] = formatNumber($value['visitor_number'] + $value['visitor_externnumber']);
                    $list[$key]['redu'] = $list[$key]['visitor_number'];
                    $list[$key]['room_image'] = getavatar($value['room_image']);
                    $list[$key]['tag_image'] = $value['tag_image'] ? $image_url . $value['tag_image'] : '';
                    $list[$key]['tab_icon'] = $value['tab_icon'] ? $image_url . $value['tab_icon'] : '';
                    if ($value['room_type'] == '狼人杀') {
                        $list[$key]['type'] = 3;
                    } else if ($value['room_type'] == '你画我猜') {
                        $list[$key]['type'] = 4;
                    } else if ($value['room_type'] == '谁是卧底') {
                        $list[$key]['type'] = 5;
                    } else {
                        $list[$key]['type'] = 1;
                    }
                }
            }
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->setex('hot_recommend_room', 60, json_encode($list));
            return $list;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getRandData($redisKey, $count) {
        $redis = RedisCommon::getInstance()->getRedis();
        $data['count'] = $redis->zCard($redisKey);
        $pageNum = floor($data['count'] / 6);
        $page = rand(1, $pageNum);
        $start = ($page - 1) * 6;
        $end = $start + 5;
        $data['listUser'] = $redis->zRange($redisKey, $start, $end);
        return $data;
    }

    private function buildGreetKey($userId)
    {
        return $this->userGreetKey . $userId;
    }

    private function buildChangeKey($userId)
    {
        return $this->userChangeKey . $userId;
    }


    private function incrGreetTimes($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $greetKey = $this->buildGreetKey($userId);
        $greetTime = $redis->incr($greetKey);
        if ($greetTime == 1) {
            $endTime = strtotime(date('Y-m-d', strtotime('+1day')));
            $redis->expireAt($greetKey, $endTime);
        }
        Log::info(sprintf('MemberRecommendService::incrGreetTimes userId=%d key=%s greetTime=%d',
            $userId, $greetKey, $greetTime));
        return $greetTime;
    }

    private function incrChangeTimes($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $changeKey = $this->buildChangeKey($userId);
        $changeTime = $redis->incr($changeKey);
        if ($changeTime == 1) {
            $endTime = strtotime(date('Y-m-d', strtotime('+1day')));
            $redis->expireAt($changeKey, $endTime);
        }
        Log::info(sprintf('MemberRecommendService::incrChangeTimes userId=%d key=%s changeTime=%d',
            $userId, $changeKey, $changeTime));
        return $changeTime;
    }

    //打咋呼次数 最多5次
    public function getGreetTimes($userId){
        $redis = RedisCommon::getInstance()->getRedis();
        $greetKey = $this->buildGreetKey($userId);
        $greetTime = $redis->get($greetKey);
        if ($greetTime === false) {
            return $this->MAX_GREET_TIMES;
        }
        $greetTime = max(0, $this->MAX_GREET_TIMES - $greetTime);
        return min($this->MAX_GREET_TIMES, $greetTime);
    }

    //换一批次数
    public function getChangeTimes($userId){
        $redis = RedisCommon::getInstance()->getRedis();
        $changeKey = $this->buildChangeKey($userId);
        $changeTime = $redis->get($changeKey);
        if ($changeTime === false) {
            return $this->MAX_CHANGE_TIMES;
        }
        $changeTime = max(0, $this->MAX_CHANGE_TIMES - $changeTime);
        return min($this->MAX_CHANGE_TIMES, $changeTime);
    }

    /**
     * @param $roomId
     * @param $userModel UserModel
     * @param $roomTypeModel
     * @return array
     */
    public function viewUserData($roomId, $userModel, $roomTypeModel)
    {
        return [
            'userId' => $userModel->userId,
            'nickName' => $userModel->nickname ? $userModel->nickname : '未知',
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'sex' => $userModel->sex ? $userModel->sex : 2,
            'age' => $userModel->birthday ? TimeUtil::birthdayToAge($userModel->birthday) : 18,
            'intro' => $userModel->intro ? $userModel->intro : '你主动我们就有故事',
            'roomId' => empty($roomId) ? '' : $roomId,
            'city' => $userModel->city == '' ? '' : $userModel->city,
            'roomModeName' => $roomTypeModel == null ? '' : $roomTypeModel->roomMode,
            'roomModeTabIcon' => $roomTypeModel == null ? '' : CommonUtil::buildImageUrl($roomTypeModel->tabIcon)
        ];
    }

    public function getMuaOnlineUserList($filterSex, $page, $pageNum, $type)
    {
        $redisKey = 'user_online_all_list';
        switch ($filterSex) {
            case 1: //男
                $redisKey = 'user_online_' . $filterSex . '_list';
                break;
            case 2: //女
                $redisKey = 'user_online_' . $filterSex . '_list';
                break;
            case 4: //随意看看
                $redisKey = 'user_online_all_list';
                break;
            default:
                break;
        }
        if ($type == 1) {
            $start = ($page - 1) * $pageNum;
            $end = ($start + $pageNum) - 1;
            $listUser = $this->getOnePageData($redisKey, $start, $end);
        } else {
            $count = 6;
            $listUser = $this->getRandData($redisKey, $count);
        }
        $res['allOnlineList'] = [];
        if (!empty($listUser['listUser'])) {
            $list = array_values($listUser['listUser']);
            $userModels = UserModelCache::getInstance()->findList($list);
            if (!empty($userModels)) {
                $res['allOnlineList'] = $this->dealMuaOnlineUserList($userModels);
            }
        }
        $res['count'] = $listUser['count'];
        return $res;
    }

    //获取提审中 展示的数据
    public function getVersionOnlineUserList($page,$pageNum)
    {
        $start = ($page - 1) * $pageNum;
        $end = ($start + $pageNum) - 1;
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = VersionCheckCache::$onlineUserListKey;
        $listUser['listUser'] = $redis->zRange($redisKey, $start, $end);
        $listUser['count'] = $redis->zCard($redisKey);
        $res['allOnlineList'] = [];
        if (!empty($listUser['listUser'])) {
            $list = array_values($listUser['listUser']);
            $userModels = UserModelCache::getInstance()->findList($list);
            if (!empty($userModels)) {
                $res['allOnlineList'] = $this->dealMuaOnlineUserList($userModels);
                shuffle($res['allOnlineList']);
            }
        }
        $res['count'] = $listUser['count'];
        return $res;
    }

    protected function dealMuaOnlineUserList($userModels)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $allOnlineList = [];
        foreach ($userModels as $userModel) {
            $ret = [
                'userId' => $userModel->userId,
                'nickName' => $userModel->nickname ?? '未知',
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                'sex' => $userModel->sex ?? 2,
                'birthday' => TimeUtil::birthdayToAge($userModel->birthday) ?? 18,
                'intro' => !empty($userModel->intro) ? $userModel->intro : '你主动我们就有故事',
                'city' => !empty($userModel->city) ? $userModel->city : '',
                'hotRoomList' => [],
                'type' => 1,
            ];

            $room_id = $redis->hget('user_current_room', $userModel->userId);
            $ret['roomId'] = empty($room_id) ? '' : $room_id;
            if (!empty($room_id)) {
                $roomInfo = RoomModelDao::getInstance()->loadRoom($room_id);
                $roomMode = RoomTypeModelDao::getInstance()->loadRoomType($roomInfo->roomType);
            }
            $ret['roomModeName'] = empty($roomMode) ? '' : $roomMode->roomMode;
            $ret['roomModeTabIcon'] = empty($roomMode) ? '' : getavatar($roomMode->tabIcon);

            $onlineType = $redis->zRank('user_online_all_list', $userModel->userId);
            if (empty($onlineType)) {
                $ret['onlineType'] = 0;
            } else {
                $ret['onlineType'] = !empty($room_id) ? 2 : 1;  //onlineType 0:不在线 1：在线 2：在房间
            }

            $allOnlineList[] = $ret;

        }
        return $allOnlineList;
    }

}