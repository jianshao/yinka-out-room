<?php

namespace app\domain\open\model;

/**
 * Class HuaweiChannelModel
 * TODO https://developer.huawei.com/consumer/cn/doc/distribution/promotion/channel-query-0000001182564011
 * @package app\domain\open\model
 */
class HuaweiChannelModel
{
    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $deviceId;

    /**
     * @var string
     */
    public $oaid;

    /**
     * @var string
     */
    public $channel;

    /**
     * @var string
     */
    public $callback;

    /**
     * @var string
     */
    public $taskid;

    /**
     * @var string
     */
    public $subTaskId;

    /**
     * @var string
     */
    public $rtaid;

    /**
     * @var int
     */
    public $ctime;


}

