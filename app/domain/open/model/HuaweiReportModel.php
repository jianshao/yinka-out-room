<?php

namespace app\domain\open\model;

/**
 * Class HuaweiReportModel
 * TODO https://developer.huawei.com/consumer/cn/doc/distribution/promotion/channel-query-0000001182564011
 * @package app\domain\open\model
 * @example {"callback":"security:4C5CF7732058A7B1539A422A:1426DE533977DCAB8CE9D3CB00BF301AE90120E4CC5B88971DDD7E2A691E1AEB323C129BAE53B105E51D76D8C90357","taskid":"601529627","subTaskId":"6015296270000","RTAID":"","channel":"601529627"}
 */
class HuaweiReportModel
{

    /**
     * @var string
     */
    public $factoryType;

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
     * @var string
     */
    public $strDate;

    /**
     * @var int
     */
    public $createTime;

}

