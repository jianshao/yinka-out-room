<?php


namespace app\domain\activity\zhongqiu;

use app\common\RedisCommon;
use app\domain\dao\DeliveryAddressDao;
use app\domain\exceptions\FQException;
use app\domain\models\DeliveryAddressModel;
use app\service\LockService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class ZhongQiuService
{
    protected static $instance;
    public $giftKindId = "466";
    public $startTime = "2021-09-09 00:00:00";
    public $stopTime = "2021-09-15 23:59:59";
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhongQiuService();
        }
        return self::$instance;
    }

    public function buildKey(){
        return "zhongqiu_user";
    }

    public function postAddress($userId, $name, $mobile, $region, $address, $timestamp){

        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        if ($redis->hGet($key, $userId) <= 0){
            throw new FQException("没有该礼券",500);
        }

        CommonUtil::validateMobile($mobile);

        $lockKey = "zhongqiu_user_".$userId;;
        LockService::getInstance()->lock($lockKey);
        try {
            $count = 1;
            $redis->hIncrBy($key, $userId, -$count);

            $model = new DeliveryAddressModel();
            $model->userId = $userId;
            $model->name = $name;
            $model->reward = $this->giftKindId;
            $model->count = $count;
            $model->createTime = $timestamp;
            $model->activityType = 'zhongqiu';
            $model->region = $region;
            $model->mobile = $mobile;
            $model->address = $address;
            DeliveryAddressDao::getInstance()->addData($model);

            Log::info(sprintf('ZhongQiuService.postAddress ok userId=%d', $userId));
        }finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    public function addGift($fromUserId, $sendDetails){
        # 需要添加福袋的人 <userId 福袋礼物id， 福袋数量>
        $addUserMap = [];
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && $giftDetail->giftKind->kindId == $this->giftKindId) {
                    $addUserMap[$receiveUser->userId] = $giftDetail->count;
                }
            }
        }

        Log::info(sprintf('ZhongQiuService.addGift fromUserId=%d, addUserMap=%s',
            $fromUserId, json_encode($addUserMap)));

        if (empty($addUserMap)){
            return;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        foreach ($addUserMap as $userId => $count){
            $redis->hIncrBy($key, $userId, $count);
        }
    }

    public function onSendGiftEvent($event){
        if ($this->isExpire()){
            return;
        }

        try {
            $this->addGift($event->fromUserId, $event->sendDetails);
        }catch (Exception $e) {
            Log::error(sprintf('ZhongQiuService onSendGiftEvent Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function isExpire(){
        $timestamp = time();
        $startTime = TimeUtil::strToTime($this->startTime);
        $stopTime = TimeUtil::strToTime($this->stopTime);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

}