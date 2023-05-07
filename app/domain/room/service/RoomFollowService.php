<?php


namespace app\domain\room\service;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomFollowModelDao;
use app\domain\room\dao\RoomModelDao;
use app\event\RoomAttentionEvent;
use think\facade\Log;

class RoomFollowService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomFollowService();
        }
        return self::$instance;
    }

    public function attentionRoom($roomId, $userId) {
        if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
            throw new FQException('当前房间不存在', 500);
        }

        $data = RoomFollowModelDao::getInstance()->loadFollow($roomId, $userId);
        if (!empty($data)) {
            throw new FQException('已关注该房间', 500);
        }

        $timestamp = time();
        try {
            RoomFollowModelDao::getInstance()->addFollow($roomId, $userId, $timestamp);
            Log::info(sprintf('RoomFollowService::attentionRoom ok userId=%d roomId=%d',
                $userId, $roomId));
            event(new RoomAttentionEvent($userId, $roomId, $timestamp));
        } catch (\Exception $e) {
            throw new FQException('已关注该房间', 500);
        }
    }

    public function cancelAttentionRoom($roomId, $userId) {
        if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
            throw new FQException('当前房间不存在', 500);
        }

        $data = RoomFollowModelDao::getInstance()->loadFollow($roomId, $userId);

        if (empty($data)) {
            throw new FQException('还没有关注该房间', 500);
        }

        RoomFollowModelDao::getInstance()->delFollow($roomId, $userId);

        Log::info(sprintf('RoomFollowService::cancelAttentionRoom ok userId=%d roomId=%d',
            $userId, $roomId));
    }
}