<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\api\view\v1\RoomView;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\guild\cache\MuaKingKongBucket;
use app\domain\guild\cache\MuaRoomBucket;
use app\domain\room\dao\PhotoWallModelDao;
use app\domain\room\dao\RoomFollowModelDao;
use app\domain\room\dao\RoomInfoAuditDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\service\RoomService;
use app\domain\shumei\ShuMeiCheckType;
use app\event\RoomCreateEvent;
use app\facade\RequestAes as Request;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\dao\QueryRoomTypeDao;
use app\query\room\dao\RoomNameModelDao;
use app\query\room\service\QueryRoomService;
use app\query\room\service\QueryRoomTypeService;
use app\query\user\cache\UserModelCache;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use app\utils\TimeUtil;
use think\Exception;
use think\facade\Log;


class LanguageroomController extends ApiBaseController
{

    //房间推荐列表
    private $red_room_key = "regist_roomid";
    private $room_lock_key = "room_lock_";


    /*创建房间标签列表
     * @return mixed
     */
    public function createRoomTagList()
    {
        $userId = intval($this->headUid);
        //判断当前用户是否创建过房间;

        $roomId = RoomService::getInstance()->findRoomidForUserId($userId);
        $roomNames = RoomNameModelDao::getInstance()->getAll();
        $randList = [];
        foreach ($roomNames as $k => $v) {
            if ($v['type'] == 1) {
                $randList[1][] = $v['name'];
            } else {
                $randList[2][] = $v['name'];
            }
        }

        $createRooms = [];
        $roomTypeModels = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere(['pid', '=', '0']);
        foreach ($roomTypeModels as $roomTypeModel) {
            $createRooms[] = [
                'category_id' => $roomTypeModel->id,
                'category_name' => $roomTypeModel->roomMode,
                'rand_list' => ArrayUtil::safeGet($randList, $roomTypeModel->id),
            ];
        }

        foreach ($createRooms as $createRoom) {
            $res = [];
            $models = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere(['pid', '=', $createRoom['category_id']], 1);
            foreach ($models as $model) {
                $res[] = [
                    'type_id' => $model->id,
                    'type_name' => $model->roomMode,
                    'micnum' => $model->micCount,
                    'pid' => $model->pid,
                    'tab_icon' => CommonUtil::buildImageUrl($model->tabIcon),
                ];
            }
            $createRooms['list'] = $res;
        }
        return rjson([
            'room_id' => $roomId,
            'creatRoom' => $createRooms
        ]);
    }

    /*创建房间标签列表
     * @return mixed
     */
    public function createRoomTagNewList()
    {
        //判断当前用户是否创建过房间
        $userId = intval($this->headUid);

        $roomId = RoomService::getInstance()->findRoomidForUserId($userId);
        $createRooms = [];
        $roomTypes = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere(['pid', '=', 0]);
        foreach ($roomTypes as $roomType) {
            $roomTypeModels = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere([['pid', '=', $roomType->id],['mode_type', '=', 1]], 1);
            $childList = [];
            foreach ($roomTypeModels as $k => $roomTypeModel) {
                $nameList = RoomNameModelDao::getInstance()->findRoomNames($roomTypeModel->id);
                $gameImage = '';
                if ($roomTypeModel->id == 4) {
                    $gameImage = CommonUtil::buildImageUrl('/images/tagcoin/new/nhwc.png');
                } elseif ($roomTypeModel->id == 5) {
                    $gameImage = CommonUtil::buildImageUrl('/images/tagcoin/new/sswd.png');
                }

                $childList[] = [
                    'type_id' => $roomTypeModel->id,
                    'type_name' => $roomTypeModel->roomMode,
                    'micnum' => $roomTypeModel->micCount,
                    'pid' => $roomTypeModel->pid,
                    'rand_list' => array_column($nameList, 'name'),
                    'tab_icon' => CommonUtil::buildImageUrl($roomTypeModel->tabIcon),
                    'game_image' => $gameImage
                ];
            }
            $createRooms[] = [
                'category_id' => $roomType->id,
                'category_name' => $roomType->roomMode,
                'list' => $childList
            ];
        }

        if (!empty($roomId)) {
            $this->fixOldUserCreateRoomTask($userId, $roomId);
        }

        return rjson([
            'room_id' => $roomId,
            'creatRoom' => $createRooms
        ]);
    }

    public function fixOldUserCreateRoomTask($userId, $roomId)
    {
        # 有任务之前的老用户以及创建过房间的就让任务完成 房间在2021年之前创建的
        try {
            $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
            if (!empty($roomModel) && $roomModel->createTime < TimeUtil::strToTime("2021-01-01 00:00:00")) {
                event(new RoomCreateEvent($userId, $roomId, time()));
            }
        } catch (Exception $e) {
            Log::error(sprintf('fixOldUserCreateRoomTask error userId=%d roomId=%d, ex=%s file=%s:%d',
                $userId, $roomId, $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }

    /**创建房间接口
     * @return mixed
     */
    public function CreateRoom()
    {
        $params = Request::param();
        $userId = intval($this->headUid);

        if (!$params['room_type'] || !$params['room_name']) {
            return rjson([], 500, '参数错误');
        }

        $roomType = intval($params['room_type']);
        $roomName = $params['room_name'];

        try {
            $roomId = RoomService::getInstance()->createRoom($userId, $roomType, $roomName);
            return rjson($roomId, 200, '创建成功');
        } catch (FQException $e) {
            return rjson(null, $e->getCode(), $e->getMessage());
        }
    }

    public function viewQueryRoom($queryRoom)
    {
        return [
            'room_id' => $queryRoom->roomId,
            'room_name' => $queryRoom->roomName,
            'room_type' => $queryRoom->roomMode,
            'room_lock' => $queryRoom->lock,
            'visitor_number' => $queryRoom->visitorNumber,
            'is_live' => $queryRoom->isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'pretty_room_id' => $queryRoom->prettyRoomId,
        ];
    }

    public function viewMyQueryRoom($queryRoom)
    {
        $ret = $this->viewQueryRoom($queryRoom);
        $ret['hx_room'] = $queryRoom->hxRoom;
        return $ret;
    }

    public function myRoom()
    {
        $myRoom = QueryRoomService::getInstance()->queryMyRoom($this->headUid);
        $managerRooms = QueryRoomService::getInstance()->queryMyManagerRoom($this->headUid);
        $myRoomList = [];
        if ($myRoom) {
            $myRoomList[] = RoomView::viewMyQueryRoom($myRoom);
        }
        $managerRoomList = [];
        foreach ($managerRooms as $room) {
            $managerRoomList[] = RoomView::viewQueryRoom($room);
        }
        $result = [
            'myroom_list' => $myRoomList,    //我创建的房间信息
            'myroom_member' => $managerRoomList,      //我的管理员列表
        ];
        return rjson($result);
    }

    /**
     * 举报房间
     */
    public function reportRoom()
    {
        $roomId = Request::param('report_roomid');
        $content = Request::param('report_content');
        $userId = intval($this->headUid);

        RoomService::getInstance()->reportRoom($roomId, $userId, $content);
        return rjson([], 200, '举报成功');
    }


    /**
     * @info 房间派对列表lite版
     * @param $room_type int     房间类型id
     * @param $page  int        分页默认第一页
     * @param $pageNum   int       分页数量
     */
    public function partyRoomListLite()
    {
        //获取数据
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $roomType = Request::param('room_type', 0, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        $page = Request::param('page', 1, 'intval');
        $userId = $this->headUid;
        if ($this->channel == 'appStore' && $this->version == '3.0.4') {
            $pageNum = 200;
        }
        if (empty($roomType)) {
            return rjson([], 500, '参数错误');
        }
        list($roomList, $totalPage) = $this->fitPartyRoomListlite($roomType, $pageNum, $page, $userId, $this->source, $versionCheckStatus);
//        //返回数据
        $data = [
            'room_list' => $roomList,
            'pageInfo' => [
                'page' => $page,
                'pageNum' => $pageNum,
                'totalPage' => $totalPage,
            ]
        ];
        return rjson($data, 200, 'success');
    }


    /**
     * @Info  获取排队房间的list 的分页数据
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @param $userId
     */
    private function fitPartyRoomListlite($roomType, $pageNum, $page, $userId, $source = "", $versionCheckStatus)
    {
        if ($versionCheckStatus) {
            //提审中 展示的数据
            list($roomIds, $totalPage) = RoomService::getInstance()->getVersionPartyRoomListIds($roomType, $pageNum, $page);
            //获取房间的数据
            $roomData = QueryRoomService::getInstance()->initRoomData($roomIds);
        } else {
            //获取房间idlist
            list($roomIds, $totalPage) = RoomService::getInstance()->getPartyRoomListIds($roomType, $pageNum, $page);
            //获取房间的数据
            $roomData = RoomService::getInstance()->initRoomData($roomIds);
        }
        //循环拼接数据，转view模型
        $tpl_list = RoomView::searchRoomListViewLite($roomData, $userId, $source, $versionCheckStatus);
        return [$tpl_list, $totalPage];
    }

    private function joinViewPresonData($roomData, $userId, $source, $versionCheckStatus = 0)
    {
        $roomList = [];
        foreach ($roomData as $partyRoom) {
            if ($versionCheckStatus) {
                $roomInfo = RoomView::viewTsPersonQueryRoom($partyRoom, $source);
            } else {
                $roomInfo = RoomView::viewPersonQueryRoom($partyRoom, $source);
            }
            $roomInfo['redpackets'] = QueryRoomService::getInstance()->hasRedPacket($partyRoom->roomId, $userId);
            $roomList[] = $roomInfo;
        }
        return $roomList;
    }

    private function initRoomDataLite($roomIds)
    {
        if (empty($roomIds)) {
            return [];
        }
        $data = [];
        foreach ($roomIds as $roomId) {
            $item = GuildQueryRoomModelCache::getInstance()->find($roomId);
            if (empty($item) || $item->roomId === 0) {
                continue;
            }
            $data[] = $item;
        }
        return $data;
    }

    /**房间详情
     * @param $token    token值
     * @param $room_id  房间id
     */
    public function RoomDetails()
    {
        //获取数据
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        $roomId = intval(Request::param('room_id'));
        if (!$roomId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);

        $followModel = RoomFollowModelDao::getInstance()->loadFollow($roomId, $userId);
        $isAttentionRoom = $followModel ? 1 : 0;

        //查询房间背景图
        $roomInfo = RoomModelDao::getInstance()->loadRoomData($roomId);
        if ($roomInfo === null) {
            throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_NOT_FIND_ROOM_FAIL), Error::ERROR_NOT_FIND_ROOM_FAIL);
        }
        //如果是自己的房间不展示
        if ($roomInfo['user_id'] == $userId) {
            $isAttentionRoom = 1;
        }
        $backgroundImage = CommonUtil::buildImageUrl($roomInfo['background_image']);
        if (in_array($roomInfo['room_type'], [3, 4, 5])) {
            $roomType = $roomInfo['room_type'];
        } else {
            $roomType = 1;
        }

        if ($roomInfo['guild_id'] > 0) {
            $roomTypes = QueryRoomTypeDao::getInstance()->roomTypeGuild($roomInfo['room_type']);
        } else {
            $roomTypes = QueryRoomTypeDao::getInstance()->roomTypePerson($roomInfo['room_type']);
        }
        $result = [
            'isAttentionRoom' => $isAttentionRoom,
            'audioStreamSwitch' => ShuMeiCheckType::$AUDIO_STREAM_CHECK_SWITCH,
            'audioStreamCheckRule' => ShuMeiCheckType::$AUDIO_STREAM_STREAM_TYPE,
            'fansCount' => 0, # 获取该房间的粉丝数量 客户端没有用
            'noSeeActivityMsgLevel' => 1, #1等级不可以看见转盘/宝箱/打地鼠的消息
            'roombg' => $backgroundImage,
            'roomType' => $roomType,
            'types' => $roomTypes,
            'box' => [
                'type' => 'h5',
                'image' => CommonUtil::buildImageUrl('/image/baoxiang.gif'),
                'name' => '宝箱',
                'url' => config('config.baoxiang') . $this->headToken,
                'show_type' => 0
            ],
        ];
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if ($userModel->lvDengji >= 20) {
            $result['gameList'] = [
//                [
//                    'type' => 'h5',
//                    'image' => CommonUtil::buildImageUrl('/activity/baoxiang.png'),
//                    'name' => '星战风暴',
//                    'url' => config('config.baoxiang') . $this->headToken,
//                    'show_type' => 0
//                ],
//                [
//                    'type' => 'h5',
//                    'image' => CommonUtil::buildImageUrl('/activity/zhuanpan.png'),
//                    'name' => '幸运大转盘',
//                    'url' => config('config.zhuanpan') . $this->headToken,
//                    'show_type' => 0
//                ],
//                [
//                    'type' => 'h5',
//                    'image' => CommonUtil::buildImageUrl('/image/dadishu.png'),
//                    'name' => '打地鼠',
//                    'url' => config('config.dadishu') . $this->headToken,
//                    'show_type' => 0
//                ]
            ];
        } else {
            $result['gameList'] = [
//                [
//                    'type' => 'h5',
//                    'image' => CommonUtil::buildImageUrl('/activity/baoxiang.png'),
//                    'name' => '星战风暴',
//                    'url' => config('config.baoxiang') . $this->headToken,
//                    'show_type' => 0
//                ],
//                [
//                    'type' => 'h5',
//                    'image' => CommonUtil::buildImageUrl('/image/dadishu.png'),
//                    'name' => '打地鼠',
//                    'url' => config('config.dadishu') . $this->headToken,
//                    'show_type' => 0
//                ]
            ];
        }

        if ($versionCheckStatus) {
            $result['gameList'] = [];
            $result['box'] = [];
        }
        return rjson($result);
    }

    /**
     * 修改房间
     * @param $token    token值
     */
    public function saveRoom()
    {
        //获取数据
        $roomId = Request::param('room_id');       //房间id
        $profile = Request::param('profile');       //修改的json
        $profile = json_decode($profile, true);
        $userId = intval($this->headUid);

        if (empty($profile)) {
            return rjson([], 500, '数据错误');
        }

        $redis = RedisCommon::getInstance()->getRedis();
        //正在游戏中不能切
        $isGame = $redis->SISMEMBER('isgaming', $roomId);
        Log::record("查询房间游戏中-----" . $isGame, "info");
        if (!empty($isGame) && array_key_exists("room_type", $profile)) {
            return rjson([], 500, '当前房间正在游戏中');
        }
        //end

        try {
            $updateProfile = [];
            if (array_key_exists('room_name', $profile)) {
                $updateProfile['name'] = trim($profile['room_name']);
            }
            if (array_key_exists('room_desc', $profile)) {
                $updateProfile['desc'] = trim($profile['room_desc']);
            }
            if (array_key_exists('room_welcomes', $profile)) {
                $updateProfile['welcomes'] = trim($profile['room_welcomes']);
            }
            if (array_key_exists('room_type', $profile)) {
                $updateProfile['roomType'] = $profile['room_type'];
            }
            if (array_key_exists('background_image', $profile)) {
                $updateProfile['backgroundImage'] = $profile['background_image'];
            }
            if (array_key_exists('is_wheat', $profile)) {
                $updateProfile['isWheat'] = $profile['is_wheat'];
            }

            RoomService::getInstance()->editRoom($userId, $roomId, $updateProfile);
            return rjson([], 200, '修改成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * 修改房间
     * @param $token    token值
     */
    public function saveRoomLite()
    {
        //获取数据
        $roomId = Request::param('room_id');       //房间id
        $profile = Request::param('profile');       //修改的json
        $profile = json_decode($profile, true);
        $userId = intval($this->headUid);

        if (empty($profile)) {
            return rjson([], 500, '数据错误');
        }

        $redis = RedisCommon::getInstance()->getRedis();
        //正在游戏中不能切
        $isGame = $redis->SISMEMBER('isgaming', $roomId);
        Log::record("查询房间游戏中-----" . $isGame, "info");
        if (!empty($isGame) && array_key_exists("room_type", $profile)) {
            return rjson([], 500, '当前房间正在游戏中');
        }
        //end

        try {
            $updateProfile = [];
            if (array_key_exists('room_name', $profile)) {
                $updateProfile['name'] = trim($profile['room_name']);
            }
            if (array_key_exists('room_desc', $profile)) {
                $updateProfile['desc'] = trim($profile['room_desc']);
            }
            if (array_key_exists('room_welcomes', $profile)) {
                $updateProfile['welcomes'] = trim($profile['room_welcomes']);
            }
            if (array_key_exists('room_type', $profile)) {
                $updateProfile['roomType'] = $profile['room_type'];
            }
            if (array_key_exists('background_image', $profile)) {
                $updateProfile['backgroundImage'] = $profile['background_image'];
            }
            if (array_key_exists('is_wheat', $profile)) {
                $updateProfile['isWheat'] = $profile['is_wheat'];
            }
            if (array_key_exists('photo_id', $profile)) {
                $updateProfile['photo_id'] = $profile['photo_id'];
            }

            RoomService::getInstance()->editRoom($userId, $roomId, $updateProfile);
            return rjson([], 200, '修改成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 推荐房间列表
     */
    public function hotRoom()
    {
        $result = [
            'hotroom_list' => [],
            'game_list' => []
        ];
        return rjson($result);
    }


    public function newHotRoom()
    {
        return rjson([
            'hotroom_list' => []
        ]);
    }

    /**房间锁
     * @param $token    token值
     * @param $pwd      密码
     * @param $type 1 加锁  2解锁
     */
    public function lockRoom()
    {
        //获取数据
        $roomId = Request::param('room_id');
        $password = Request::param('pwd');
        $type = Request::param('type');
        $userId = intval($this->headUid);

        if (!$roomId || !is_numeric($type)) {
            return rjson([], 500, '参数错误');
        }

        $roomId = intval($roomId);

        try {
            if ($type == 1) {
                RoomService::getInstance()->lockRoom($userId, $roomId, $password);
            } else {
                RoomService::getInstance()->unlockRoom($userId, $roomId);
            }
            return rjson([], 200, '更新成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**房间设置接口  派对用户不能修改分类,不返回游戏类型
     * @param $token    token值
     * @param $room_id  房间id
     */
    public function roomInfo()
    {
        //获取数据
        $room_id = Request::param('room_id');
        if (!$room_id) {
            return rjson([], 500, '参数错误');
        }
        $roomModel = RoomModelDao::getInstance()->loadRoom($room_id);
        if (empty($roomModel)) {
            return rjson([], 500, '此房间不存在');
        }

        if ($roomModel->guildId > 0) {
            $where = ['pid', '=', 100];
        } else {
            $where = ['pid', 'in', '1,2'];
        }
        $roomTypeModels = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere($where);
        $newRoomInfo = [];
        foreach ($roomTypeModels as $roomTypeModel) {
            $newRoomInfo[] = [
                'type_id' => $roomTypeModel->id,
                'type_name' => $roomTypeModel->roomMode,
                'micnum' => $roomTypeModel->micCount,
                'is_use' => $roomModel->roomType == $roomTypeModel->id ? 1 : 0
            ];
        }

        $roomInfo = [
            'room_id' => $roomModel->roomId,
            'room_name' => $roomModel->name,
            'room_desc' => $roomModel->desc,
            'room_welcomes' => $roomModel->welcomes,
            'background_image' => CommonUtil::buildImageUrl($roomModel->backgroundImage),
            'room_lock' => $roomModel->lock == 1 ? 1 : 2,
            'is_wheat' => $roomModel->isWheat,
            'guild_id' => $roomModel->guildId,
            'room_type' => ArrayUtil::sort($newRoomInfo, 'is_use', SORT_DESC)
        ];
        $roomInfo['room_type'] = ArrayUtil::sort($newRoomInfo, 'is_use', SORT_DESC);
        return rjson($roomInfo);
    }


    /**房间设置接口  派对用户不能修改分类,不返回游戏类型
     * @param $token    token值
     * @param $room_id  房间id
     */
    public function roomInfoLite()
    {
        //获取数据
        $version = Request::header('VERSION');
        $room_id = Request::param('room_id');
        if (!$room_id) {
            return rjson([], 500, '参数错误');
        }
//        $field = 'id as room_id,room_name,room_type,room_desc,room_welcomes,background_image,room_lock,is_wheat,guild_id';
        $roomInfoModel = RoomModelDao::getInstance()->loadRoom($room_id);
        if ($roomInfoModel === null) {
            return rjson([], 500, '此房间不存在');
        }
        $roomInfo = RoomModelDao::getInstance()->modelToData($roomInfoModel);
//        初始化背景墙
        $roomInfo['room_wall'] = $this->initPhotoWall($roomInfo);
        if ($roomInfo['room_lock'] == 1) {
            $roomInfo['room_lock'] = 1;
        } else {
            $roomInfo['room_lock'] = 2;
        }
        $newRoomInfo = [];
        $image_url = config('config.APP_URL_image');
        $roomInfo['background_image'] = $roomInfo['background_image'] ? $image_url . $roomInfo['background_image'] : '';
        $where[] = ['pid', '=', 100];
        if ($roomInfo['guild_id'] > 0) {
            $where = ['mode_type', 'in', '1,2'];
        } else {
            $where = ['mode_type', 'in', '1'];
        }
        $roomTypeModels = QueryRoomTypeDao::getInstance()->loadRoomTypeByPidWhere($where);
        foreach ($roomTypeModels as $roomTypeModel) {
            $newRoomInfo[] = [
                'type_id' => $roomTypeModel->id,
                'type_name' => $roomTypeModel->roomMode,
                'micnum' => $roomTypeModel->micCount,
                'is_use' => $roomInfo['room_type'] == $roomTypeModel->id ? 1 : 0
            ];
        }
        //排序
        $roomInfo['room_type'] = ArrayUtil::sort($newRoomInfo, 'is_use', SORT_DESC);

        // 审核状态
        $roomInfo['audit_actions'] = $this->getAuditStatus($room_id, $roomInfo);

        $result = RoomView::roomInfoLiteView($roomInfo);
        return rjson($result);
    }


    private function getAuditStatus($roomId, &$roomInfo)
    {
        $results = RoomInfoAuditDao::getInstance()->getAuditStatus($roomId);
        $data = [
            'room_name' => [
                "status" => 1
            ],
            'room_welcomes' => [
                "status" => 1
            ],
            'room_desc' => [
                "status" => 1
            ]
        ];

        foreach ($results as $v) {
            if ($v['action'] == 'roomName') {
                $data['room_name']['status'] = $v['status'];
                $roomInfo['room_name'] = $v['content'];
            }
            if ($v['action'] == 'roomWelcomes') {
                $data['room_welcomes']['status'] = $v['status'];
                $roomInfo['room_welcomes'] = $v['content'];
            }
            if ($v['action'] == 'roomDesc') {
                $data['room_desc']['status'] = $v['status'];
                $roomInfo['room_desc'] = $v['content'];
            }
        }
        return $data;
    }

    /**照片墙列表
     * @param $token    token值
     * @param $room_id   房间id
     */
    public function photoWall()
    {
        //获取数据
        $room_id = Request::param('room_id');
        if (!$room_id) {
            return rjson([], 500, '参数错误');
        }
//        $field = 'id as room_id,room_name,room_type,room_desc,room_welcomes,background_image,room_lock';
        $roomInfoModel = RoomModelDao::getInstance()->loadRoom($room_id);
        if ($roomInfoModel === null) {
            return rjson([], 500, '此房间不存在');
        }
        $roomInfo = RoomModelDao::getInstance()->modelToData($roomInfoModel);
        $list = $this->initPhotoWall($roomInfo);
        //排序
//        $paiKey =  array_column($list, 'is_use'); //取出数组中status的一列，返回一维数组
//        array_multisort($paiKey, SORT_DESC, $list);//排序，根据$status 排序
        return rjson($list);
    }


    private function initPhotoWall($roomInfo)
    {
        $room_type = $roomInfo['room_type'];
        $room_mode = QueryRoomTypeDao::getInstance()->roomtypeForPid($room_type);
        $where[] = ['status', '=', 2];
        $where[] = ['is_del', '=', 1];
        $where[] = ['room_mode', '=', $room_mode];
        $list = PhotoWallModelDao::getInstance()->getModel()->field('id as photo_id,image,is_vip')->where($where)->order('is_vip desc')->select()->toArray();
        $image_url = config('config.APP_URL_image');
        $arr = [];
        $newArr = [];
        $vipArr = [];
        foreach ($list as $key => $value) {
            $list[$key]['image'] = $image_url . $value['image'];
            if ($roomInfo['background_image'] == $value['image']) {
                $list[$key]['is_use'] = 1;
                if ($value['is_vip'] = 0) {
                    $arr[] = $list[$key];
                } else {
                    $vipArr[] = $list[$key];
                }

            } else {
                $list[$key]['is_use'] = 0;
                $newArr[] = $list[$key];
            }
        }
        $arr = array_merge($arr, $vipArr);
        $list = array_merge($arr, $newArr);
        return $list;
    }

    /**
     * 修改房间背景
     * @param $token    token值
     */
    public function saveRoomWall()
    {
        //获取数据
        $room_id = Request::param('room_id');       //房间id
        $photo_id = Request::param('photo_id');     //图片墙
        if (!is_numeric($room_id) || !is_numeric($photo_id)) {
            return rjson([], 500, '参数错误');
        }
        //权限检测
        $roomModel = RoomModelDao::getInstance()->loadRoom($room_id);
        if ($roomModel === null) {
            return rjson([], 500, '此房间不存在');
        }
        $isRoom = RoomModelDao::getInstance()->modelToData($roomModel);

        $avatarUrl = RoomService::getInstance()->saveRoomWallHandle($isRoom, $photo_id, $this->headUid);
        //发送GO消息
        $modestr = ['roomId' => (int)$room_id, 'type' => 'baseData'];
        $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
        $msgData = json_encode($modestr);
        $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
        Log::record("房间其他信息修改添加发送参数背景-----" . $msgData, "info");
        Log::record("房间其他信息修改换添加发送背景-----" . $moderesmsg, "info");
        //发消息操作
        RoomNotifyService::getInstance()->notifyRoomBackgroundImageUpdate((int)$room_id, $avatarUrl);
        return rjson([], 200, '修改成功');
    }


    /**首页房间列表接口 (接口已废弃)
     * @param $token     用户token值
     * @param $room_type     房间类型id
     * @param $page          分页默认第一页
     */
    public function roomList()
    {
        return rjson([], 200, 'success');
    }


    public function newRoomListLite()
    {
//        获取数据
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $roomType = Request::param('room_type', 0, 'intval');
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        if (!$roomType) {
            throw new FQException("参数错误", 500);
        }
        list($data, $totalPage) = $this->fitNewRoomListLite($roomType, $page, $pageNum, $this->source, $versionCheckStatus);
        $pageInfo = array("page" => (int)$page, "pageNum" => (int)$pageNum, "totalPage" => (int)$totalPage);
        $result = [
            "room_list" => $data,
            "pageInfo" => $pageInfo,
        ];
        return rjson($result);
    }


    private function fitNewRoomListLite($roomType, $page, $pageNum, $source, $versionCheckStatus)
    {
//        获取房间idlist
        if ($versionCheckStatus) {
            /* app提审中 */
            list($roomIds, $totalPage) = RoomService::getInstance()->getVersionPartyRoomListIds($roomType, $pageNum, $page);
            $roomData = QueryRoomService::getInstance()->initRoomData($roomIds);
        } else {
            list($roomIds, $totalPage) = RoomService::getInstance()->getPartyRoomListIds($roomType, $pageNum, $page);
            //        获取房间的数据
            $roomData = RoomService::getInstance()->initRoomData($roomIds);
        }
//        循环拼接数据，转view模型
        $tpl_list = $this->joinViewPresonData($roomData, $this->headUid, $source, $versionCheckStatus);
        return [$tpl_list, $totalPage];
    }

    /**
     * 房间分类
     * @param   $type 1派对 2首页
     */
    public function roomTypeList()
    {
        $type = Request::param('type');
        if (!is_numeric($type)) {
            return rjson([], 500, '参数错误');
        }

        //查询分类
        if ($type == 1) {
            $remen = [
                'type_id' => 60,
                'room_mode' => '热门'
            ];
            $list = QueryRoomTypeService::getInstance()->loadRoomTypeForPidHundred();
        } else {
            $remen = [
                'type_id' => 6,
                'room_mode' => '热门'
            ];
            $list = QueryRoomTypeService::getInstance()->loadRoomTypeForPidOne();
        }
        array_unshift($list, $remen);
        return rjson($list);
    }

    /**
     * 房间分类
     * @param   $type 1派对 2首页
     */
    public function newRoomTypeList()
    {

        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        $type = Request::param('type');
        if (!is_numeric($type)) {
            return rjson([], 500, '参数错误');
        }
        if ($versionCheckStatus) {
            /* app提审中 */
            if ($type == 1) {
                $list = [
                    ['type_id' => 8889, 'room_mode' => '热门', 'tab_icon' => '',],
                    ['type_id' => 8888, 'room_mode' => '女神', 'tab_icon' => '',],
                    ['type_id' => 8887, 'room_mode' => '男神', 'tab_icon' => '',]
                ];
            } else {
                $list = [
                    ['type_id' => 9999, 'room_mode' => '推荐', 'tab_icon' => '',],
                    ['type_id' => 9998, 'room_mode' => '交友', 'tab_icon' => '',],
                    ['type_id' => 9997, 'room_mode' => '闲聊', 'tab_icon' => '',]
                ];
            }
            return rjson($list);
        }
        $randLook = [];
        //查询分类
        if ($type == 1) {
            $remen = [
                'type_id' => 60,
                'room_mode' => '热门',
                'tab_icon' => '',
            ];
            $list = QueryRoomTypeService::getInstance()->loadRoomTypeForPidHundred();
        } else {
            $randLook = [
                'type_id' => 9999,
                'room_mode' => '推荐',
                'tab_icon' => '',
            ];
            $list = QueryRoomTypeService::getInstance()->loadRoomTypeForPidOne();
        }
        if (isset($remen) && $remen) {
            array_unshift($list, $remen);
        }
        if ($type != 1) {
            array_unshift($list, $randLook);
        }
        return rjson($list);
    }

    /**
     * 通过房间id获取对应的房间类型
     * @param $room_id      房间id  3 狼人杀 4 你画我猜 5谁是卧底
     */
    public function roomTypeStatus()
    {
        $roomId = intval(Request::param('room_id'));
        $roomInfo = RoomModelDao::getInstance()->loadRoom($roomId);
        if (!$roomInfo) {
            return rjson([], 500, '该当前房间不存在');
        }

        if ($roomInfo->roomType == 3) {
            $result = 3;
        } else if ($roomInfo->roomType == 4) {
            $result = 4;
        } else if ($roomInfo->roomType == 5) {
            $result = 5;
        } else {
            $result = 1;
        }
        return rjson($result);
    }

    /**修改房间类型
     * @param $room_id  房间id
     * @param $type_id  类型id
     */
    public function editRoomType()
    {
        $room_id = Request::param('room_id');
        $room_type = Request::param('room_type');
        //权限检测
        $roomModel = RoomModelDao::getInstance()->loadRoom($room_id);
        if (empty($roomModel)) {
            return rjson([], 500, '此房间不存在');
        }
        $roomFind = RoomManagerModelDao::getInstance()->findManagerByUserId($room_id, $this->headUid);
        if (empty($roomFind) && $roomModel->userId != $this->headUid) {
            return rjson([], 500, '该用户权限不足无法修改');
        }
        $roomType = QueryRoomTypeDao::getInstance()->loadRoomType($room_type);
        if (empty($roomType)) {
            return rjson([], 500, "房间类型不存在");
        }
        if ($roomModel->guildId > 0) {
            return rjson([], 500, "房间类型不能修改");
        }
        //发消息操作
        $isChangeRoom = false;

        RoomNotifyService::getInstance()->notifyEditRoomType((int)$room_id, $roomModel, $roomType, $isChangeRoom);
        //修改房间设置操作
        RoomModelDao::getInstance()->updateDatas($room_id, ['room_type' => $room_type]);
        return rjson([], 200, '修改成功');
    }

    public function uniqueRoomList($arr, $otherArr)
    {
        $unique_room_list = array_filter($arr, function ($val, $key) use ($otherArr) {
            if (!in_array($key, $otherArr)) {
                return true;
            }
        }, ARRAY_FILTER_USE_BOTH);
        return $unique_room_list;
    }

    /**
     * mua新厅推荐
     */
    public function muaNewRoomRecommendLite()
    {
        $result = $this->fitMuaNewRoomRecommendLite($this->source);
        return rjson($result, 200, 'success');
    }

    private function fitMuaNewRoomRecommendLite($source)
    {
//        获取新厅数据
        $roomIds = GuildQueryRoomModelCache::getInstance()->getMuaRecommendForCache();
        if (empty($roomIds)) {
            return [];
        }
        //        获取房间的数据
        $roomData = $this->initRoomDataLite($roomIds);
//        循环拼接数据，转view模型
        return $this->joinMuaHotRoomLiteData($roomData, $source);
    }

    /**
     * mua房间金刚位
     */
    public function muaRoomKingKongLite()
    {
        //        获取数据
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');

        $result = $this->fitMuaRoomKingKongLite(9999, $page, $pageNum, $this->source);
        return rjson($result, 200, 'success');
    }


    private function fitMuaRoomKingKongLite($roomType, $page, $pageNum, $source)
    {
//        获取房间idlist
        list($roomIds, $totalPage) = $this->getMuaKingKongListIds($roomType, $pageNum, $page);
//        获取房间的数据
        $roomData = RoomService::getInstance()->initRoomData($roomIds);
//        循环拼接数据，转view模型
        return $this->joinMuaHotRoomLiteData($roomData, $source);
    }


    private function getMuaKingKongListIds($roomType, $pageNum, $page)
    {
        $bucketObj = new MuaKingKongBucket($roomType);
        $start = ($page - 1) * $pageNum;
        $end = $start + $pageNum - 1;
        $list = $bucketObj->getList($start, $end);
        $totalPage = $bucketObj->getListTotalPage($pageNum);
        return [$list, $totalPage];
    }

    public function muaHotRoomLite()
    {
//        获取数据
        $roomType = Request::param('room_type', 0, 'intval');
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        if (!$roomType) {
            throw new FQException("参数错误", 500);
        }
        list($data, $totalPage) = $this->fitMuaHotRoomLite($roomType, $page, $pageNum, $this->source);
        $pageInfo = array("page" => (int)$page, "pageNum" => (int)$pageNum, "totalPage" => (int)$totalPage);
        $result = [
            "room_list" => $data,
            "pageInfo" => $pageInfo,
        ];
        return rjson($result);
    }


    private function fitMuaHotRoomLite($roomType, $page, $pageNum, $source = "")
    {
//        获取房间idlist
        list($roomIds, $totalPage) = $this->getMuaHotRoomListIds($roomType, $pageNum, $page);
//        获取房间的数据
        $roomData = RoomService::getInstance()->initRoomData($roomIds);
//        循环拼接数据，转view模型
        return $this->joinMuaHotRoomLiteData($roomData, $source);
    }

    private function getMuaHotRoomListIds($roomType, $pageNum, $page)
    {
        $bucketObj = new MuaRoomBucket($roomType);
        $start = ($page - 1) * $pageNum;
        $end = $start + $pageNum - 1;
        $list = $bucketObj->getList($start, $end);
        $totalPage = $bucketObj->getListTotalPage($pageNum);
        return [$list, $totalPage];
    }

    private function joinMuaHotRoomLiteData($roomData, $source = "")
    {
        $result = [];
        $channel = $this->channel;
        foreach ($roomData as $partyRoom) {
            $roomInfo = RoomView::viewMuaHotRoomLiteData($partyRoom, $source);
            $roomInfo['type'] = 1;
            $result[] = $roomInfo;
        }
        return $result;
    }

    /**
     * @info 模拟恋爱
     * 获取一个随机房间id
     */
    public function randRoom()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus',0); //提审状态 1正在提审 0非提审
        if ($versionCheckStatus) {
            /* app提审中 */
            $roomId = RoomService::getInstance()->versionRandRoom($this->headUid);
        } else {
            $roomId = RoomService::getInstance()->randRoom($this->headUid);
        }
        return rjson(['roomId' => $roomId], 200, 'success');
    }

    /**
     * @info  首页 热门房间推荐位
     * @return \think\response\Json
     * @throws FQException
     */
    public function indexHotRoom()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $pageNum = Request::param('pageNum', 10, 'intval');
        $resultRoomData = RoomService::getInstance()->indexHotRoomForBacket($pageNum, $versionCheckStatus);
//        循环拼接数据，转view模型
        $result = RoomView::searchRoomListViewLite($resultRoomData, $this->headUid, $this->source ,$versionCheckStatus);
        return rjson($result, 200, 'success');
    }

    /**
     * @info 派对页人气推荐 按人气热度排序，最多返回3条数据，过滤：没有人的房间，锁房的房间
     * @return \think\response\Json
     * @throws FQException
     */
    public function partRecommend()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $pageNum = Request::param('pageNum', 3, 'intval');
        $resultRoomData = RoomService::getInstance()->partRecommendRoomForBacket($pageNum, $versionCheckStatus);
//        循环拼接数据，转view模型
        $result = RoomView::searchRoomListViewLite($resultRoomData, $this->headUid, $this->source ,$versionCheckStatus);
        return rjson($result, 200, 'success');
    }

}
