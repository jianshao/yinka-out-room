<?php


namespace app\domain\activity\gameVote;


use app\utils\ArrayUtil;

class GameVoteUser
{
//    用户ID
    public $userId = 0;
//    领奖次数
    public $voteNumber = 0;
//    今日领取的礼物数据list [投票的gameid=>是否投票过] （1可投票，2不能投票）
    public $rewardData = null;

    public $rewardStatus = 0;  //0 没有领取过 1已经领取过了

//    更新时间
    public $updateTime = 0;

    public function __construct($userId, $timestamp = 0)
    {
        $this->userId = $userId;
        $this->updateTime = $timestamp;
        $this->rewardStatus = 0;
        $this->rewardData = new GameVoteUserRewardData();
    }


    public function fromJson($jsonObj)
    {
        $this->updateTime = $jsonObj['updateTime'];
        $this->voteNumber = (int)ArrayUtil::safeGet($jsonObj, 'voteNumber', 0);
        $this->rewardStatus = (int)ArrayUtil::safeGet($jsonObj, 'rewardStatus', 0);
        $rewardData = ArrayUtil::safeGet($jsonObj, 'rewardData', '');
        $reward = new GameVoteUserRewardData;
        $this->rewardData = $reward->fromJson($rewardData);
        return $this;
    }

    public function toJson()
    {
        return [
            'userId' => $this->userId,
            'updateTime' => $this->updateTime,
            'voteNumber' => $this->voteNumber,
            'rewardStatus' => $this->rewardStatus,
            'rewardData' => $this->rewardData->toJson(),
        ];
    }


    /**
     * @info 剩余的领奖次数
     */
    public function getVoteNumber()
    {
        return 3 - $this->voteNumber;
    }
}