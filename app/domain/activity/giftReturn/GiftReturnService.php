<?php


namespace app\domain\activity\giftReturn;

use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\service\LockService;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class GiftReturnService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GiftReturnService();
        }
        return self::$instance;
    }

    public function getReward($userId, $timestamp){
        $giftUser = GiftReturnUserDao::getInstance()->loadUser($userId, $timestamp);
        if ($giftUser->gotReward == 2){
            throw new FQException("您已领取过奖励",500);
        }

        if ($giftUser->beanCount <= 0){
            throw new FQException("您没有奖励可领取",500);
        }

        $config = Config::loadConf();
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $giftUser, $config, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);

                $giftUser->gotReward = 2;
                GiftReturnUserDao::getInstance()->saveUser($giftUser);

                $rewardCount = intval($giftUser->beanCount*$config['rate']);
                if ($rewardCount > 0) {
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'giftReturn');
                    $user->getAssets()->add(AssetKindIds::$BEAN, $rewardCount, $timestamp, $biEvent);
                }

                Log::info(sprintf('GiftReturnService.getReward ok userId=%d beanCount:rate=%d:%s rewardCount=%d',
                    $userId, $giftUser->beanCount, $config['rate'], $rewardCount));
            });
        } catch (Exception $e) {
            Log::info(sprintf('GiftReturnService.getReward error userId=%d ex:%d es:%s',
                $userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function addGift($fromUserId, $sendDetails, $giftIds){
        # 需要添加福袋的人 <userId 福袋礼物id， 福袋数量>
        $addGiftMap = [];
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, $giftIds)) {
                    if (!array_key_exists($giftDetail->giftKind->kindId, $addGiftMap)){
                        $addGiftMap[$giftDetail->giftKind->kindId] = $giftDetail->count;
                    }else{
                        $addGiftMap[$giftDetail->giftKind->kindId] += $giftDetail->count;
                    }
                }
            }
        }

        Log::info(sprintf('addGift fromUserId=%d, addUserMap=%s giftIds=%s',
            $fromUserId, json_encode($addGiftMap), json_encode($giftIds)));

        if (empty($addGiftMap)){
            return;
        }

        # 加福袋
        $timestamp = time();
        // 用户加锁
        $lockKey = "gift_return_".$fromUserId;;
        LockService::getInstance()->lock($lockKey);
        try {
            $giftUser = GiftReturnUserDao::getInstance()->loadUser($fromUserId, $timestamp);
            foreach ($addGiftMap as $giftId => $count){
                if (array_key_exists($giftId, $giftUser->todayGiftMap)) {
                    $giftUser->todayGiftMap[$giftId] += $count;
                }else{
                    $giftUser->todayGiftMap[$giftId] = $count;
                }

                if (array_key_exists($giftId, $giftUser->totalGiftMap)) {
                    $giftUser->totalGiftMap[$giftId] += $count;
                }else{
                    $giftUser->totalGiftMap[$giftId] = $count;
                }
            }

            GiftReturnUserDao::getInstance()->saveUser($giftUser);
        }finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    public function onSendGiftEvent($event){
        if ($this->isExpire()){
            return;
        }

        $config = Config::loadConf();
        try {
            $giftIds = ArrayUtil::safeGet($config, 'giftIds', []);
            $this->addGift($event->fromUserId, $event->sendDetails, $giftIds);
        }catch (Exception $e) {
            Log::error(sprintf('onSendGiftEvent.addGift Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function isExpire(){
        $config = Config::loadConf();
        $timestamp = time();
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

}