<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\guoqing\GuoQingService;
use app\domain\activity\guoqing\GuoQingSystem;
use app\domain\activity\guoqing\GuoQingUserDao;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use app\domain\user\model\UserModel;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;
use app\utils\TimeUtil;

class GuoQingController extends BaseController
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

        $curTimestamp = time();
        $stopTime = TimeUtil::strToTime(GuoQingSystem::getInstance()->stopTime);
        if ($curTimestamp > $stopTime+86400){
            throw new FQException("活动已过期",500);
        }

        $isExpire = GuoQingService::getInstance()->isExpire($curTimestamp);
        $startTime = TimeUtil::strToTime(GuoQingSystem::getInstance()->startTime);
        $guoqingUser = GuoQingUserDao::getInstance()->loadUser($userId, time());
        #签到信息
        $i = 0;
        $curBox = null;
        $boxList = [];
        foreach (GuoQingSystem::getInstance()->boxs as $box){
            $timestamp = $startTime+$i*86400;
            if (TimeUtil::isSameDay($curTimestamp, $timestamp) || $isExpire){
                $curBox = $box;
            }
            $boxList[] = [
                'boxId' => $box->boxId,
                'date' =>GuoQingService::getInstance()->buildDate($startTime+$i*86400),
                'energy' => $box->energy,
                'name' => $box->name
            ];
            $i+=1;
        }

        $curEnergy = GuoQingService::getInstance()->getEnergy($userId, $curTimestamp);
        $data['startTime'] = GuoQingSystem::getInstance()->startTime;
        $data['stopTime'] = GuoQingSystem::getInstance()->stopTime;
        $data['curBoxId'] = $curBox?$curBox->boxId:0;
        $data['totalEnergy'] = $curBox?$curBox->energy:0;
        $data['curEnergy'] = min($curEnergy, $data['totalEnergy']);
        $data['status'] = in_array($data['curBoxId'], $guoqingUser->boxs)?2:(!$isExpire&&$data['curEnergy'] >= $data['totalEnergy']?1:0);
        $data['boxList'] = $boxList;
        $data['todayRank'] = $this->getTodayRank($userId, $curTimestamp, $isExpire);

        $data['statements'] = [];
        if ($curBox != null){
            $curBoxId = $isExpire ? 8 : $curBox->boxId;
            for ($i = 1; $i < $curBoxId; $i++) {
                $timestamp = $startTime+($i-1)*86400;
                $ret = $this->getStatements($timestamp);
                if (!empty($ret)){
                    $data['statements'][$i] = $ret;
                }
            }
        }

        if (empty($data['statements'])){
            $data['statements'] = (object)array();
        }

        return rjsonFit($data);
    }

    public function getTodayRank($selfUserId, $timestamp, $isExpire) {
        $ret = [];
        $selfInfo = null;

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $rankKey = GuoQingService::getInstance()->buildRankKey($date);
        $rankList = $redis->zRevRange($rankKey, 0, 9, true);
        if (!empty($rankList)) {
            $userIds = array_keys($rankList);
            $userMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            $i = 0;
            foreach ($rankList as $userId => $score) {
                $userRank = $i + 1;
                $bean = GuoQingService::getInstance()->decodeScore($score);
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
            $score = intval($redis->zScore($rankKey, $selfUserId));
            $bean = GuoQingService::getInstance()->decodeScore($score);
            $selfInfo = [
                'user' => $this->encodeUser($userModel),
                'rank' => -1,
                'score' => $bean
            ];
        }

        $energyPool = GuoQingService::getInstance()->getEnergyPool($timestamp);
        if (!$isExpire && $energyPool < GuoQingSystem::getInstance()->basePool){
            $energyPool = GuoQingSystem::getInstance()->basePool;
        }
        return [
            'energyPool' => $energyPool,
            'rankList' => $ret,
            'selfRank' => $selfInfo
        ];
    }

    public function getStatements($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = GuoQingService::getInstance()->buildWinKey($date);
        $data = $redis->hMGet($key, ['totalPool', 'winList']);
        $winInfo = $data['winList'];

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

    public function getBoxReward(){
        $userId = $this->checkMToken();
        if (GuoQingService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $boxId = $this->request->param('boxId');

        $timestamp = time();
        $rewards = GuoQingService::getInstance()->getBoxReward($userId, $boxId, $timestamp);

        $ret = [];
        foreach ($rewards as $reward){
            $assetKind = AssetSystem::getInstance()->findAssetKind($reward->assetId);
            $data = [
                "name" => $assetKind->displayName,
                "image" => CommonUtil::buildImageUrl($assetKind->image),
                "count" => $reward->count,
                "price" => 0
            ];

            if (AssetUtils::isGiftAsset($reward->assetId)){
                $data['price'] = $assetKind->giftKind->price?$reward->count*$assetKind->giftKind->price->count:0;
            }

            $ret[] = $data;
        }

        return rjsonFit($ret);
    }

}