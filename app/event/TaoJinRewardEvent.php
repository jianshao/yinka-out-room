<?php

namespace app\event;


//购买商品
class TaoJinRewardEvent extends AppEvent
{
    public $userId = 0;
    public $gameId = 0;
    //奖励
    public $rewards = null;

    public function __construct($userId, $gameId, $rewards, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->gameId = $gameId;
        $this->rewards = $rewards;
    }
}