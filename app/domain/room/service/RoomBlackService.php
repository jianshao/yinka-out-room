<?php


namespace app\domain\room\service;


use app\common\RedisCommon;
use app\domain\duke\dao\DukeModelDao;
use app\domain\duke\DukeSystem;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomBlackModelDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\model\RoomBlackModel;
use app\domain\user\dao\UserModelDao;
use app\event\RoomBanUserEvent;
use app\event\RoomBlackUserEvent;
use think\facade\Log;

class RoomBlackService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomBlackService();
        }
        return self::$instance;
    }

    public function addBanUser($roomId, $banUserId, $longTime, $opUserId) {
        $roomData = RoomModelDao::getInstance()->loadRoomData($roomId);
        if (empty($roomData)) {
            throw new FQException('此房间不存在', 500);
        }

        if ($roomData['user_id'] == $banUserId) {
            throw new FQException('权限不足', 500);
        }

        if (!UserModelDao::getInstance()->isUserIdExists($banUserId)) {
            throw new FQException('此用户不存在', 500);
        }

        if ($opUserId != $roomData['user_id']) {
            $this->authPermission($roomId, $opUserId);
        }

        $timestamp = time();
        $dukeModel = DukeModelDao::getInstance()->loadDuke($banUserId);
        DukeSystem::getInstance()->adjustDuke($dukeModel, $timestamp);
        if ($dukeModel->dukeLevel >= 5) {
            throw new FQException('该用户为国王身份无法禁言', 500);
        }

        $existsBan=RoomBlackModelDao::getInstance()->existsForRoomUserType($roomId,$banUserId,2);

        if (!empty($existsBan)) {
            throw new FQException('用户已经被禁言', 500);
        }

        $model = new RoomBlackModel;
        $model->roomId = $roomId;
        $model->userId = $banUserId;
        $model->ctime = $timestamp;
        $model->longTime = $longTime;
        $model->type = 2;
        RoomBlackModelDao::getInstance()->storeModel($model);


        //缓存禁言
        $key = 'room_user_disable_msg_' . $roomId . '_' . $banUserId;
        $redis = RedisCommon::getInstance()->getRedis();
        if ($longTime < 0) {
            $redis->set($key,1);
        } else {
            $redis->setex($key, $longTime,1);
        }

        Log::info(sprintf('RoomBlackService::addBanUser ok banUserId=%d roomId=%d longTime=%d opUserId=%d',
            $banUserId, $roomId, $longTime, $opUserId));

        event(new RoomBanUserEvent($banUserId, $roomId, $longTime, true, $opUserId, $roomData['user_id'], $timestamp));
    }

    public function removeBanUser($roomId, $banUserId, $opUserId) {
        $roomData = RoomModelDao::getInstance()->loadRoomData($roomId);
        if (empty($roomData)) {
            throw new FQException('此房间不存在', 500);
        }

        if ($roomData['user_id'] == $banUserId) {
            throw new FQException('权限不足', 500);
        }

        if ($opUserId != $roomData['user_id']) {
            $this->authPermission($roomId, $opUserId);
        }

        $existsBan=RoomBlackModelDao::getInstance()->existsForRoomUserType($roomId,$banUserId,2);

        if (empty($existsBan)) {
            throw new FQException('此用户未被禁言', 500);
        }

        RoomBlackModelDao::getInstance()->removeForRoomUserType($roomId,$banUserId,2);

        //缓存踢出
        $blackKey = 'room_user_disable_msg_' . $roomId . '_' . $banUserId;
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del($blackKey);

        Log::info(sprintf('RoomBlackService::removeBanUser ok banUserId=%d roomId=%d opUserId=%d',
            $banUserId, $roomId, $opUserId));

        event(new RoomBanUserEvent($banUserId, $roomId, 0, false, $opUserId, $roomData['user_id'], time()));
    }

    public function addBlackUser($roomId, $blackUserId, $longTime, $opUserId) {
        $roomData = RoomModelDao::getInstance()->loadRoomData($roomId);
        if (empty($roomData)) {
            throw new FQException('此房间不存在', 500);
        }

        if ($roomData['user_id'] == $blackUserId) {
            throw new FQException('权限不足', 500);
        }

        if (!UserModelDao::getInstance()->isUserIdExists($blackUserId)) {
            throw new FQException('此用户不存在', 500);
        }

        $timestamp = time();

        $redis = RedisCommon::getInstance()->getRedis();
        $isInvisUser = $redis->sIsMember('invis_user', $opUserId);

        if (!$isInvisUser) {
            $dukeModel = DukeModelDao::getInstance()->loadDuke($blackUserId);
            DukeSystem::getInstance()->adjustDuke($dukeModel, $timestamp);
            if ($dukeModel->dukeLevel >= 5) {
                throw new FQException('该用户为国王身份无法踢出房间', 500);
            }
        }

        if ($opUserId != $roomData['user_id']) {
            $this->authPermission($roomId, $opUserId);
        }

        $existsBlack=RoomBlackModelDao::getInstance()->existsForRoomUserType($roomId,$blackUserId,1);

        if (!empty($existsBlack)) {
            throw new FQException('该用户已在黑名单', 500);
        }

        $model = new RoomBlackModel;
        $model->roomId = $roomId;
        $model->userId = $blackUserId;
        $model->ctime = $timestamp;
        $model->longTime = $longTime;
        $model->type = 1;
        RoomBlackModelDao::getInstance()->storeModel($model);

        $blackKey = 'room_user_kickout_' . $roomId . '_' . $blackUserId;

        if ($longTime < 0) {
            $redis->SET($blackKey, 1);
        } else {
            $redis->SETEX($blackKey, $longTime, 1);
        }

        Log::info(sprintf('RoomBlackService::addBlackUser ok blackUserId=%d roomId=%d longTime=%d opUserId=%d',
            $blackUserId, $roomId, $longTime, $opUserId));

        event(new RoomBlackUserEvent($blackUserId, $roomId, $longTime, $opUserId, $roomData['user_id'], $timestamp));
    }

    public function removeBlackUser($roomId, $blackUserId, $opUserId)
    {
        $roomData = RoomModelDao::getInstance()->loadRoomData($roomId);

        if (empty($roomData)) {
            throw new FQException('此房间不存在', 500);
        }

        if (!UserModelDao::getInstance()->isUserIdExists($blackUserId)) {
            throw new FQException('此用户不存在', 500);
        }

        if ($opUserId != $roomData['user_id']) {
            //判断当前用户是否为管理员房主
            $this->authPermission($roomId, $opUserId);
        }

        RoomBlackModelDao::getInstance()->removeForRoomUserType($roomId, $blackUserId, 1);

        //缓存踢出
        $blackKey = 'room_user_kickout_' . $roomId . '_' . $blackUserId;
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del($blackKey);

        Log::info(sprintf('RoomBlackService::removeBlackUser ok blackUserId=%d roomId=%d opUserId=%d',
            $blackUserId, $roomId, $opUserId));
    }

    /**
     * @param $roomId
     * @param $opUserId
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function authPermission($roomId, $opUserId)
    {
        if (empty($roomId) || empty($opUserId)) {
            throw new FQException("用户房间信息异常请检查", 500);
        }
        $adminUser = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $opUserId);
        if ($adminUser === null) {
            throw new FQException('该用户权限不足无法操作', 500);
        }
    }
}