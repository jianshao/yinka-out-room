<?php


namespace app\domain\activity\zhongqiuPK;

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class ZhongQiuPKService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhongQiuPKService();
        }
        return self::$instance;
    }

    public function buildKey(){
        return "zhongqiupk_pool";
    }

    public function buildFactionRankKey($faction, $date){
        return sprintf('zhongqiupk_faction_rank:%s:%s', $faction, $date);
    }

    public function buildFactionUserKey($faction){
        return sprintf('zhongqiupk_faction_user:%s', $faction);
    }

    public function buildWinFactionKey($date){
        return sprintf('zhongqiupk_win_faction:%s', $date);
    }

    public function buildFactionTotalRankKey($date){
        return sprintf('zhongqiupk_total_rank:%s', $date);
    }

    public function checkin($userId, $day, $isBuQian, $timestamp){
        $checkinCount = count(ZhongQiuPKSystem::getInstance()->checkins);
        if ($day <= 0 || $day > $checkinCount){
            throw new FQException("参数错误",500);
        }

        $pkuser = ZhongQiuPKUserDao::getInstance()->loadUser($userId, $timestamp);
        if (array_key_exists($day, $pkuser->checkins)){
            throw new FQException("您已签到",500);
        }

//        Db::startTrans();
//        try {
//            $user = UserRepository::getInstance()->loadUser($userId);
//
//            $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'zhongqiupk', 'checkin', $day);
//            if ($isBuQian){
//                $retroactive = ZhongQiuPKSystem::getInstance()->retroactive;
//                if ($user->getAssets()->balance(AssetKindIds::$BEAN, $timestamp) < $retroactive){
//                    throw new FQException("音豆不足，不可以补签",500);
//                }
//                $user->getAssets()->consume(AssetKindIds::$BEAN, $retroactive, $timestamp, $biEvent);
//            }
//
//            $reward = ZhongQiuPKSystem::getInstance()->getCheckInReward($day);
//            if (!empty($reward)) {
//                $user->getAssets()->add($reward['assetId'], $reward['count'], $timestamp, $biEvent);
//            }
//
//            Log::info(sprintf('ZhongQiuPKService.checkin ok userId=%d day=%d isBuQian=%d reward=%s:%s',
//                $userId, $day, $isBuQian, $reward['assetId'], $reward['count']));
//            Db::commit();
//
//        } catch (Exception $e) {
//            Db::rollback();
//            throw $e;
//        }

        $pkuser->checkins[$day] = $isBuQian?3:2;
        if (count($pkuser->checkins) == $checkinCount){
            $pkuser->checkinStatus = 1;
        }
        ZhongQiuPKUserDao::getInstance()->saveUser($pkuser);
        return $reward;
    }

    public function getCheckInReward($userId, $timestamp){
        $pkuser = ZhongQiuPKUserDao::getInstance()->loadUser($userId, $timestamp);
        if ($pkuser->checkinStatus == 2){
            throw new FQException("您已领取",500);
        }elseif ($pkuser->checkinStatus == 0){
            throw new FQException("您未达到领取条件",500);
        }

//        Db::startTrans();
//        try {
//            $user = UserRepository::getInstance()->loadUser($userId);
//
//            $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'zhongqiupk', 'checkin');
//
//            $reward = ZhongQiuPKSystem::getInstance()->checkinReward;
//            if (!empty($reward)) {
//                $user->getAssets()->add($reward['assetId'], $reward['count'], $timestamp, $biEvent);
//            }
//
//            Log::info(sprintf('ZhongQiuPKService.getCheckInReward ok userId=%d reward=%d:%s',
//                $userId, $reward['assetId'], $reward['count']));
//            Db::commit();
//
//        } catch (Exception $e) {
//            Db::rollback();
//            throw $e;
//        }

        $pkuser->checkinStatus = 2;
        ZhongQiuPKUserDao::getInstance()->saveUser($pkuser);
        return $reward;
    }

    public function addFaction($userId, $faction, $timestamp){

        if (!in_array($faction, [ZhongQiuPKSystem::$eggFaction, ZhongQiuPKSystem::$wuRenFaction])){
            throw new FQException("参数错误",500);
        }

        $user = ZhongQiuPKUserDao::getInstance()->loadUser($userId, $timestamp);
        if (!empty($user->faction)){
            throw new FQException("您已有帮派，不可加入",500);
        }

        $user->faction = $faction;
        ZhongQiuPKUserDao::getInstance()->saveUser($user);

        $redis = RedisCommon::getInstance()->getRedis();
        $redis->sAdd($this->buildFactionUserKey($faction), $userId);
    }

    public function addPool($fromUserId, $sendDetails){
        $giftCoin = 0;
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, ZhongQiuPKSystem::getInstance()->giftIds)) {
                    $giftCoin += $giftDetail->count * $giftDetail->giftKind->price->count;
                }
            }
        }

        Log::info(sprintf('ZhongQiuPKService.addPool fromUserId=%d, giftCoin=%d',
            $fromUserId, $giftCoin));

        if ($giftCoin == 0){
            return;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr(time(), '%Y%m%d');
        $key = $this->buildKey();
        $add = intval($giftCoin*ZhongQiuPKSystem::getInstance()->rate);
        if ($redis->hGet($key, $date) < ZhongQiuPKSystem::getInstance()->basePool){
            $add += ZhongQiuPKSystem::getInstance()->basePool;
        }
        $redis->hIncrBy($key, $date, $add);

    }

    public function delMoonlightPool($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildKey();
        return $redis->hDel($key, $date);
    }

    public function getMoonlightPool($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildKey();
        return intval($redis->hGet($key, $date));
    }

    public function addMoonlightValue($userId, $sendDetails, $timestamp){

        #加月光值的人
        $addUserMap = [];
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, ZhongQiuPKSystem::getInstance()->giftIds)){
                    if (array_key_exists($receiveUser->userId, $addUserMap)) {
                        $addUserMap[$receiveUser->userId] += $giftDetail->count * $giftDetail->giftKind->price->count;
                    }else{
                        $addUserMap[$receiveUser->userId] = $giftDetail->count * $giftDetail->giftKind->price->count;
                    }
                }
            }
        }

        if (empty($addUserMap)){
            return;
        }

        Log::info(sprintf('ZhongQiuPKService.addMoonlightValue userId=%d addUserMap=%s',
            $userId, json_encode($addUserMap)));

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        foreach ($addUserMap as $userId => $giftCount){
            try {
                $user = ZhongQiuPKUserDao::getInstance()->loadUser($userId, $timestamp);
                if (empty($user->faction)){
                    continue;
                }

                $key = $this->buildFactionRankKey($user->faction, $date);
                $score = $redis->zIncrBy($key, $this->encodeScore($giftCount, $timestamp), $userId);
                $score = $this->decodeScore(intval($score));
                $redis->zAdd($key, $this->encodeScore($score, $timestamp, 1), $userId);
                $redis->hIncrBy($this->buildFactionTotalRankKey($date), $user->faction, $giftCount);
            }catch (Exception $e) {
                Log::error(sprintf('ZhongQiuPKService.addMoonlightValue userId=%d giftCount=%d file=%s:%d',
                    $userId, $giftCount, $e->getFile(), $e->getLine()));
            }
        }
    }

    private function encodeScore($bean, $timestamp, $addTime=0){
        #豆偏移
        $beanPower = 10000000000;
        #最大时间
        $MAX_TIME = 9999999999;
        return $bean*$beanPower + ($addTime ? $MAX_TIME-$timestamp:0);
    }

    public function decodeScore($score){
        #豆偏移
        $beanPower = 10000000000;
        return intval($score/$beanPower);
    }

    public function onSendGiftEvent($event){
        if ($this->isExpire()){
            return;
        }

        try {
            $this->addPool($event->fromUserId, $event->sendDetails);
        }catch (Exception $e) {
            Log::error(sprintf('ZhongQiuPKService addPoolException userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        try {
            $this->addMoonlightValue($event->fromUserId, $event->sendDetails, $event->timestamp);
        }catch (Exception $e) {
            Log::error(sprintf('ZhongQiuPKService addMoonlightValue Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function carveupPool($timestamp){
        try {

            if ($this->isExpire($timestamp)){
                return;
            }

            $moonlightPool = $this->getMoonlightPool($timestamp);
            $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
            Log::info(sprintf('ZhongQiuPKService.carveupPool date=%s moonlightPool=%d',
                $date, $moonlightPool));
            if ($moonlightPool <= 0){
                return;
            }

            $redis = RedisCommon::getInstance()->getRedis();

            $totalKey = $this->buildFactionTotalRankKey($date);
            $eggRankCount = (int)$redis->hGet($totalKey, ZhongQiuPKSystem::$eggFaction);
            $wurenRankCount = (int)$redis->hGet($totalKey, ZhongQiuPKSystem::$wuRenFaction);

            if ($eggRankCount >= $wurenRankCount){
                $eggFactionkey = $this->buildFactionRankKey(ZhongQiuPKSystem::$eggFaction, $date);
                $winRankList = $redis->zRevRange($eggFactionkey, 0, 9, true);
                $winFaction = ZhongQiuPKSystem::$eggFaction;
            }else{
                $wurenFactionkey = $this->buildFactionRankKey(ZhongQiuPKSystem::$wuRenFaction, $date);
                $winRankList = $redis->zRevRange($wurenFactionkey, 0, 9, true);
                $winFaction = ZhongQiuPKSystem::$wuRenFaction;
            }

            Log::info(sprintf('ZhongQiuPKService.carveupPool eggRankCount=%d wurenRankCount=%d date=%s moonlightPool=%d winRankList=%s',
                $eggRankCount, $wurenRankCount, $date, $moonlightPool, json_encode($winRankList)));

            $winInfo = [];
            $i = 0;
            foreach ($winRankList as $userId => $score){
                $bean = intval($moonlightPool*ZhongQiuPKSystem::getInstance()->poolRates[$i]);
                $this->carveupPoolImpl($userId, $bean, $timestamp);
                $i+=1;

                $winInfo[] = [$userId, $bean];
                $msg = sprintf('恭喜你在%s的帮派比拼中加入的%s派中获得了第%d名，奖励%d音豆已加入背包！',
                    $this->buildDate($timestamp), $this->buildFaction($winFaction), $i, $bean);
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ["msg" => $msg]]);
                Log::info(sprintf("ZhongQiuPKService.carveupPool result userId=%d resMsg=%s", $userId, $resMsg));
            }

            $this->delMoonlightPool($timestamp);

            $key = $this->buildWinFactionKey($date);
            $redis->hMSet($key, [
                'winFaction' => $winFaction,
                'totalPool' => $moonlightPool,
                'winFactionList' => json_encode($winInfo)
            ]);

        }catch (Exception $e) {
            Log::error(sprintf('ZhongQiuPKService carveupPool date=%s ex=%d:%s trace=%s',
                $date, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function buildDate($timestamp){
        return sprintf('%d月%d日', date('m', $timestamp), date('d', $timestamp));
    }

    public function buildFaction($faction){
        $factions = [
            ZhongQiuPKSystem::$wuRenFaction => "伍仁",
            ZhongQiuPKSystem::$eggFaction => "蛋黄",
        ];
        return ArrayUtil::safeGet($factions, $faction);
    }

    public function carveupPoolImpl($userId, $bean, $timestamp){
//        Db::startTrans();
//        try {
//            $user = UserRepository::getInstance()->loadUser($userId);
//
//            $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'zhongqiupk', 'pk');
//
//            if ($bean > 0) {
//                $user->getAssets()->add(AssetKindIds::$BEAN, $bean, $timestamp, $biEvent);
//            }
//
//            Log::info(sprintf('ZhongQiuPKService.carveupPoolImpl ok userId=%d reward=%d',
//                $userId, $bean));
//            Db::commit();
//
//        } catch (Exception $e) {
//            Db::rollback();
//            Log::error(sprintf('carveupPoolImpl userId=%d bean=%d timestamp=%d trace=%s',
//                $userId, $bean, $timestamp, $e->getTraceAsString()));
//        }
    }

    public function isExpire($timestamp=null){
        $config = Config::loadConf();
        $timestamp = $timestamp==null ? time(): $timestamp;
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

}