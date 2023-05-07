<?php


namespace app\query\redpacket\service;


use app\common\RedisCommon;
use app\domain\redpacket\RedPacketDetailInfo;
use app\query\redpacket\dao\RedPacketDetailModelDao;
use app\domain\redpacket\RedPacketGetDetail;
use app\query\redpacket\dao\RedPacketModelDao;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;

class RedPacketService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketService();
        }
        return self::$instance;
    }

    public function buildGrabUsersKey($redPacketId) {
        return 'hongbao:grabusers:' . $redPacketId;
    }

    public function getRedPacketDetailInfo($redPacketId) {
        $redPacketModel = RedPacketModelDao::getInstance()->findById($redPacketId);
        if ($redPacketModel) {
            $detailModels = RedPacketDetailModelDao::getInstance()->getRedDetailModels($redPacketId);
            if (!empty($detailModels)) {
                $userIds = [];
                foreach ($detailModels as $detailModel){
                    $userIds[] = $detailModel->getUserId;
                }
                $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

                $getCount = count($detailModels);
                $getDetailList = [];
                foreach ($detailModels as $detailModel) {
                    $userModel = ArrayUtil::safeGet($userModelMap, $detailModel->getUserId);
                    if (empty($userModel)){
                        continue;
                    }

                    $getDetail = new RedPacketGetDetail();
                    $getDetail->id = $detailModel->id;
                    $getDetail->redPacketId = $detailModel->redPacketId;
                    $getDetail->getUserId = $detailModel->getUserId;
                    $getDetail->getTime = $detailModel->getTime;
                    $getDetail->beanCount = $detailModel->beanCount;
                    $getDetail->isGet = $detailModel->isGet;
                    $getDetail->createTime = $detailModel->createTime;
                    $getDetail->updateTime = $detailModel->updateTime;
                    $getDetail->getUserAvatar = $userModel->avatar;
                    $getDetail->getUserNickname = $userModel->nickname;
                    $getDetailList[] = $getDetail;
                }
                return new RedPacketDetailInfo($redPacketModel->count, $getCount, $getDetailList);
            }
        }
        return null;
    }

    public function getRoomRedPacketCount($userId, $roomId)
    {
        $redPacketModels = RedPacketModelDao::getInstance()->findByRoomId($roomId);
        if (empty($redPacketModels)) {
            return [0, 0];
        }

        $canGrabData = null;
        $count = 0;
        $end = 0;
        $redis = RedisCommon::getInstance()->getRedis();
        foreach ($redPacketModels as $redPacketModel) {
            $grabKey = $this->buildGrabUsersKey($redPacketModel->id);
            $isGrab = $redis->sIsMember($grabKey, $userId);
            if (!$isGrab) {
                $count += 1;
                if ($canGrabData == null) {
                    $canGrabData = RedPacketModelDao::getInstance()->modelToData($redPacketModel);
                }
            }
        }

        if ($canGrabData != null) {
            $end = max(0, $canGrabData['send_time'] + $canGrabData['count_down'] - time());
        }

        return [$count, $end];
    }

}