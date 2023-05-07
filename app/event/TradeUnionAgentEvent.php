<?php

namespace app\event;

//工会代充事件
class TradeUnionAgentEvent extends AppEvent
{
    // 用户id
    public $uid = 0;
    // 送给谁id
    public $toUid = 0;
    // 钻石数量
    public $exchangeDiamond = 0;
    //豆数量
    public $bean = 0;

    public function __construct($uid, $toUid, $exchangeDiamond, $bean, $timestamp)
    {
        parent::__construct($timestamp);
        $this->uid = $uid;
        $this->toUid = $toUid;
        $this->exchangeDiamond = $exchangeDiamond;
        $this->bean = $bean;
    }
}