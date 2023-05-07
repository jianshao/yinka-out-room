<?php


namespace app\domain\bi;


use app\utils\ArrayUtil;

class BIReport
{
    // 单例
    protected static $instance;
    protected $modelDao = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BIReport();
            self::$instance->modelDao = BIUserAssetModelDao::getInstance();
        }
        return self::$instance;
    }

    protected function makeModel($userId, $delta, $after, $timestamp, $biEvent){
        $model = new BIUserAssetModel();
        $model->uid = $userId;
        $model->change = $delta;
        $model->changeAfter = $after;
        $model->changeBefore = $after - $delta;
        $model->createTime = time();
        $model->updateTime = $timestamp;
        $model->eventId = $biEvent['eventId'];

        if(ArrayUtil::safeGet($biEvent, 'toUid')){
            $model->toUid = $biEvent['toUid'];
        }

        if(ArrayUtil::safeGet($biEvent, 'roomId')){
            $model->roomId = $biEvent['roomId'];
        }

        if(ArrayUtil::safeGet($biEvent, 'ext1')){
            $model->ext1 = (string)$biEvent['ext1'];
        }

        if(ArrayUtil::safeGet($biEvent, 'ext2')){
            $model->ext2 = (string)$biEvent['ext2'];
        }

        if(ArrayUtil::safeGet($biEvent, 'ext3')){
            $model->ext3 = (string)$biEvent['ext3'];
        }

        if(ArrayUtil::safeGet($biEvent, 'ext4')){
            $model->ext4 = (string)$biEvent['ext4'];
        }

        if(ArrayUtil::safeGet($biEvent, 'ext5')){
            $model->ext5 = (string)$biEvent['ext5'];
        }

        return $model;
    }

    public function reportBank($userId, $accountId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$BANK_TYPE;
        $model->assetId = $accountId;
        $this->modelDao->addData($model);
    }

    public function reportEnergy($userId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$ENERGY_TYPE;
        $model->assetId = 'energy';
        $this->modelDao->addData($model);
    }

    public function reportBean($userId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$BEAN_TYPE;
        $model->assetId = 'bean';
        $this->modelDao->addData($model);
    }

    public function reportDiamond($userId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$DIAMOND_TYPE;
        $model->assetId = 'diamond';
        $this->modelDao->addData($model);
    }

    public function reportCoin($userId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$COIN_TYPE;
        $model->assetId = 'coin';
        $this->modelDao->addData($model);
    }

    public function reportProp($userId, $kindId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$PROP_TYPE;
        $model->assetId = (string)$kindId;
        $this->modelDao->addData($model);
    }

    public function reportGift($userId, $kindId, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$GIFT_TYPE;
        $model->assetId = (string)$kindId;
        $this->modelDao->addData($model);
    }

    public function reportOre($userId, $oreType, $delta, $after, $timestamp, $biEvent) {
        $model = $this->makeModel($userId, $delta, $after, $timestamp, $biEvent);
        $model->type = BIConfig::$ORE_TYPE;
        $model->assetId = (string)$oreType;
        $this->modelDao->addData($model);
    }

    public function makeSendGiftBIEvent($roomId, $toUserId, $kindId, $deliveryKindId, $count, $fromBag, $price) {
        return [
            'eventId' => BIConfig::$SEND_GIFT_EVENTID,
            'toUid' => $toUserId,
            'roomId' => $roomId,
            'ext1' => $kindId,
            'ext2' => $deliveryKindId,
            'ext3' => $count,
            'ext4' => $price,
            'ext5' => $fromBag ? 1 : 0,
        ];
    }

    public function makeSystemSendGiftBIEvent($roomId, $fromUserId, $kindId, $deliveryKindId, $count, $fromBag, $price) {
        return [
            'eventId' => BIConfig::$SYSTEM_SEND_GIFT_EVENTID,
            'toUid' => $fromUserId,
            'roomId' => $roomId,
            'ext1' => $kindId,
            'ext2' => $deliveryKindId,
            'ext3' => $count,
            'ext4' => $price,
            'ext5' => $fromBag ? 1 : 0,
        ];
    }

    public function makeOpenGiftBIEvent($roomId, $kindId, $count) {
        return [
            'eventId' => BIConfig::$OPEN_GIFT,
            'roomId' => $roomId,
            'ext1' => $kindId,
            'ext2' => $count,
        ];
    }

    public function makeReceiveGiftBIEvent($roomId, $fromUserId, $kindId, $deliveryKindId, $count, $fromBag, $price) {
        return [
            'eventId' => BIConfig::$RECEIVE_GIFT_EVENTID,
            'toUid' => $fromUserId,
            'roomId' => $roomId,
            'ext1' => $kindId,
            'ext2' => $deliveryKindId,
            'ext3' => $count,
            'ext4' => $price,
            'ext5' => $fromBag ? 1 : 0,
        ];
    }

    public function makeDiamondExchangeBeanBIEvent($beanCount, $coinCount) {
        return [
            'eventId' => BIConfig::$DIAMOND_EXCHANGE_EVENTID,
            'ext1' => $beanCount,
            'ext2' => $coinCount,
        ];
    }

    public function makeBeanExchangeCoinBIEvent($diamondCount, $beanCount) {
        return [
            'eventId' => BIConfig::$COIN_EXCHANGE_EVENTID,
            'ext1' => $diamondCount,
            'ext2' => $beanCount,
        ];
    }

    public function makeGMAdjustBIEvent($operatorId, $reason) {
        return [
            'eventId' => BIConfig::$GM_ADJUST,
            'ext1' => $operatorId,
            'ext5' => $reason
        ];
    }

    public function makeActivityBIEvent($roomId, $activityType, $ext2='', $ext3='', $ext4='', $ext5='') {
        return [
            'roomId' => $roomId,
            'eventId' => BIConfig::$ACTIVITY_EVENTID,
            'ext1' => $activityType, //活动类型
            'ext2' => $ext2, //活动子类型
            'ext3' => $ext3,        //次数
            'ext4' => $ext4,
            'ext5' => $ext5,
        ];
    }

    public function makeWithdrawPretakeoffBIEvent($orderId, $amount, $ext2 = "", $ext4 = "", $ext5 = "")
    {
        return [
            'eventId' => BIConfig::$WITHDRAW_PRETAKEOFF_EVENTID,
            'ext1' => $orderId,
            'ext3' => $amount,
            'ext2' => $ext2,
            'ext4' => $ext4,
            'ext5' => $ext5,
        ];
    }

    public function makeWithdrawRefuseBIEvent($orderId, $amount, $ext2 = "", $ext4 = "", $ext5 = "")
    {
        return [
            'eventId' => BIConfig::$WITHDRAW_REFUSE_EVENTID,
            'ext1' => $orderId,
            'ext3' => $amount,
            'ext2' => $ext2,
            'ext4' => $ext4,
            'ext5' => $ext5,
        ];
    }

    public function makeActivityExpiredBIEvent($roomId, $activityType, $ext2='', $ext3='', $ext4='', $ext5='') {
        return [
            'roomId' => $roomId,
            'eventId' => BIConfig::$ACTIVITY_EXPIRED_EVENTID,
            'ext1' => $activityType, //活动类型
            'ext2' => $ext2, //活动子类型
            'ext3' => $ext3,        //次数
            'ext4' => $ext4,
            'ext5' => $ext5,
        ];
    }

    public function makeBuyGoodsBIEvent($mallId, $goodsId, $count, $from) {
        return [
            'eventId' => BIConfig::$BUY_EVENTID,
            'ext1' => $mallId,
            'ext2' => $goodsId,
            'ext3' => $count,
            'ext4' => $from
        ];
    }

    public function makeSendGoodsBIEvent($mallId, $goodsId, $count, $userId, $from) {
        return [
            'eventId' => BIConfig::$MALL_SEND_EVENTID,
            'toUid' => $userId,
            'ext1' => $mallId,
            'ext2' => $goodsId,
            'ext3' => $count,
            'ext4' => $from
        ];
    }

    public function makeReceiveGoodsBIEvent($mallId, $goodsId, $count, $userId, $from) {
        return [
            'eventId' => BIConfig::$MALL_RECEIVE_EVENTID,
            'toUid' => $userId,
            'ext1' => $mallId,
            'ext2' => $goodsId,
            'ext3' => $count,
            'ext4' => $from
        ];
    }

    public function makeTaskBIEvent($taskType, $taskId) {
        return [
            'eventId' => BIConfig::$TASK_EVENTID,
            'ext1' => $taskType,
            'ext2' => $taskId
        ];
    }

    public function makeDukeBIEvent($nowLevel, $newLevel) {
        return [
            'eventId' => BIConfig::$DUKE_EVENTID,
            'ext1' => $nowLevel,
            'ext2' => $newLevel
        ];
    }

    public function makePrivilegeLevelBIEvent($nowLevel, $newLevel) {
        return [
            'eventId' => BIConfig::$PRIVILEGE_REWARD_EVENTID,
            'ext1' => $nowLevel,
            'ext2' => $newLevel
        ];
    }

    public function makeVipBIEvent($nowLevel, $newLevel) {
        return [
            'eventId' => BIConfig::$VIP_EVENTID,
            'ext1' => $nowLevel,
            'ext2' => $newLevel
        ];
    }

    public function makeChargeDeliveryBIEvent($orderId, $productId, $channel) {
        return [
            'eventId' => BIConfig::$CHARGE_EVENTID,
            'ext1' => $orderId,
            'ext2' => $productId,
            'ext3' => $channel
        ];
    }

    public function makeSendRedPacketsBIEvent($fromUserId, $roomId, $type, $redPacketId) {
        return [
            'eventId' => BIConfig::$REDPACKETS_EVENTID,
            'toUid' => $fromUserId,
            'roomId' => $roomId,
            'ext1' => $redPacketId,
            'ext2' => $type
        ];
    }

    public function makeGrabRedPacketsBIEvent($userId, $roomId, $type, $redPacketId) {
        return [
            'eventId' => BIConfig::$REDPACKETS_GRAB_EVENTID,
            'toUid' => $userId,
            'roomId' => $roomId,
            'ext1' => $redPacketId,
            'ext2' => $type
        ];
    }

    public function makeReturnRedPacketsBIEvent($fromUserId, $roomId, $type, $redPacketId) {
        return [
            'eventId' => BIConfig::$REDPACKETS_RETURN_EVENTID,
            'toUid' => $fromUserId,
            'roomId' => $roomId,
            'ext1' => $redPacketId,
            'ext2' => $type
        ];
    }

    public function makeTradeUnionAgentBIEvent($toUserId, $exchangeDiamond, $orderId) {
        return [
            'eventId' => BIConfig::$REPLACE_CHARGE_EVENTID,
            'toUid' => $toUserId,
            'change_amount' => $exchangeDiamond,
            'ext1' => $orderId,
        ];
    }

    public function makePropActionBIEvent($kindId, $count, $actionName) {
        return [
            'eventId' => BIConfig::$PROP_ACTION_EVENTID,
            'ext1' => $kindId,
            'ext2' => $count,
            'ext3' => $actionName,
        ];
    }

    public function makeGiftActionBIEvent($kindId, $count, $actionName) {
        return [
            'eventId' => BIConfig::$GIFT_ACTION_EVENTID,
            'ext1' => $kindId,
            'ext2' => $count,
            'ext3' => $actionName,
        ];
    }

    public function makeGiftSuperRewardBIEvent($roomId, $giftKindId, $count, $receiversCount) {
        return [
            'eventId' => BIConfig::$SUPER_REWARD_EVENTID,
            'roomId' => $roomId,
            'ext1' => $giftKindId,
            'ext2' => $count,
            'ext3' => $receiversCount,
        ];
    }

    public function makeFirstChargeRewardBIEvent($orderId, $productId, $channel, $vipNormalTime = false) {
        return [
            'eventId' => BIConfig::$CHARGE_EVENTID,
            'ext1' => $orderId,
            'ext2' => $productId,
            'ext3' => $channel,
            'ext5' => $vipNormalTime // vip、svip 是否按正常添加时间， 非自然日
        ];
    }
}