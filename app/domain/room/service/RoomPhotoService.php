<?php


namespace app\domain\room\service;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\domain\Config;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\gift\service\GiftService;
use app\domain\prop\PropKindBubble;
use app\domain\user\UserRepository;
use app\query\prop\service\PropQueryService;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomPhotoModelDao;
use app\domain\room\model\RoomManagerModel;
use app\domain\room\model\RoomPhotoModel;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\user\dao\UserModelDao;
use app\form\ReceiveUser;
use app\service\LockService;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class RoomPhotoService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomPhotoService();
        }
        return self::$instance;
    }

    public function getRoomPhotoGifts(){
        $conf = Config::getInstance()->getConfigByKey("room_photo_conf");
        return ArrayUtil::safeGet($conf, 'gifts', []);
    }

    public function addPhoto($roomId, $userId, $image, $giftId, $timestamp) {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null or $roomModel->guildId == 0) {
            throw new FQException('该房间不是派对房', 500);
        }

        if (!in_array($giftId, $this->getRoomPhotoGifts())) {
            throw new FQException('不能选择该礼物', 500);
        }

        if(RoomPhotoModelDao::getInstance()->getPhotoCount($roomId) >= 100){
            throw new FQException("房间相册已满", 500);
        }

        if ($roomModel->userId != $userId) {
            $managerModel = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
            if (empty($managerModel)) {
                throw new FQException("该用户权限不足无法修改", 500);
            }
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
        if ($giftKind == null) {
            throw new FQException('没有该礼物', 500);
        }

        // 用户加锁
        $lockKey = "room_photo_lock_".$roomId;
        LockService::getInstance()->lock($lockKey);
        try {
            $photoModel = new RoomPhotoModel();
            $photoModel->userId = $userId;
            $photoModel->roomId = $roomId;
            $photoModel->image = $image;
            $photoModel->giftId = $giftId;
            $photoModel->status = RoomPhotoModel::$STATUS_CHECKING;
            $photoModel->createTime = $timestamp;
            $photoId = RoomPhotoModelDao::getInstance()->addRoomPhoto($photoModel);

            $checkStatus = ShuMeiCheck::getInstance()->imageCheck($image,ShuMeiCheckType::$IMAGE_ALBUM_EVENT, $userId);
            if(!$checkStatus){
                RoomPhotoModelDao::getInstance()->updatePhotoStatus($photoId, RoomPhotoModel::$STATUS_CHECK_FAIL);
                throw new FQException("图片违反平台规定", 500);
            }else{
                RoomPhotoModelDao::getInstance()->updatePhotoStatus($photoId, RoomPhotoModel::$STATUS_CHECK_PASS);
            }
        }finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    public function synPublicScreen($roomId, $userId, $photoId){
        $roomPhotoModel = RoomPhotoModelDao::getInstance()->loadPhoto($photoId);
        if ($roomPhotoModel == null) {
            throw new FQException('没有该图片', 500);
        }

        if ($roomPhotoModel->userId != $userId) {
            throw new FQException('该图片不是您发布的', 500);
        }

        if ($roomPhotoModel->status != RoomPhotoModel::$STATUS_CHECK_PASS) {
            throw new FQException('只有审核通过的图片才能发布', 500);
        }

        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null or $roomModel->guildId == 0) {
            throw new FQException('该房间不是派对房', 500);
        }

        if ($roomModel->userId != $userId) {
            $managerModel = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
            if (empty($managerModel)) {
                throw new FQException("该用户权限不足无法修改", 500);
            }
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $expireTime = $redis->ttl(sprintf('room_photo_%d_%d', $roomId, $photoId));
        if($expireTime > 0){
            throw new FQException((int)$expireTime.'秒后可再发布', 500);
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($roomPhotoModel->giftId);
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
//        $bubble = PropQueryService::getInstance()->getWaredProp($userId, PropKindBubble::$TYPE_NAME);
        $msg = [
            'msgId'=>2093,
            'roomId' => $roomId,
            'user' => [
                'userIdentity' => RoomManagerModelDao::getInstance()->viewUserIdentity($roomId, $userModel->userId),
                'userId' => $userModel->userId,
                'prettyId' => $userModel->prettyId,
                'userLevel' => $userModel->lvDengji,
                'nickName' => $userModel->nickname,
                'isVip' => $userModel->vipLevel,
                'bubble' => null,
            ],
            'items' => [
                'photoId' => $roomPhotoModel->photoId,
                'photoImage' => CommonUtil::buildImageUrl($roomPhotoModel->image),
                'giftId' => $giftKind->kindId,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
            ]
        ];
        $msgData['msg'] = json_encode($msg);
        $msgData['roomId'] = $roomId;
        $msgData['toUserId'] = '0';
        RoomNotifyService::getInstance()->notifyRoomMsg($roomId, $msgData);

        $redis->set(sprintf('room_photo_%d_%d', $roomId, $photoId), 1);
        $redis->expire(sprintf('room_photo_%d_%d', $roomId, $photoId), 60);
    }

    private function checkRemovePhoto($photoId) {
        $roomPhotoModel = RoomPhotoModelDao::getInstance()->loadPhoto($photoId);
        if ($roomPhotoModel == null) {
            throw new FQException('没有该图片', 500);
        }

        if ($roomPhotoModel->status == RoomPhotoModel::$STATUS_CHECKING) {
            throw new FQException('审核中的图片不能删除', 500);
        }
    }

    public function removePhotos($roomId, $userId, $photoIds){
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('此房间不存在', 500);
        }

        if ($roomModel->userId != $userId) {
            throw new FQException("只有房主才能删除照片", 500);
        }

        Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use($photoIds) {
            foreach ($photoIds as $photoId){
                $this->checkRemovePhoto($photoId);
            }
            RoomPhotoModelDao::getInstance()->removeRoomPhotos($photoIds);
        });
    }

    public function removeAllPhoto($roomId, $userId){
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('此房间不存在', 500);
        }

        if ($roomModel->userId != $userId) {
            throw new FQException("只有房主才能删除照片", 500);
        }

        Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use($roomId) {
            RoomPhotoModelDao::getInstance()->removeAllRoomPhoto($roomId);
        });
    }

    public function unlockPhoto($roomId, $userId, $micId, $photoId) {
        $roomPhotoModel = RoomPhotoModelDao::getInstance()->loadPhoto($photoId);
        if ($roomPhotoModel == null) {
            throw new FQException('没有该照片', 500);
        }

        if (RoomPhotoModelDao::getInstance()->isUnLockPhoto($roomId, $userId, $photoId)){
            throw new FQException('该照片已解锁', 500);
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($roomPhotoModel->giftId);
        if ($giftKind == null) {
            throw new FQException('没有该礼物', 500);
        }

        $receiveUsers = ReceiveUser::fromUserMicIdArray([$roomPhotoModel->userId], [$micId]);
        GiftService::getInstance()->sendGift($roomId, $userId, $receiveUsers, $giftKind, 1);
        RoomPhotoModelDao::getInstance()->addUnLockPhoto($roomId, $userId, $photoId);

    }
}