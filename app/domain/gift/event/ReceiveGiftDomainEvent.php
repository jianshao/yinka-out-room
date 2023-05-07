<?php

namespace app\domain\gift\event;
use app\domain\events\DomainUserEvent;

class ReceiveGiftDomainEvent extends DomainUserEvent
{
    public $roomId = 0;
    public $fromUserId = null;
    public $receiveUser = null;
    public $giftDetailsList = null;
    public $fromBag = false;

    public function __construct($roomId, $user, $fromUserId, $receiveUser, $giftDetailsList, $fromBag, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->roomId = $roomId;
        $this->fromUserId = $fromUserId;
        $this->receiveUser = $receiveUser;
        $this->giftDetailsList = $giftDetailsList;
        $this->fromBag = $fromBag;
    }

    public function calcReceiverAssetCount($assetId) {
        $count = 0;
        foreach ($this->giftDetailsList as $details) {
            if ($details->receiverAssets) {
                foreach ($details->receiverAssets as $receiverAsset) {
                    if ($receiverAsset->assetId == $assetId) {
                        $count += $receiverAsset->count;
                    }
                }
            }
        }
        return $count;
    }
}


