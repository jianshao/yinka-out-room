<?php

namespace app\event;

class SendGiftEvent extends AppEvent
{
    // 哪个房间
    public $roomId = 0;
    // 谁发送的
    public $fromUserId = null;
    // 都发给谁
    public $receiveUsers = null;
    // 发哪个礼物
    public $giftKind = null;
    // 发了多少个
    public $count = 0;
    // 是否背包触发
    public $fromBag = false;
    // 发出去的 list<[receiveUser, list<GiftDetail>]>
    public $sendDetails = null;
    // 实际接收到的 list<[receiveUser, list<GiftDetail>]>
    public $receiveDetails = null;

    public $superRewardLists = null;
    // 服务端是否发送礼物通知  //v1是客户端自己发云信推送， v2是服务器发云信推送，v2需要初始化该参数
    public $sendNotice = false;

    public function __construct($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $fromBag, $sendDetails, $receiveDetails, $timestamp, $superRewardLists, $sendNotice = false,$fromUserBeanBalance=0 ,$receiverUserDiamondBalance=[])
    {
        parent::__construct($timestamp);
        $this->roomId = $roomId;
        $this->fromUserId = $fromUserId;
        $this->receiveUsers = $receiveUsers;
        $this->giftKind = $giftKind;
        $this->count = $count;
        $this->fromBag = $fromBag;
        $this->sendDetails = $sendDetails;
        $this->receiveDetails = $receiveDetails;
        $this->superRewardLists = $superRewardLists;
        $this->sendNotice = $sendNotice;
        $this->fromUserBeanBalance = $fromUserBeanBalance;
        $this->receiverUserDiamondBalance = $receiverUserDiamondBalance;
    }

    public function calcSenderConsumeCount($assetId)
    {
        $count = 0;
        foreach ($this->sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->consumeAsset && $giftDetail->consumeAsset->assetId == $assetId) {
                    $count += $giftDetail->consumeAsset->count;
                }
            }
        }
        return $count;
    }


    public function calcSenderConsumeCountSpec($assetId)
    {
        $count = 0;
        foreach ($this->sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->consumeAsset && $giftDetail->consumeAsset->assetId == $assetId) {
                    if ($giftDetail->giftKind->kindId == 395) {
                        $addCount = intval($giftDetail->consumeAsset->count * 0.5);
                    } else {
                        $addCount = $giftDetail->consumeAsset->count;
                    }
                    $count += $addCount;
                }
            }
        }
        return $count;
    }

    public function calcSenderPriceSpec($assetId)
    {
        $count = 0;
        foreach ($this->sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind->price && $giftDetail->giftKind->price->assetId == $assetId) {
                    $tmp = $giftDetail->giftKind->price->count * $giftDetail->count;
                    if ($giftDetail->giftKind->kindId == 395) {
                        $tmp = $tmp * 0.5;
                    }
                    $count += (int)$tmp;
                }
            }
        }
        return $count;
    }
}