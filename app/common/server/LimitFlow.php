<?php

namespace app\common\server;

use app\common\RedisCommon;

/**
 * redis 滑动窗口限流
 * Class LimitFlow
 * @package app\common\server
 */
class LimitFlow
{
    protected $key = null;
    protected $redis_connect;

//    1分钟限制5
//    10分钟限制50
//    1小时限制3000
    private $rules = [
        60 => 5,
        600 => 50,
        3600 => 3000,
    ];

    /**
     * LimitFlow constructor.
     * @param $key string
     * @param $rules array 规则 key=>时间点（s） values 频次
     */
    public function __construct($key, $rules = null)
    {
        $this->redis_connect = RedisCommon::getInstance()->getRedis();
        $this->key = $key;
        $this->rules = is_null($rules) ? $this->rules : $rules;
    }

    /**
     * 是否放行
     * @return int 0 or 1 0：放行  1:拒绝
     */
    public function isPass()
    {
        return $this->checkLimits($this->rules, $this->key, 'default');
    }


    function checkLimits($rules, $key, $tel)
    {
        $redis = $this->redis_connect;
        foreach ($rules as $ruleTime => $rule) {
            $redisKey = $key . "_" . $ruleTime;
            $score = time();
            $member = $tel . '_' . $score;
            $redis->multi();
            $redis->zRemRangeByScore($redisKey, 0, $score - $ruleTime);//移除窗口以外的数据
            $redis->zAdd($redisKey, $score, $member);
            $redis->expire($redisKey, $ruleTime);
            $redis->zRange($redisKey, 0, -1, true);
            $members = $redis->exec();
            if (empty($members[3])) {
                break;
            }
            $nums = count($members[3]);
            if ($nums > $rule) {
                return 1;
            }
        }
        return 0;
    }


    private function getZscanData($redisKey)
    {
        $iterator = null;
        $count = 100;
        return $this->redis_connect->zscan($redisKey, $iterator, null, $count);
    }

}