<?php


namespace app\domain\room\service;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\guild\cache\GuildRoomBucket;
use app\domain\guild\cache\HomeHotRoomBucket;
use app\domain\guild\cache\HomeHotRoomCache;
use app\domain\guild\cache\RecreationHotRoomBucket;
use app\domain\guild\cache\RecreationHotRoomCache;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\redpacket\RedPacketModelDao;
use app\domain\room\dao\PhotoWallModelDao;
use app\domain\room\dao\RoomCheckSwitchDao;
use app\domain\room\dao\RoomHotValueDao;
use app\domain\room\dao\RoomInfoAuditDao;
use app\domain\room\dao\RoomInfoMapDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomReportModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use app\domain\room\dao\RoomWallModelDao;
use app\domain\room\model\PhotoWallModel;
use app\domain\room\model\RoomInfoAuditActionModel;
use app\domain\room\model\RoomModel;
use app\domain\room\model\RoomWallModel;
use app\domain\room\RoomRepository;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberDetailAuditModel;
use app\domain\version\cache\VersionCheckCache;
use app\event\InnerRoomPartyEvent;
use app\event\RoomCreateEvent;
use app\event\RoomLockEvent;
use app\event\RoomUnlockEvent;
use app\event\RoomUpdateEvent;
use app\query\room\cache\CachePrefix;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\service\QueryRoomService;
use app\service\IdService;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use app\utils\Error;
use Exception;
use think\facade\Log;


class RoomService
{
    protected static $instance;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomService();
        }
        return self::$instance;
    }

    /**
     * 生成roomId
     */
    private function getNextRoomId()
    {
        // 最多循环20次，防止进入死循环
        for ($i = 0; $i < 20; $i++) {
            $roomId = IdService::getInstance()->getNextRoomId();
            if (!CommonUtil::isPrettyNumber($roomId)) {
                return $roomId;
            }
        }
        throw new FQException('房间ID生成错误', 500);
    }

    public function createRoom($userId, $roomType, $roomName)
    {
        $this->filterRoomName($roomName);

        /* 文本检测 */
        $checkStatus = ShuMeiCheck::getInstance()->textCheck($roomName, ShuMeiCheckType::$TEXT_ROOM_NAME_EVENT, $userId);
        if (!$checkStatus) {
            throw new FQException('房间名称包含敏感字符', 500);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel == null) {
            throw new FQException('用户不存在', 500);
        }

        if (empty($userModel->username)) {
            throw new FQException('您还没有绑定手机号', 5100);
        }

        if (!CommonUtil::getAppDev() && $userModel->attestation != 1) {
            throw new FQException('根据《网络游戏管理暂行办法》的要求，用户在未实名的情况下，部分功能使用受限', 500);
        }

        $roomModel = RoomModelDao::getInstance()->loadRoomByUserId($userId);
        if ($roomModel != null) {
            throw new FQException('该用户只能创建一个房间', 500);
        }

        $roomType = RoomTypeModelDao::getInstance()->loadRoomType($roomType);

        if ($roomType == null) {
            throw new FQException('该类型不存在', 500);
        }

        $roomId = $this->getNextRoomId();
        $roomModel = new RoomModel();
        $roomModel->roomId = $roomId;
        $roomModel->userId = $userId;
        $roomModel->name = $roomName;
        $roomModel->roomType = $roomType->id;
        $roomModel->createTime = time();
        $roomModel->prettyRoomId = $roomModel->roomId;
        $roomModel->backgroundImage = PhotoWallModelDao::getInstance()->loadDefaultImageWithPidStatus($roomType->pid, 0, 2);
        RoomModelDao::getInstance()->createRoom($roomModel);
        RoomInfoMapDao::getInstance()->addByUserId($userId, $roomId);
        event(new RoomCreateEvent($userId, $roomId, time()));
        return $roomModel->roomId;
    }

    private function roomInfoAuditImpl($userId, $roomId, $content, $action)
    {
        $model = new MemberDetailAuditModel();
        $model->userId = $userId;
        $model->roomId = $roomId;
        $model->content = $content;
        $model->status = 0;
        $model->action = $action;
        $model->createTime = time();

        $itemObject = RoomInfoAuditDao::getInstance()->findNotAudit($model);
        if (empty($itemObject)) {
            RoomInfoAuditDao::getInstance()->store($model);
        }
    }

    private function roomInfoAudit($roomModel, $content, $action)
    {
        if ($roomModel->guildId != 0) {
            if (RoomCheckSwitchDao::getInstance()->isAudit('guild_room')) {
                // 人工审核
                $this->roomInfoAuditImpl($roomModel->userId, $roomModel->roomId, $content, $action);
                return true;
            } else {
                $this->cancelAuditStatus($roomModel->roomId, $action, $content);
            }
        }
        if ($roomModel->guildId == 0) {
            if (RoomCheckSwitchDao::getInstance()->isAudit('person_room')) {
                // 人工审核
                $this->roomInfoAuditImpl($roomModel->userId, $roomModel->roomId, $content, $action);
                return true;
            } else {
                $this->cancelAuditStatus($roomModel->roomId, $action, $content);
            }
        }
        return false;
    }

    public function roomInfoAuditHandler($id, $status, $operatorId)
    {
        $model = RoomInfoAuditDao::getInstance()->updateStatus($id, $status, $operatorId);
        if ($status == 1) {
            $action = null;
            if ($model->action === 'roomName')
                $action = 'name';
            if ($model->action === 'roomDesc')
                $action = 'desc';
            if ($model->action === 'roomWelcomes')
                $action = 'welcomes';

            if ($action) {
                $profile = [
                    $action => $model->content
                ];
                $updateProfile = $this->editRoomImpl($model->user_id, $model->room_id, $profile);
                event(new RoomUpdateEvent($model->user_id, (int)$model->room_id, $updateProfile, time()));
            }
        }
    }

    public function cancelAuditStatus($roomId, $action, $content)
    {
        RoomInfoAuditDao::getInstance()->cancelAuditStatus($roomId, $action, $content);
    }

    private function filterRoomName($name)
    {
        if (preg_match('/.*?\d{4,}.*?/i', $name)) {
            throw new FQException("房间名称不支持多位数字", 425);
        }

        if (mb_strlen($name, 'gb2312') > 30) {
            throw new FQException('名称不超过30个字符', 500);
        }
    }


    public function editRoom($userId, $roomId, $profile)
    {
        //todo 文本检测
        $texts = [];
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('房间不存在', 500);
        }

        if (array_key_exists('name', $profile)) {
            $this->filterRoomName($profile['name']);

            $profile['name'] = trim($profile['name']);
            if (!empty($profile['name'])) {
                /* 文本检测 */
                $checkData = ShuMeiCheck::getInstance()->textCheck($profile['name'], ShuMeiCheckType::$TEXT_ROOM_NAME_EVENT, $userId);
                if (!$checkData) {
                    throw new FQException('房间名称包含敏感字符', 500);
                }

                // 人工审核
                if ($roomModel->name != $profile['name']) {
                    if ($this->roomInfoAudit($roomModel, $profile['name'], RoomInfoAuditActionModel::$roomName)) {
                        unset($profile['name']);
                    }
                }
            }
        }

        if (array_key_exists('desc', $profile)) {
            $len = mb_strlen($profile['desc'], 'gb2312');
            if ($len > 500) {
                throw new FQException('房间公告不超过500个字符', 500);
            }
            $profile['desc'] = trim($profile['desc']);
            if (!empty($profile['desc'])) {
                /* 文本检测 */
                $checkData = ShuMeiCheck::getInstance()->textCheck($profile['desc'], ShuMeiCheckType::$TEXT_ROOM_DESC_EVENT, $userId);
                if (!$checkData) {
                    throw new FQException('房间公告包含敏感字符', 500);
                }

                // 人工审核
                if ($roomModel->desc != $profile['desc']) {
                    if ($this->roomInfoAudit($roomModel, $profile['desc'], RoomInfoAuditActionModel::$roomDesc)) {
                        unset($profile['desc']);
                    }
                }
            } else {
                // 修改为空
                $this->cancelAuditStatus($roomId, RoomInfoAuditActionModel::$roomDesc, '');
            }
        }

        if (array_key_exists('welcomes', $profile)) {
            $len = mb_strlen($profile['welcomes'], 'gb2312');
            if ($len > 200) {
                throw new FQException('房间欢迎语不超过200个字符', 500);
            }
            $profile['welcomes'] = trim($profile['welcomes']);
            if (!empty($profile['welcomes'])) {
                /* 文本检测 */
                $checkData = ShuMeiCheck::getInstance()->textCheck($profile['welcomes'], ShuMeiCheckType::$TEXT_ROOM_WELCOMES_EVENT, $userId);
                if (!$checkData) {
                    throw new FQException('房间欢迎语包含敏感字符', 500);
                }

                // 人工审核
                if ($roomModel->welcomes != $profile['welcomes']) {
                    if ($this->roomInfoAudit($roomModel, $profile['welcomes'], RoomInfoAuditActionModel::$roomWelcomes)) {
                        unset($profile['welcomes']);
                    }
                }
            } else {
                // 修改为空
                $this->cancelAuditStatus($roomId, RoomInfoAuditActionModel::$roomWelcomes, '');
            }
        }

//        if (!empty($texts)) {
//            if (!GreenCommon::getInstance()->checkTexts($texts)) {
//                throw new FQException('当前包含色情或敏感字字符', 500);
//            }
//        }

        $updateProfile = $this->editRoomImpl($userId, $roomId, $profile);

        event(new RoomUpdateEvent($userId, (int)$roomId, $updateProfile, time()));
    }


    /**
     * @param $password
     * @return float|int
     * @throws FQException
     */
    private function filterPassword($password)
    {
        $len = mb_strlen($password);
        if ($len != 4 || !is_numeric($password)) {
            throw new FQException('密码为数字且不能超过4个', 500);
        }
        return $password;
    }

    public function lockRoom($userId, $roomId, $password)
    {
        $password = $this->filterPassword($password);

        $this->lockRoomImpl($userId, $roomId, $password);

        $redis = RedisCommon::getInstance()->getRedis();
        $redis->SET('room_lock_' . $roomId, $password);
        $redis->SADD('room_lock', $roomId);
//        更新列表锁房状态
        RoomHotValueDao::getInstance()->lockRoom($roomId);
        event(new RoomLockEvent($userId, $roomId, $password, time()));
    }

    public function unlockRoom($userId, $roomId)
    {
        $this->unlockRoomImpl($userId, $roomId);

        $redis = RedisCommon::getInstance()->getRedis();
        $redis->DEL('room_lock_' . $roomId);
        $redis->SREM('room_lock', $roomId);
//        更新列表锁房状态
        RoomHotValueDao::getInstance()->unlockRoom($roomId);
        event(new RoomUnlockEvent($userId, $roomId, time()));
    }

    private function unlockRoomImpl($userId, $roomId)
    {
        try {
            //判断用户是否为管理员
            $manager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
            Sharding::getInstance()->getConnectModel('roomMaster', $roomId)->transaction(function () use ($roomId, $userId, $manager) {
                $room = RoomRepository::getInstance()->loadRoom($roomId);
                if ($room == null) {
                    throw new FQException('此房间不存在', 500);
                }

                if ($userId != $room->getModel()->userId && $manager == null) {
                    throw new FQException('该用户权限不足无法操作', 500);
                }

                $room->unlockRoom();
            });

        } catch (Exception $e) {
            throw $e;
        }
    }


    private function lockRoomImpl($userId, $roomId, $password)
    {
        try {
            $haveRed = RedPacketModelDao::getInstance()->loadRedPackModelWithRoomId($roomId);
            if ($haveRed !== null) {
                throw new FQException('已存在红包不能加锁', 500);
            }

            //判断用户是否为管理员
            $manager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
            Sharding::getInstance()->getConnectModel('roomMaster', $roomId)->transaction(function () use ($roomId, $userId, $password, $manager) {
                $room = RoomRepository::getInstance()->loadRoom($roomId);
                if ($room == null) {
                    throw new FQException('此房间不存在', 500);
                }

                if ($userId != $room->getModel()->userId && $manager == null) {
                    throw new FQException('该用户权限不足无法操作', 500);
                }

                $room->lockRoom($password);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function editRoomImpl($userId, $roomId, $profile)
    {
        try {
            $roomType = null;
            if (array_key_exists('roomType', $profile)) {
                $roomType = RoomTypeModelDao::getInstance()->loadRoomType($profile['roomType']);
            }

            list($profile, $avatarUrl, $roomModel) = Sharding::getInstance()->getConnectModel('roomMaster', $roomId)->transaction(function () use ($roomId, $userId, $profile, $roomType) {
                $room = RoomRepository::getInstance()->loadRoom($roomId);
                if ($room == null) {
                    throw new FQException('房间不存在', 500);
                }

                if (array_key_exists('roomType', $profile)) {
                    if ($profile['roomType'] != $room->getModel()->roomType) {
                        if ($roomType == null or !$roomType->pid) {
                            throw new FQException('房间类型不存在', 500);
                        }
                        if ($room->getModel()->userId != $userId) {
                            throw new FQException('该用户权限不足无法修改', 500);
                        }
                        if ($room->getModel()->guildId != 0) {
                            throw new FQException('公会房间不支持修改房间类型', 500);
                        }
                        $roomWall = RoomWallModelDao::getInstance()->loadRoomWallByRoomType($roomId, $profile['roomType']);
                        if ($roomWall == null) {
                            $photoWallModel = PhotoWallModelDao::getInstance()->loadModelOneWithPidStatusStart($roomType->pid, 2, 1);
                            $roomWall = new RoomWallModel();
                            $roomWall->roomId = $roomId;
                            $roomWall->roomType = $profile['roomType'];
                            $roomWall->photoId = (int)$photoWallModel->id;
                            RoomWallModelDao::getInstance()->saveRoomWall($roomWall);
                            $profile['backgroundImage'] = $photoWallModel->image;
                        } else {
                            $profile['backgroundImage'] = $room->getModel()->image;
                        }
                    } else {
                        unset($profile['roomType']);
                    }
                }

                $avatarUrl = '';
                $room->updateProfile($profile);
                $roomModel = $room->getModel();
                return [$profile, $avatarUrl, $roomModel];
            });

        } catch (Exception $e) {
            Log::error(sprintf('EditRoomException $roomId=%d ex=%d:%s',
                $roomId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        if (array_key_exists('photo_id', $profile)) {
            $isRoom = RoomModelDao::getInstance()->modelToData($roomModel);
            $avatarUrl = $this->saveRoomWallHandle($isRoom, $profile['photo_id'], $userId);
        }

        if (array_key_exists('photo_id', $profile)) {
            //发送GO消息
            $modestr = ['roomId' => (int)$roomId, 'type' => 'baseData'];
            $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            $msgData = json_encode($modestr);
            $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            Log::record("房间其他信息修改添加发送参数背景-----" . $msgData, "info");
            Log::record("房间其他信息修改换添加发送背景-----" . $moderesmsg, "info");
            //发消息操作
            RoomNotifyService::getInstance()->notifyRoomBackgroundImageUpdate((int)$roomId, $avatarUrl);
        }

        return $profile;
    }


    /**
     * @param $isRoom
     * @param $photo_id
     * @param $userId
     * @return string
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveRoomWallHandle($isRoom, $photo_id, $userId)
    {
        $room_id = $isRoom['id'];
        $roomManagerModel = RoomManagerModelDao::getInstance()->findManagerByUserId($room_id, $userId);
        if ($roomManagerModel === null && $isRoom['user_id'] != $userId) {
            throw new FQException("该用户权限不足无法修改", 500);
        }
        $photoWallModel = PhotoWallModelDao::getInstance()->loadModelForId($photo_id);
        if ($photoWallModel === null) {
            return getavatar("");
        }
        if ($photoWallModel->isVip == 1) {
            //判断用户会员权限
            $userInfo = UserModelDao::getInstance()->loadUserModel($userId);
            if ($userInfo->vipLevel != 2) {
                throw new FQException("该用户无法更换会员房间背景图", 500);
            }
        }
        //修改房间设置操作
        $profile['background_image'] = $photoWallModel->image;
        RoomModelDao::getInstance()->saveRoomForData($room_id, $profile);
        if ($photoWallModel) {
            $roomWall = RoomWallModelDao::getInstance()->loadRoomWallByRoomType($room_id, $isRoom['room_type']);
            if ($roomWall) {
                $roomWall->photoId = $photo_id;
                RoomWallModelDao::getInstance()->updateRoomWall($roomWall);
            } else {
                $roomWall = new RoomWallModel();
                $roomWall->roomId = $room_id;
                $roomWall->roomType = $isRoom['room_type'];
                $roomWall->photoId = $photo_id;
                RoomWallModelDao::getInstance()->saveRoomWall($roomWall);
            }
        }
        return getavatar($photoWallModel->image);
    }

    public function reportRoom($roomId, $userId, $content)
    {
        //获取用户id
        if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
            throw new FQException('该房间不存在', 500);
        }
        $found = RoomReportModelDao::getInstance()->loadRoomReport($userId, $roomId);
        if (!empty($found)) {
            throw new FQException('您已经举报过该房间', 2000);
        }

        try {
            RoomReportModelDao::getInstance()->addRoomReport($userId, $roomId, $content, time());
            Log::info(sprintf('RoomService::reportRoom roomId=%d userId=%d content=%s',
                $roomId, $userId, $content));
        } catch (Exception $e) {
            throw new FQException('您已经举报过该房间', 2000);
        }
    }


    /**
     * @param $userId
     * @throws FQException
     */
    public function randRoom($userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户不存在", 500);
        }
        $sex = $userModel->sex;
        $roomType = CachePrefix::$roomTypeWoman;
        if ($sex == 2) {
            $roomType = CachePrefix::$roomTypeMan;
        }
        $roomId = RoomService::getInstance()->loadRandRoom($roomType);
        if (empty($roomId)) {
            if ($roomType === CachePrefix::$roomTypeWoman) {
                $roomType = CachePrefix::$roomTypeMan;
            } else {
                $roomType = CachePrefix::$roomTypeWoman;
            }
            $roomId = RoomService::getInstance()->loadRandRoom($roomType);
            if (empty($roomId)) {
//                throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_NOT_FIND_ROOM_FAIL), Error::ERROR_NOT_FIND_ROOM_FAIL);
                throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_NOT_PARTNER_FAIL), Error::ERROR_NOT_PARTNER_FAIL);
            }
        }
        return $roomId;
    }

    /**
     * @info 提审中 随机获取一个房间
     * @param $userId
     * @return int
     * @throws FQException
     */
    public function versionRandRoom($userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户不存在", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $randRoomList = $redis->SRANDMEMBER(VersionCheckCache::$roomListKey, 1);
        return !empty($randRoomList) ? intval($randRoomList[0]) : 0;
    }

    /**
     * @info 模拟恋爱，获取房间存在
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @return int
     */
    public function loadRandRoom($roomType)
    {
        $cacheKey = CachePrefix::$randRoomKey;
        $redis = RedisCommon::getInstance()->getRedis();
        $randroomIds = $redis->SMEMBERS($cacheKey);
        if (empty($randroomIds)) {
            return 0;
        }
        $currentRoomTypeIds = [];
        foreach ($randroomIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
            if ($roomModel === null) {
                continue;
            }
            if (CommonUtil::getAppDev() === false) {
                if ($roomModel->lock == '1') {
                    continue;
                }
            }
            if ($this->isEmptyRoom($roomModel->roomId)) {
                continue;
            }

            if ($roomModel->roomType === $roomType) {
                $currentRoomTypeIds[] = $roomModel->roomId;
                continue;
            }
        }
        if (empty($currentRoomTypeIds)) {
            return 0;
        }
        $randRoomKey = array_rand($currentRoomTypeIds, 1);
        return $currentRoomTypeIds[$randRoomKey];
    }

    public function initRoomData($roomIds)
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

    /**
     * @param $pageNum
     * @param $versionCheckStatus
     * @param false $reverse
     * @return array
     */
    public function indexHotRoomForBacket($pageNum, $versionCheckStatus)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        if ($versionCheckStatus) {
            $cacheKey = VersionCheckCache::$hotRoomListKey; //?提审中:正常
            $roomIds = $redis->Smembers($cacheKey);
            $roomIds = array_flip($roomIds);
        } else {
            $listPage = $pageNum * 2;
            $roomIds = $this->getHomeHotRoomBucket($listPage);
        }
        if (empty($roomIds)) {
            return [];
        }
        // 获取房间的数据
        if($versionCheckStatus){
            $roomData = QueryRoomService::getInstance()->initRoomData($roomIds);
        }else{
            $roomData = $this->initRoomData($roomIds);
        }
        if (empty($roomData)) {
            return [];
        }
        $resultRoomData = [];
        foreach ($roomData as $key => $itemRoomData) {
            if ((int)$itemRoomData->lock === 1) {
                continue;
            }
            if (!$versionCheckStatus && $this->isEmptyRoom($itemRoomData->roomId)) {
                continue;
            }
            $model = new HomeHotRoomCache($itemRoomData->roomId);
            $itemRoomData->popularNumber = (int)$model->getHotSum();
            $resultRoomData[] = $itemRoomData;
        }
        if (empty($resultRoomData)) {
            return [];
        }
        $resultRoomData = array_slice($resultRoomData, 0, $pageNum);
        return $resultRoomData;
    }

    /**
     * @info 获取房间bucket根据score 排序后的 ids list
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @return array
     */
    public function getPartyRoomListIds($roomType, $pageNum, $page)
    {
        $bucketObj = new GuildRoomBucket($roomType);
        $start = ($page - 1) * $pageNum;
        $end = $start + $pageNum - 1;
        $list = $bucketObj->getList($start, $end);
        $totalPage = $bucketObj->getListTotalPage($pageNum);
        return [$list, $totalPage];
    }


    /**
     * @info 获取房间bucket根据score 排序后的 ids list
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @return array
     */
    public function getHomeHotRoomBucket($pageNum)
    {
        $bucketObj = new HomeHotRoomBucket();
        $start = 0;
        $end = $start + $pageNum - 1;
        return $bucketObj->getList($start, $end);
    }


    /**
     * @info 获取房间bucket根据score 排序后的 ids list
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @return array
     */
    public function getRecommendHotRoomBucket($pageNum)
    {
        $bucketObj = new RecreationHotRoomBucket();
        $start = 0;
        $end = $start + $pageNum - 1;
        return $bucketObj->getList($start, $end);
    }

    /**
     * @info 获取提审中 展示的房间
     * @param $roomType
     * @param $pageNum
     * @param $page
     * @return array
     */
    public function getVersionPartyRoomListIds($roomType, $pageNum, $page)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = VersionCheckCache::$roomTypeListKey;
        $list = $redis->zRevRange(sprintf($cacheKey, $roomType), ($page - 1) * $pageNum, $page * $pageNum - 1, true);
        $totalPage = ceil($redis->zCard(sprintf($cacheKey, $roomType)) / $pageNum);
        return [$list, $totalPage];
    }

    /**
     * @info 获取派对页房间推荐位的数据
     * @param $pageNum
     * @return array
     * @throws FQException
     */
    public function partRecommendRoomForBacket($pageNum, $versionCheckStatus)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        if ($versionCheckStatus) {
            $cacheKey = VersionCheckCache::$partRecommendRoomKey; //?提审中:正常
            $roomIds = $redis->Smembers($cacheKey);
            $roomIds = array_flip($roomIds);
        } else {
            $listPage = $pageNum + 10;
            $roomIds = $this->getRecommendHotRoomBucket($listPage);
        }
        if (empty($roomIds)) {
            return [];
        }
        //获取房间的数据
        if($versionCheckStatus){
            QueryRoomService::getInstance()->initRoomData($roomIds);
        }else{
            $roomData = $this->initRoomData($roomIds);
        }
        if (empty($roomData)) {
            return [];
        }
        $resultRoomData = [];
        foreach ($roomData as $key => $itemRoomData) {
            if ((int)$itemRoomData->lock === 1) {
                continue;
            }
            if (!$versionCheckStatus && $this->isEmptyRoom($itemRoomData->roomId)) {
                continue;
            }
            $model = new RecreationHotRoomCache($itemRoomData->roomId);
            $itemRoomData->popularNumber = (int)$model->getHotSum();
            $resultRoomData[] = $itemRoomData;
        }
        if (empty($resultRoomData)) {
            return [];
        }
        $resultRoomData = array_slice($resultRoomData, 0, $pageNum);
        return $resultRoomData;
    }


    /**
     * @info 房间是否为空
     * @return bool  //true 空   fasle 非空
     */
    public function isEmptyRoom($roomid)
    {
//        测试服务器展示所有房间不过滤
        if (config("config.appDev") === "dev") {
            return false;
        }
        $redis_connect = RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf("%s%s", \app\domain\guild\cache\CachePrefix::$RoomUserList, $roomid);
        $data = $redis_connect->hKeys($cacheKey);
        if (empty($data) || $data == '0') {
            return true;
        }
        return false;
    }

    /**
     * @param $roomId
     * @return int
     */
    public function getRoomPkstatus($roomId)
    {
        if (empty($roomId)) {
            return 2;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $result = $redis->sIsMember(CachePrefix::$guild_pk_rooms, $roomId);

        return $result ? 1 : 2;
    }

    //reset 获取线上有效的公会房间的工会长uid
    public function getOnlineGuildDataList()
    {
        return MemberGuildModelDao::getInstance()->getGuildDataList();
    }

    /**
     * @info  通过用户id获取房间id
     * @param $userId
     * @return mixed
     * @throws FQException
     */
    public function findRoomidForUserId($userId)
    {
        if (empty($userId)) {
            return 0;
        }
        return RoomInfoMapDao::getInstance()->getRoomIdByUserId($userId);
    }

    /**
     * @info inner修改房间信息，后台的请求接口
     * @param $roomId
     * @param $guildId
     * @param $tagId
     * @param $roomName
     * @param $isHot
     * @param $isShow
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerEditRoom($roomId, $guildId, $tagId, $roomName, $isHot, $isShow)
    {
        if ($guildId > 0) {
            $where['id'] = $guildId;
            $field = 'id';
            $guild_detail = MemberGuildModelDao::getInstance()->getOne($where, $field);
            if (empty($guild_detail)) {
                throw new FQException('当前公会不存在', 601);
            }
        }

        if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
            throw new FQException('此房间不存在', 500);
        }

        $data['room_name'] = $roomName;
        $data['is_hot'] = $isHot;
        $data['is_show'] = $isShow;
        $data['tag_id'] = $tagId;
        $data['guild_id'] = $guildId;
        $saveRe = RoomModelDao::getInstance()->saveRoomForData($roomId, $data);
        if (empty($saveRe)) {
            return false;
        }

        RoomInfoMapDao::getInstance()->updateInsertGuildId($guildId, $roomId);

        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 401);
        }

        if ($roomModel->guildId !== $guildId) {
            RoomNotifyService::getInstance()->notifySyncRoomData($roomId, 'guild');
        }

        $roomType = RoomTypeModelDao::getInstance()->loadRoomType($roomModel->roomType);
        if ($roomType === null) {
            throw new FQException("房间类型异常", 500);
        }

        $profile = [];
        $profile['roomName'] = $roomName;
        $profile['isHot'] = $isHot;
        $profile['isShow'] = $isShow;
        $profile['tagId'] = $tagId;
        $profile['guildId'] = $guildId;
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return true;
    }

    /**
     * @param $roomId
     * @param $failureTime
     * @param $backgroundPath
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerRoomOssFile($roomId, $failureTime, $backgroundPath)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 500);
        }

        $data = ['background_image' => $backgroundPath];
        $saveRe = RoomModelDao::getInstance()->saveRoomForData($roomId, $data);
        if (empty($saveRe)) {
            return false;
        }

        //添加数据
        $photoWallModel = new PhotoWallModel();
        $photoWallModel->image = $backgroundPath;
        $photoWallModel->roomId = $roomId;
        $photoWallModel->failureTime = strtotime(date("Y-m-d", strtotime(sprintf("+%s months", $failureTime), time())));
        PhotoWallModelDao::getInstance()->storeModel($photoWallModel);

        $profile = [];
        $profile['backgroundImage'] = $backgroundPath;
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return true;
    }

    /**
     * @param $roomId
     * @param $prettyRoomId
     * @return bool
     * @throws FQException
     */
    public function innerAddRoomPretty($roomId, $prettyRoomId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 500);
        }

        $isset = RoomInfoMapDao::getInstance()->getRoomIdByPretty($prettyRoomId);
        if ($isset) {
            throw new FQException("靓号已存在", 500);
        }

        $data = [];
        $data['pretty_room_id'] = $prettyRoomId;
        $result=RoomInfoMapDao::getInstance()->addByPretty($prettyRoomId, $roomId);
        if (empty($result)){
            throw new FQException("修改失败", 500);
        }
        RoomModelDao::getInstance()->updateDatas($roomId, $data);

        $profile = [];
        $profile['prettyRoomId'] = $prettyRoomId;
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return true;
    }

    /**
     * @info 加入公会房间
     * @param $roomId
     * @param $checkId
     * @param $guildId
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerAddRoomParty($roomId, $roomType, $guildId)
    {
//        auth room
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间数据异常", 500);
        }

//        auth guild
        $memberGuildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($guildId);
        if ($memberGuildModel === null) {
            throw new FQException("工会不存在", 500);
        }

        $roomTypeMode = RoomTypeModelDao::getInstance()->loadRoomType($roomType);
        $roomTypeModePid = 0;
        if ($roomTypeMode !== null) {
            $roomTypeModePid = $roomTypeMode->pid;
        }

        $photoWallModel = PhotoWallModelDao::getInstance()->loadModelOneWithPidStatusStart($roomTypeModePid, 2, 1);

        $data = [
            "room_type" => $roomType,
            "background_image" => $photoWallModel->image,
            "guild_id" => $guildId,
        ];
        $updateRe = RoomModelDao::getInstance()->updateDatas($roomId, $data);
        if (!$updateRe) {
            return false;
        }
        RoomInfoMapDao::getInstance()->updateInsertGuildId($guildId, $roomId);

//        修改房间热度值
        $hotValue = 1;
        RoomHotValueDao::getInstance()->setOrignalHotValue($roomId, $hotValue);
        RoomNotifyService::getInstance()->notifyHotChange($hotValue, $roomId);

        $profile = [];
        $profile['roomType'] = $roomType;
        $profile['backgroundImage'] = $photoWallModel->image;
        $profile['guildId'] = $guildId;
        event(new InnerRoomPartyEvent($roomModel->userId, $roomId, $profile, time()));
        return true;
    }

    /**
     * @param $roomId
     * @param $datas
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerUpdateRoomForMap($roomId, $datas)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间数据异常", 500);
        }
        $updateProfile = [];
        if (array_key_exists('is_block', $datas)) {
            $updateProfile['is_block'] = $datas['is_block'];
        }

        if (array_key_exists('is_hide', $datas)) {
            $updateProfile['is_hide'] = $datas['is_hide'];
        }
        if (empty($updateProfile)) {
            return false;
        }

        $result = RoomModelDao::getInstance()->updateDatas($roomId, $updateProfile);

        $profile = [];
        if (array_key_exists('is_block', $datas)) {
            $profile['isBlock'] = $datas['is_block'];
        }
        if (array_key_exists('is_hide', $datas)) {
            $profile['isHide'] = $datas['is_hide'];
        }
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return $result;
    }


    /**
     * @param $roomId
     * @param $guildId
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerAddGuildRoomIndex($roomId, $guildId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 401);
        }
        if ($roomModel->guildId !== 0 || $roomModel->guildIndexId !== 0) {
            throw new FQException("该房间已加入其他工会", 410);
        }

        $data['guild_index_id'] = $guildId;
        $updateRe = RoomModelDao::getInstance()->updateDatas($roomId, $data);
        if (!$updateRe) {
            return false;
        }
        $profile['guild_index_id'] = $data['guild_index_id'];
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return $updateRe;
    }


    /**
     * @info inner 修改工会房间
     * @param $roomId
     * @param $guildId
     */
    public function innerAddGuildRoom($roomId, $guildId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 401);
        }
        $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($guildId);
        if ($guildModel === null) {
            throw new FQException("公会不存在", 500);
        }
        $check_id = '23';
        $pid = RoomTypeModelDao::getInstance()->loadPidForId($check_id);

        $photoWallModel = PhotoWallModelDao::getInstance()->loadModelOneWithPidStatusStartDesc($pid, 2, 1);

        $data = [
            "room_type" => $check_id,
            "background_image" => $photoWallModel->image,
            "guild_id" => $guildId,
        ];
        $updateRe = RoomModelDao::getInstance()->updateDatas($roomId, $data);
        if (!$updateRe) {
            return false;
        }
        RoomInfoMapDao::getInstance()->updateInsertGuildId($guildId, $roomId);

//        初始化房间热度值
        RoomHotValueDao::getInstance()->setFieldValue($roomId, 'orignal', 1);

        $profile['room_type'] = $data['room_type'];
        $profile['background_image'] = $data['background_image'];
        $profile['guild_id'] = $data['guild_id'];
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return $updateRe;
    }


    /**
     * @param $roomId
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerDelGuidRoomIndex($roomId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 401);
        }
        if ($roomModel->guildIndexId === 0) {
            throw new FQException("房间已经没有工会了", 410);
        }
        $data['guild_index_id'] = 0;
        $updateRe = RoomModelDao::getInstance()->updateDatas($roomId, $data);
        if (!$updateRe) {
            return false;
        }
        $profile['guild_index_id'] = $data['guild_index_id'];
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return $updateRe;
    }

    /**
     * @param $roomId
     * @param $guildId
     * @param $guildType
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function innerDelGuidRoom($roomId, $guildId, $guildType)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel === null) {
            throw new FQException("房间不存在", 401);
        }
        if ($roomModel->guildId !== $guildId) {
            throw new FQException("公会id错误", 500);
        }

        if ($guildType === 1) {
            $data['guild_id'] = 0;
        } else {
            $data['guild_index_id'] = 0;
        }
        $data['room_type'] = 9;
        $data['background_image'] = PhotoWallModelDao::getInstance()->loadModelOneWithPidStatusStartDesc(1, 2, 1);
        $updateRe = RoomModelDao::getInstance()->updateDatas($roomId, $data);
        if (!$updateRe) {
            return false;
        }
        RoomInfoMapDao::getInstance()->updateInsertGuildId(0, $roomId);

        $profile['room_type'] = $data['room_type'];
        $profile['background_image'] = $data['background_image'];
        event(new RoomUpdateEvent($roomModel->userId, $roomId, $profile, time()));
        return $updateRe;
    }
}















