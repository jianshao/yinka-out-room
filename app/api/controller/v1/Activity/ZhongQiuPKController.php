<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\zhongqiuPK\ZhongQiuPKService;
use app\domain\activity\zhongqiuPK\ZhongQiuPKSystem;
use app\domain\activity\zhongqiuPK\ZhongQiuPKUserDao;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\user\model\UserModel;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;

class ZhongQiuPKController extends BaseController
{
    private function checkMToken() {
        $token = $this->request->param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            throw new FQException('用户信息错误', 500);
        }
        $userId = $redis->get($token);
        if (!$userId) {
            throw new FQException('用户信息错误', 500);
        }
        return $userId;
    }

    /**
     * 初始化
     * @return [type] [description]
     */
    public function init()
    {
        $userId = $this->checkMToken();

        $timestamp = time();
        $isExpire = ZhongQiuPKService::getInstance()->isExpire($timestamp);
        $startTime = TimeUtil::strToTime(ZhongQiuPKSystem::getInstance()->startTime);
        $pkUser = ZhongQiuPKUserDao::getInstance()->loadUser($userId, $timestamp);

        $data['userFaction'] = $pkUser->faction;
        #签到信息
        $i = 0;
        $checkinList = [];
        foreach (ZhongQiuPKSystem::getInstance()->checkins as $item){
            $assetKind = AssetSystem::getInstance()->findAssetKind($item['assetId']);
            $status = ArrayUtil::safeGet($pkUser->checkins, $i+1, 0);
            $checkinTime = $startTime+$i*86400;
            if ($status == 0 && $timestamp >= $checkinTime){
                $status = TimeUtil::isSameDay($timestamp, $checkinTime)?1:4;
            }
            $checkinList[] = [
                'date' => ZhongQiuPKService::getInstance()->buildDate($checkinTime),
                'name' => $assetKind->displayName,
                'image' => CommonUtil::buildImageUrl($assetKind->image),
                'count' => $item['count'],
                'status' => $isExpire?0:$status, #0-不可签 1-可签 2-已签 3-补签过了 4-补签可以签到
            ];

            $i+=1;
        }
        $data['checkinList'] = $checkinList;
        $data['retroactive'] = ZhongQiuPKSystem::getInstance()->retroactive;

        #杏花酒
        $giftId = AssetUtils::getGiftKindIdFromAssetId(ZhongQiuPKSystem::getInstance()->checkinReward['assetId']);
        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
        $data['checkinReward'] = [
            'name' => $giftKind->name,
            'image' => CommonUtil::buildImageUrl($giftKind->image),
            'price' => $giftKind->price?$giftKind->price->count:0,
            'status' => $isExpire?0:$pkUser->checkinStatus,
        ];

        $data['eggFaction'] = $this->getFactionRank($userId, ZhongQiuPKSystem::$eggFaction, $timestamp);
        $data['wuRenFaction'] = $this->getFactionRank($userId, ZhongQiuPKSystem::$wuRenFaction, $timestamp);
        $data['moonlightPool'] = ZhongQiuPKService::getInstance()->getMoonlightPool($timestamp);
        if (!$isExpire && $data['moonlightPool'] < ZhongQiuPKSystem::getInstance()->basePool){
            $data['moonlightPool'] = ZhongQiuPKSystem::getInstance()->basePool;
        }
        $data['ystdayRank'] = $this->getYstdayRank($timestamp);

        return rjsonFit($data);
    }

    public function getFactionRank($selfUserId, $faction, $timestamp) {
        $ret = [];
        $selfInfo = null;

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $factionTotalKey = ZhongQiuPKService::getInstance()->buildFactionTotalRankKey($date);
        $moonlightValue = intval($redis->hGet($factionTotalKey, $faction));
        $factionkey = ZhongQiuPKService::getInstance()->buildFactionRankKey($faction, $date);
        $rankList = $redis->zRevRange($factionkey, 0, 9, true);
        if (!empty($rankList)) {
            $userIds = array_keys($rankList);
            $userMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            $i = 0;
            foreach ($rankList as $userId => $score) {
                $userRank = $i + 1;
                $bean = ZhongQiuPKService::getInstance()->decodeScore($score);
                $data = [
                    'user' => $this->encodeUser($userMap[$userId]),
                    'rank' => $userRank,
                    'score' => $bean
                ];
                $ret[] = $data;

                if ($userId == $selfUserId){
                    $selfInfo = $data;
                }

                $i+=1;
            }
        }

        if ($selfInfo == null){
            $userModel = UserModelCache::getInstance()->getUserInfo($selfUserId);
            $score = intval($redis->zScore($factionkey, $selfUserId));
            $bean = ZhongQiuPKService::getInstance()->decodeScore($score);
            $selfInfo = [
                'user' => $this->encodeUser($userModel),
                'rank' => -1,
                'score' => $bean
            ];
        }

        $userCount = (int)$redis->sCard(ZhongQiuPKService::getInstance()->buildFactionUserKey($faction));
        return [
            'userCount' => $userCount,
            'moonlightValue' => $moonlightValue,
            'rankList' => $ret,
            'selfRank' => $selfInfo
        ];
    }

    public function getYstdayRank($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp-86400, '%Y%m%d');
        $key = ZhongQiuPKService::getInstance()->buildWinFactionKey($date);
        $data = $redis->hMGet($key, ['winFaction', 'totalPool', 'winFactionList']);
        $winInfo = $data['winFactionList'];

        $ret = [];
        if (!empty($winInfo)){
            $winInfo = json_decode($winInfo, true);

            $userIds = array_column($winInfo, 0);
            $userMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            $i = 0;
            foreach ($winInfo as $item) {
                $userRank = $i + 1;
                $ret[] = [
                    'user' => $this->encodeUser($userMap[$item[0]]),
                    'rank' => $userRank,
                    'bean' => $item[1]
                ];
                $i+=1;
            }
        }else{
            return [];
        }

        return [
            'date' => ZhongQiuPKService::getInstance()->buildDate($timestamp-86400),
            'winFaction' => $data['winFaction'],
            'totalPool' => intval($data['totalPool']),
            'rankList' => $ret,
        ];
    }

    private function encodeUser(UserModel $userModel){
        return [
            'userId' => $userModel->userId,
            'name' => $userModel->nickname,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar)
        ];
    }

    public function checkin(){
        $userId = $this->checkMToken();
        if (ZhongQiuPKService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $day = $this->request->param('day');
        $isBuQian = $this->request->param('isBuQian');

        $timestamp = time();
        ZhongQiuPKService::getInstance()->checkin($userId, $day, $isBuQian, $timestamp);

        return rjsonFit($msg="领取成功");
    }

    public function getCheckInReward(){
        $userId = $this->checkMToken();

        if (ZhongQiuPKService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $timestamp = time();
        $reward = ZhongQiuPKService::getInstance()->getCheckInReward($userId, $timestamp);

        $giftId = AssetUtils::getGiftKindIdFromAssetId($reward['assetId']);
        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);

        return rjsonFit([
            'name' => $giftKind->name,
            'image' => CommonUtil::buildImageUrl($giftKind->image),
            'price' => $giftKind->price?$giftKind->price->count:0,
            'count' => $reward['count']
        ]);
    }

    public function addFaction(){
        $userId = $this->checkMToken();
        if (ZhongQiuPKService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $faction = $this->request->param('faction');

        $timestamp = time();
        ZhongQiuPKService::getInstance()->addFaction($userId, $faction, $timestamp);

        return rjsonFit([
            "faction" => $faction
        ],200, "加入成功");
    }

}