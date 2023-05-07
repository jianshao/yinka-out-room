<?php
/**
 */

namespace app\common\server;

use app\common\RedisCommon;

/**
 * 基于redis-cell实现的一个漏桶操作类
 * Class LeakyBucket
 * @package app\common\server
 */
class LeakyBucket
{
    protected $key = null;

    protected $max_burst = null;

    protected $tokens = null;

    protected $seconds = null;

    protected $apply = 1;

    protected $redis_connect;

    /**
     * LeakyBucket constructor.
     * @param $key string
     * @param $max_burst int 初始桶数量
     * @param $tokens int 速率
     * @param $seconds int 时间
     * @param int $apply 每次漏水数量
     */
    public function __construct($key,$max_burst,$tokens,$seconds,$apply=1)
    {
        $this->redis_connect=RedisCommon::getInstance()->getRedis();

        $this->key = $key;

        $this->max_burst = $max_burst;

        $this->tokens = $tokens;

        $this->seconds = $seconds;

        $this->apply = $apply;
    }

    /**
     * 是否放行
     * @return int 0 or 1 0：放行  1:拒绝
     */
    public function isPass()
    {
        $rs = $this->redis_connect->rawCommand('CL.THROTTLE',$this->key,$this->max_burst,$this->tokens,$this->seconds,$this->apply);
        return $rs[0];
    }
}