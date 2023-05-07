<?php


namespace app\domain\activity\guoqing;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\forum\dao\ForumModelDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\UserRepository;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class GuoQingService
{
    protected static $instance;
    public static $FORUM_TOPIC_ID = 77;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GuoQingService();
        }
        return self::$instance;
    }

    public function buildKey(){
        return "guoqing_pool";
    }

    public function buildRankKey($date){
        return sprintf('guoqing_rank:%s', $date);
    }

    public function buildWinKey($date){
        return sprintf('guoqing_win:%s', $date);
    }

    public function onForumCheckPassEvent($event){

        $timestamp = time();

        Log::info(sprintf('GuoQingService.onForumCheckPassEvent userId=%d forumId=%d topicId=%d',
            $event->userId, $event->forumId, $event->topicId));

        $forumModel = ForumModelDao::getInstance()->loadForumModel($event->forumId);
        if (empty($forumModel) || $forumModel->forumUid != $event->userId || $forumModel->tid != self::$FORUM_TOPIC_ID){
            return;
        }

        if ($this->isExpire($forumModel->createTime)){
            return;
        }

        $guoqingUser = GuoQingUserDao::getInstance()->loadUser($event->userId, $timestamp);
        if ($guoqingUser->forumStatus == 1){
            return;
        }

        try {
            Sharding::getInstance()->getConnectModel('userMaster', $event->userId)->transaction(function () use($event, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($event->userId);

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'guoqing', 'forum');

                $reward = GuoQingSystem::getInstance()->forumReward;
                if (!empty($reward)) {
                    $user->getAssets()->add($reward['assetId'], $reward['count'], $timestamp, $biEvent);
                }

                Log::info(sprintf('GuoQingService.onForumCheckPassEvent ok userId=%d reward=%s:%s',
                    $event->userId, $reward['assetId'], $reward['count']));
            });
        } catch (Exception $e) {
            throw $e;
        }

        $guoqingUser->forumStatus = 1;
        GuoQingUserDao::getInstance()->saveUser($guoqingUser);

    }

    public function getBoxReward($userId, $boxId, $timestamp){
        $box = GuoQingSystem::getInstance()->findBox($boxId);
        if (empty($box)){
            throw new FQException("参数错误",500);
        }

        $guoqingUser = GuoQingUserDao::getInstance()->loadUser($userId, $timestamp);
        if (in_array($boxId, $guoqingUser->boxs)){
            throw new FQException("您已领取过该奖励",500);
        }

        $energy = $this->getEnergy($userId, $timestamp);
        if ($energy < $box->energy){
            throw new FQException("能量不足不可领取",500);
        }

        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $box, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'guoqing', 'box');

                foreach ($box->rewards as $reward){
                    $user->getAssets()->add($reward->assetId, $reward->count, $timestamp, $biEvent);
                }

                Log::info(sprintf('GuoQingService.getBoxReward ok userId=%d rewards=%s',
                    $userId, json_encode($box->rewards)));
            });
        } catch (Exception $e) {
            throw $e;
        }

        $guoqingUser->boxs[] = $boxId;
        GuoQingUserDao::getInstance()->saveUser($guoqingUser);
        return $box->rewards;
    }

    public function addPool($fromUserId, $sendDetails){
        $giftCoin = 0;
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, GuoQingSystem::getInstance()->giftIds)) {
                    $giftCoin += $giftDetail->count * $giftDetail->giftKind->price->count;
                }
            }
        }

        Log::info(sprintf('GuoQingService.addPool fromUserId=%d, giftCoin=%d',
            $fromUserId, $giftCoin));

        if ($giftCoin == 0){
            return;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr(time(), '%Y%m%d');
        $key = $this->buildKey();
        $add = intval($giftCoin*GuoQingSystem::getInstance()->rate);
        if ($redis->hGet($key, $date) < GuoQingSystem::getInstance()->basePool){
            $add += GuoQingSystem::getInstance()->basePool;
        }
        $redis->hIncrBy($key, $date, $add);

    }

    public function delEnergyPool($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildKey();
        return $redis->hDel($key, $date);
    }

    public function getEnergyPool($timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildKey();
        return intval($redis->hGet($key, $date));
    }

    public function addEnergyValue($userId, $sendDetails, $timestamp){

        #加能量值的人
        $selfGiftCount = 0;
        $addUserMap = [];
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, GuoQingSystem::getInstance()->giftIds)){
                    $giftCount = $giftDetail->count * $giftDetail->giftKind->price->count;
                    if ($receiveUser->userId!=$userId){
                        $selfGiftCount = $giftCount;
                        if (array_key_exists($receiveUser->userId, $addUserMap)) {
                            $addUserMap[$receiveUser->userId] += $giftCount;
                        }else{
                            $addUserMap[$receiveUser->userId] = $giftCount;
                        }
                    }else{
                        $selfGiftCount = $giftCount*2;
                    }
                }
            }
        }

        if ($selfGiftCount > 0){
            $addUserMap[$userId] = $selfGiftCount;
        }

        Log::info(sprintf('GuoQingService.addEnergyValue userId=%d addUserMap=%s',
            $userId, json_encode($addUserMap)));

        if (empty($addUserMap)){
            return;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        foreach ($addUserMap as $userId => $giftCount){
            try {
                $key = $this->buildRankKey($date);
                $score = $redis->zIncrBy($key, $this->encodeScore($giftCount, $timestamp), $userId);
                $score = $this->decodeScore(intval($score));
                $redis->zAdd($key, $this->encodeScore($score, $timestamp, 1), $userId);
            }catch (Exception $e) {
                Log::error(sprintf('GuoQingService.addEnergyValue userId=%d giftCount=%d file=%s:%d',
                    $userId, $giftCount, $e->getFile(), $e->getLine()));
            }
        }
    }

    public function getEnergy($userId, $timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildRankKey($date);
        $score = $redis->zScore($key, $userId);
        return intval($this->decodeScore($score));
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
            Log::error(sprintf('GuoQingService addPool Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        try {
            $this->addEnergyValue($event->fromUserId, $event->sendDetails, $event->timestamp);
        }catch (Exception $e) {
            Log::error(sprintf('GuoQingService addEnergyValue Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function carveupPool($timestamp){
        try {

            if ($this->isExpire($timestamp)){
                return;
            }
            $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
            $energyPool = $this->getEnergyPool($timestamp);
            if ($energyPool <= 0){
                return;
            }

            $startTime = TimeUtil::strToTime(GuoQingSystem::getInstance()->startTime);
            #$timestamp是前一天的时间
            $boxId = TimeUtil::calcDiffDays($startTime, $timestamp)+1;
            $box = GuoQingSystem::getInstance()->findBox($boxId);
            if (empty($box)){
                Log::error(sprintf('GuoQingService.carveupPool date=%s energyPool=%d boxId=%d',
                    $date, $energyPool, $boxId));
                return;
            }
            $redis = RedisCommon::getInstance()->getRedis();
            $winRankList = $redis->zRevRange($this->buildRankKey($date), 0, 9, true);

            Log::info(sprintf('GuoQingService.carveupPool date=%s energyPool=%d winRankList=%s',
                $date, $energyPool, json_encode($winRankList)));

            $winInfo = [];
            $i = 0;
            foreach ($winRankList as $userId => $score){
                $bean = intval($energyPool*GuoQingSystem::getInstance()->poolRates[$i]);
                $this->carveupPoolImpl($userId, $bean, $timestamp);
                $i+=1;

                $winInfo[] = [$userId, $bean];
                $msg = sprintf('恭喜你在%s的%s能量比拼中获得了第%d名，奖励%d音豆已加入背包！',
                    $this->buildDate($timestamp), $box->name, $i, $bean);
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ["msg" => $msg]]);
                Log::info(sprintf("GuoQingService.carveupPool result userId=%d resMsg=%s", $userId, $resMsg));
            }

            $this->delEnergyPool($timestamp);

            $key = $this->buildWinKey($date);
            $redis->hMSet($key, [
                'totalPool' => $energyPool,
                'winList' => json_encode($winInfo)
            ]);

        }catch (Exception $e) {
            Log::error(sprintf('GuoQingService.carveupPool date=%s ex=%d:%s trace=%s',
                $date, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function buildDate($timestamp){
        return sprintf('%d月%d日', date('m', $timestamp), date('d', $timestamp));
    }

    public function carveupPoolImpl($userId, $bean, $timestamp){
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $bean, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'guoqing', 'pk');

                if ($bean > 0) {
                    $user->getAssets()->add(AssetKindIds::$BEAN, $bean, $timestamp, $biEvent);
                }

                Log::info(sprintf('GuoQingService.carveupPoolImpl ok userId=%d reward=%d',
                    $userId, $bean));
            });
        } catch (Exception $e) {
            Log::error(sprintf('GuoQingService userId=%d bean=%d timestamp=%d trace=%s',
                $userId, $bean, $timestamp, $e->getTraceAsString()));
        }
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