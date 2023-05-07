<?php


namespace app\domain\activity\gameVote;


use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class GameVoteUserRewardData
{
//    领取的礼物数据list [投票的gameid=>是否投票过] （1可投票，2不能投票）
    public $data = [];

    public function __construct()
    {
        $config = Config::loadConf();
        $checkins = $config['list'];
        foreach ($checkins as $item) {
            $this->data[$item['id']] = 1;
        }
    }


    public function fromJson($jsonObj)
    {
        $this->data = json_decode($jsonObj, true);
        return $this;
    }

    public function toJson()
    {
        return json_encode($this->data);
    }

    public function getLevel($level)
    {
        return ArrayUtil::safeGet($this->data, $level, 0);
    }

    public function setLevel($level, $value)
    {
        if (array_key_exists($level, $this->data)) {
            $this->data[$level] = $value;
            return true;
        }
        return false;
    }

    /**
     * @param $level
     * @param $value
     */
    public function updateForLevel($level, $value)
    {
        if (!isset($this->data[$level])) {
            throw new FQException("level not find", 500);
        }
        $this->data[$level] = $value;
    }

}