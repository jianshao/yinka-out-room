<?php

namespace app\event;

class OreExchangeEvent extends AppEvent
{
    public $userId = 0;
    public $gameId = 0;
    // 兑换的礼物id
    public $assetId = null;

    public function __construct($userId, $gameId, $assetId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->gameId = $gameId;
        $this->assetId = $assetId;
    }
}