<?php
/**
 * 用户信息
 * yond
 *
 */

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\api\view\v1\UserView;
use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\dao\MonitoringModelDao;
use app\domain\exceptions\FQException;
use app\domain\user\service\UserInfoService;
use app\query\forum\dao\ForumBlackModelDao;
use app\query\forum\dao\ForumModelDao;
use app\query\dao\GiftModelDao;
use app\query\gift\dao\GiftWallModelDao;
use app\domain\gift\GiftSystem;
use app\query\gift\service\GiftWallService;
use app\domain\level\LevelSystem;
use app\query\prop\service\PropQueryService;
use app\domain\room\dao\RoomModelDao;
use app\domain\specialcare\service\UserSpecialCareService;
use app\query\site\service\SiteService;
use app\query\user\cache\NicknameLibraryCache;
use app\query\user\dao\AttentionModelDao;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\UserModel;
use app\domain\user\service\UserReportService;
use app\query\user\cache\UserModelCache;
use app\query\user\dao\FansModelDao;
use app\query\user\dao\FriendModelDao;
use app\query\user\QueryUserService;
use app\query\user\service\AttentionService;
use app\domain\user\service\UserService;
use app\domain\vip\service\VipService;
use app\query\user\service\VisitorService;
use app\service\CommonCacheService;
use app\service\VoiceDocumentService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use app\utils\TimeUtil;
use app\view\GiftView;
use \app\facade\RequestAes as Request;
use think\facade\Log;


class UserInfoController extends ApiBaseController
{
    public function userByid()
    {
        $userId = Request::param('uid');
        list($nickname, $avatar) = CommonCacheService::getInstance()->getUserNicknameAvatar($userId);
        $res = [
            'nickname' => $nickname,
            'avatar' => CommonUtil::buildImageUrl($avatar)
        ];
        return rjson($res);
    }


    /**
     * @param UserModel $userModel
     * @return array|int[]
     */
    public function getAttentionCountInfo(Usermodel $userModel)
    {
        $userId = $userModel->userId;
        if ($userModel->cancelStatus === 1) {
            return [
                'friends_count' => 0,
                'attention_count' => 0,
                'follower_count' => 0,
                'history_new' => 0,
                'history_count' => 0,
                'forum_count' => 0,
            ];
        }
        // 动态数
        $where[] = ['forum_uid', '=', $userId];
        $where[] = ['forum_status', 'in', '1,3'];
        $forum_count = ForumModelDao::getInstance()->getForumCountByWhere($where);
        return [
            'friends_count' => FriendModelDao::getInstance()->getFriendCount($userId),
            'attention_count' => AttentionModelDao::getInstance()->getAttentionCount($userId),
            'follower_count' => FansModelDao::getInstance()->getFollowCount($userId),
            //是否有新访客
            'history_new' => CommonCacheService::getInstance()->getNewVisitor($userId),
            'history_count' => CommonCacheService::getInstance()->getVisitorCount($userId),
            'forum_count' => $forum_count,
        ];
    }

    public function getCurrentRoomInfo($userModel)
    {
        $roomInfo = null;
        $curRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($userModel->userId);
        if ($curRoomId != 0) {
            $roomModel = RoomModelDao::getInstance()->loadRoom($curRoomId);
            $roomUserId = $roomModel->userId;
            $roomUserModel = QueryUserService::getInstance()->queryUserInfo($userModel->userId, $roomUserId,0);
            if ($roomModel) {
                $roomInfo = [
                    'room_id' => $curRoomId,
                    'room_name' => $roomModel->name,
                    'avatar' => CommonUtil::buildImageUrl($roomUserModel->avatar),
                ];
            }
        }
        return $roomInfo;
    }

    public function getGradeInfo($userModel)
    {
        $nextLvDengji = $userModel->lvDengji >= 100 ? 100 : $userModel->lvDengji + 1;
        $gradeInfo = [
            'lv_dengji' => $userModel->lvDengji,
            'lh_dengji' => $nextLvDengji,
            'level_number' => '' . intval($userModel->levelExp),
        ];

        $nowGrade = LevelSystem::getInstance()->getLevelByLevel($userModel->lvDengji);
        $nextGrade = $nextLvDengji == $userModel->lvDengji ? $nowGrade : LevelSystem::getInstance()->getLevelByLevel($nextLvDengji);
        if ($nextGrade == null) {
            $nextGrade = $nowGrade;
        }

        $beanModel = BeanModelDao::getInstance()->loadBean($userModel->userId);

        $gradeInfo['level_neednumber'] = $nowGrade != null ? '' . $nowGrade->count : '0';
        $gradeInfo['hight_number'] = $nextGrade != null ? '' . $nextGrade->count : '0';
        $tmp = ($userModel->levelExp / ($nextGrade != null ? $nextGrade->count : 1)) * 100;
        $gradeInfo['percentage'] = (int)abs($tmp);
        return $gradeInfo;
    }

    public function getMonitoringInfo($userId)
    {
        $ret = [];
        $model = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($model == null) {
            $ret['monitoring_status'] = 0;
        } else {
            $ret['monitoring_status'] = 1;
            $ret['monitoring_id'] = $model->monitoringId;
            $ret['user_id'] = $model->userId;
            $ret['monitoring_pwd'] = $model->monitoringPassword;
            $ret['monitoring_time'] = TimeUtil::timeToStr($model->monitoringTime);
            $ret['lock_time'] = TimeUtil::timeToStr($model->lockTime);
            $ret['constraint_lock'] = $model->constraintLock;
            $ret['status'] = $model->status;
            $ret['monitoring_endtime'] = $model->monitoringEndTime;
        }
        return $ret;
    }

    /**
     * 我的详情
     * @return [type] [description]
     */
    public function userinfo()
    {
        $queryUserId = Request::param('user_id');
        $isVisit = Request::param('isvisit');
        $userId = intval($this->headUid);

        //判断是否他人查看
        if (empty($queryUserId)) {
            $queryUserId = $userId;
        }

        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $queryUserId, $isVisit);

        if ($userModel == null) {
            return rjson([], 500, '用户信息错误');
        }

        // 构造用户信息
        $userInfo = UserView::viewUserInfo($userId, $userModel, $queryUserId, $this->version, $this->channel);

        $userInfo['type'] = 1;
        $userInfo['is_blocks'] = 1;
        $userInfo['remark_name'] = '';
        $userInfo['special_care_status'] = 2;
        $userInfo['hidden_visitor_status'] = 2;

        // 用户关注状态
        if ($userId != $queryUserId) {
            if (AttentionService::getInstance()->isFocus($userId, $queryUserId)) {
                // 已关注
                $userInfo['type'] = 2;
            }

            //判断当前用户的是否已拉黑 1未拉黑 2已拉黑用户
            $blackModel = ForumBlackModelDao::getInstance()->getBlackModel($userId, $queryUserId);
            if ($blackModel != null) {
                $userInfo['is_blocks'] = 2;
            }

            // 用户备注
            $userInfo['remark_name'] = AttentionService::getInstance()->getUserRemark($userId, $queryUserId);

            // 特别关注
            $isSpecialCare = UserSpecialCareService::getInstance()->isSpecialCare($userId, $queryUserId);
            $userInfo['special_care_status'] = $isSpecialCare ? 1 : 2; // 特别关心状态   1:是 2:不是
            // 隐身访问
            $isHiddenVisitor = VisitorService::getInstance()->isHiddenVisitor($userId, $queryUserId);
            $userInfo['hidden_visitor_status'] = $isHiddenVisitor ? 1 : 2; // 对其隐身访问状态   1:是 2:不是
        }

        $userInfo['isInvisible'] = false;
        //用户在线状态
        $redis = RedisCommon::getInstance()->getRedis();
        $room_id = CommonCacheService::getInstance()->getUserCurrentRoom($queryUserId);
        $flag = $redis->zScore('user_online_all_list', $queryUserId);
        $userInfo['online_status'] = !empty($room_id) ? (string)$room_id : !($flag === false);

        $curTime = time();
        list($onlineFlag, $lastOnlineTime) = UserService::getInstance()->getUserOnlineStatus($userId, $queryUserId, $curTime);
        $userInfo['onlineStatus'] = $onlineFlag;
        $userInfo['lastOnline'] = ['lastOnlineTime' => $lastOnlineTime, 'currentTime' => $curTime];

        // 用户是否设置隐藏在线状态
        $isHiddenOnline = UserInfoService::getInstance()->isHiddenOnline($queryUserId);
        $userInfo['hidden_online_status'] = $isHiddenOnline ? 1 : 0;  // 1:设置了隐藏在线   0:没有设置隐藏在线


        // 关注数
        $numberInfo = $this->getAttentionCountInfo($userModel);

        //查询当前房间
        $roomInfo = $this->getCurrentRoomInfo($userModel);

        // 等级信息
        $gradeInfo = $this->getGradeInfo($userModel);

        //青少年模式家长模式
        // $monitoring_info = [];
        $monitoringInfo = $this->getMonitoringInfo($queryUserId);


        //用户轮播图(三张)
        $memberAvatar = [];
        $userAlbum = CommonCacheService::getInstance()->getUserAlbum($queryUserId, $userId);
        foreach ($userAlbum as $avatar) {
            $memberAvatar[] = CommonUtil::buildImageUrl($avatar);
        }

        $result = [
            "support_staff_id" => config('config.service_customer'),
            'user_info' => $userInfo,        //用户信息
            'grade_info' => $gradeInfo,       //等级信息
            'member_avatar' => $memberAvatar,      //用户相册
            'room_info' => $roomInfo,             //房间信息
            'rank_gift' => [],              //礼物列表
            'rank_list' => [],          //排行榜信息列表
            'gift_num' => 0,             //礼物总个数
            'number_info' => $numberInfo,      //关注,粉丝,最近访客
            'monitoring_info' => $monitoringInfo,      //当前用户模式状态
            'niudanjiUrl' => config('config.niudanji').strval($this->headToken),
            'is_sound_opcode' => false,  //  是否展示音乐运营弹框
        ];
        return rjson($result);
    }

    //我的礼物列表
    public function usergiftlist()
    {
        $res = [];
        $userId = Request::param('userid');
        $receiveGiftMap = GiftWallModelDao::getInstance()->loadGiftWallByUserId($userId);
        foreach ($receiveGiftMap as $giftId => $receiveGift) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($receiveGift->kindId);
            if ($giftKind != null) {
                $res[] = [
                    'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
                    'gift_animation' => CommonUtil::buildImageUrl($giftKind->giftAnimation),
                    'animation' => CommonUtil::buildImageUrl($giftKind->animation),
                    'num' => $receiveGift->count,
                    'gift_name' => $giftKind->name,
                    'gift_type' => 1
                ];
            }
        }
        return rjson($res);
    }

    private function getUserGiftPack($userId)
    {
        $beanModel = BeanModelDao::getInstance()->loadBean($userId);
        $giftModels = GiftModelDao::getInstance()->loadAllGiftByUserId($userId);
        $packList = [];
        foreach ($giftModels as $giftModel) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftModel->kindId);
            if ($giftKind != null && $giftModel->count > 0) {
                if ($giftModel->kindId === 511) {
                    continue;
                }
                $packList[] = [
                    'gift_id' => $giftModel->kindId,
                    'pack_num' => $giftModel->count,
                    'gift_name' => $giftKind->name,
                    'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
                    'gift_number' => $giftKind->deliveryCharm,
                    'gift_coin' => $giftKind->getPriceByAssetId(AssetKindIds::$BEAN),
                    'functions' => $giftKind->functions,
                    'gift_diamond' => filter_money($giftKind->getReceiverAssetCount(AssetKindIds::$DIAMOND) / config('config.khd_scale')),
                    'clientParams' => $giftKind->clientParams
                ];
            }
        }

        $packList = ArrayUtil::sort($packList, 'gift_coin', SORT_ASC);
        return [
            'balance' => $beanModel->balance(),        //用户钱包余额
            'packlist' => $packList,       //用户背包列表
        ];
    }

    //获取用户钱包及背包列表  1.3.0
    public function userpacklist()
    {
        $userId = intval($this->headUid);
        $result = $this->getUserGiftPack($userId);
        return rjson($result);
    }

    //获取用户钱包及背包列表
    public function newUserPackList()
    {
        $userId = intval($this->headUid);
        $result = $this->getUserGiftPack($userId);

        $silverNum = 0;
        $goldNum = 0;
        $curTime = time();
        $props = PropQueryService::getInstance()->queryUserProps($userId);
        foreach ($props as $prop) {
            if ($prop->kind->kindId == 203) { //银钥匙
                $silverNum = $prop->balance($curTime);
            }
            if ($prop->kind->kindId == 204) { //金钥匙
                $goldNum = $prop->balance($curTime);
                break;
            }
        }

        if ($silverNum > 0) {
            $silverHammers = [
                'gift_id' => 6666,
                'pack_num' => $silverNum,
                'gift_name' => '许愿石',
                'gift_number' => 0,
                'gift_coin' => 0,
                'gift_image' => CommonUtil::buildImageUrl('/banner/20200706/55af56de8acce9cfe8d0434fb82add5d.png'),
                'gift_diamond' => 0
            ];
            array_unshift($result['packlist'], $silverHammers);
        }

        if ($goldNum > 0) {
            $goldHammers = [
                'gift_id' => 6666,
                'pack_num' => $goldNum,
                'gift_name' => '许愿石',
                'gift_number' => 0,
                'gift_coin' => 0,
                'gift_image' => CommonUtil::buildImageUrl('/banner/20200706/55af56de8acce9cfe8d0434fb82add5d.png'),
                'gift_diamond' => 0
            ];
            array_unshift($result['packlist'], $goldHammers);
        }

        return rjson($result);
    }

    /**
     * 用户礼物墙 (安卓3.1.0 ios2.9.4)
     * @return mixed
     */
    public function userGiftMap()
    {
        $userId = intval(Request::param('user_id'));
        $result = [];
        try {
            $receiveGiftMap = GiftWallModelDao::getInstance()->loadGiftWallByUserId($userId);
            $giftWalls = GiftSystem::getInstance()->getWalls();
            foreach ($giftWalls as $wall) {
                $userHaveCount = 0;
                $wallGifts = [];
                foreach ($wall->gifts as $gift) {
                    $count = 0;
                    $receiveGift = ArrayUtil::safeGet($receiveGiftMap, $gift->kindId);
                    if ($receiveGift != null) {
                        $count = $receiveGift->count;
                        $userHaveCount += 1;
                    }
                    $wallGifts[] = GiftView::encodeGiftWall($gift, $count);
                }
                if (count($wallGifts) > 0) {
                    $result[] = [
                        'name' => $wall->displayName,
                        'giftCount' => count($wallGifts),
                        'userHaveCount' => $userHaveCount,
                        'list' => $wallGifts,
                    ];
                }
            }

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function userGiftWall() {
        $userId = (int) Request::param('userId');
        $giftWallInfo  = GiftWallService::getInstance()->getGiftWall($userId);
        return rjson($giftWallInfo);
    }

    public function userGiftCollection() {
        $userId = (int) Request::param('userId');
        $giftCollectionInfo  = GiftWallService::getInstance()->getGiftCollection($userId);
        return rjson($giftCollectionInfo);
    }

    /**
     * 消息发送按钮  （安卓）  0不显示 1关闭 2开启
     * @return mixed
     */
    public function enableChat()
    {
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        $unableChatConfig = false;
        if (version_compare($this->version, $siteConf['apkversions'], '>')
            && $this->channel == 'HuaWei') {
            $unableChatConfig = true;
        }
        $res = $unableChatConfig == true ? 1 : 0;
        if ($res == 1) {
            $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
            $res = $userModel->unablechat == 0 ? 1 : 2;
        }
        return rjson(['unable_chat' => $res]);
    }

    /**
     * 更新私聊按钮状态  0关闭状态 1开启状态
     * @return mixed
     */
    public function saveEnableChat()
    {
        $unableChat = Request::param('unable_chat');
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        $unableChatConfig = false;
        if (version_compare($this->version, $siteConf['apkversions'], '>')
            && $this->channel == 'HuaWei') {
            $unableChatConfig = true;
        }
        if ($unableChatConfig) {
            UserModelDao::getInstance()->updateDatas($this->headUid, ['unablechat' => $unableChat]);
            return rjson('更新成功');
        } else {
            return rjson('更新失败', 500);
        }
    }


    /**
     * @info 初始化完善用户信息的数据
     * @return \think\response\Json
     */
    public function defaultNickname()
    {
        $result['nickname'] = NicknameLibraryCache::getInstance()->getNickName();
        $result['manAvatar'] = NicknameLibraryCache::getInstance()->getRandManAvatar();
        $result['womanAvatar'] = NicknameLibraryCache::getInstance()->getRandWomanAvatar();
        return rjsonFit($result, 200, "success");
    }


    /**
     * @Info 投诉用户(新版)
     * @return \think\response\Json
     * @throws FQException
     */
    public function complaintUser()
    {
        $toUserId = Request::param('toUid', 0, 'intval');
        $contents = Request::param('contents', "");
        $description = Request::param('desc', "");
        $images = Request::param('images', "");
        $videos = Request::param('videos', "");
        $userId = (int)$this->headUid;
        if ($toUserId === 0 || $contents === "" || $description === "" || $userId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

//        过滤字数
        $descLens = mb_strlen($description);
        if ($descLens > 500) {
            throw new FQException("描述内容字数不能超过500字", 500);
        }

        if ($images) {
            $imagesArr = json_decode($images, true);
            if (!is_array($imagesArr) || count($imagesArr) > 9) {
                throw new FQException("图片不能超过9张", 500);
            }
        }

        if ($videos) {
            $videosArr = json_decode($videos, true);
            if (!is_array($videosArr) || count($videosArr) > 1) {
                throw new FQException("视频只能上传1条", 500);
            }
        }

        UserReportService::getInstance()->complaintUser($userId, $toUserId, $contents, $description, $images, $videos);
        return rjson([], 200, '举报成功');
    }

    public function getVoiceDocument() {
        $userId = (int) $this->headUid;
        $data = VoiceDocumentService::getInstance()->getVoiceDocument($userId);
        return rjson($data);
    }


    /*主播关闭 打开 粉丝开播提醒按钮 安卓使用*/
    public function fansmenusatatus($token,$type){
        try {
            $userId = $this->headUid;
            if ($type == 0) {//打开开关
                $data = ['fansmenustatus' => 0];
                UserModelDao::getInstance()->updateDatas($userId, $data);
            } elseif ($type == 1) {
                $data = ['fansmenustatus' => 1];
                UserModelDao::getInstance()->updateDatas($userId, $data);
            } else {
                throw new FQException('参数错误', 500);
            }
        } catch (\Exception $e) {
            throw new FQException();
        }
    }

    /**
     * @desc 设置隐藏在线状态
     * @return \think\response\Json
     */
    public function setHiddenOnline()
    {
        $type = intval(Request::param('type'));  // 1:隐藏在线  2:取消隐藏在线
        if (!$type) {
            return rjson([], 500, '参数错误');
        }
        $userId = $this->headUid;
        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            UserInfoService::getInstance()->setHiddenOnline($userId, $type);
        } catch (\Exception $e) {
            Log::error(sprintf('UserInfoController setHiddenOnline Failed userId=%d type=%d errmsg=%d',
                $userId, $type, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

    /**
     * @desc 隐私信息
     * @return \think\response\Json
     */
    public function getHiddenInfo()
    {
        $userId = $this->headUid;

        // 隐藏在线状态
        $isHiddenOnline = UserInfoService::getInstance()->isHiddenOnline($userId);

        $result = [
            'hidden_online_status' => $isHiddenOnline ? 1 : 0, // 1:设置了隐藏在线   0:没有设置隐藏在线
        ];

        return rjson($result);
    }

}