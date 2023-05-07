<?php

namespace app\common\server;

use app\common\QueueRedis;

/**
 * redis 队列入队
 * Class QueuePop
 * @package app\common\server
 */
class QueuePop
{
    protected $key = null;
    protected $redis_connect;

    /**
     * @param $key string
     */
    public function __construct()
    {
        $this->redis_connect = QueueRedis::getInstance()->getRedis();
        $this->redis_connect->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $this->key = config('cache.queuekey') ? config('cache.queuekey') : 'queuetasklist_master';
    }

    /**
     * @info 序列化数据
     * @param $message array 请求的数据
     * @return false|string
     */
    private function decodemessage($message)
    {
        if (empty($message)) {
            return '';
        }
        return json_decode($message, true);
    }

    /**
     * @info cove to message data
     * @return false|string
     */
    public function rPop()
    {
        $data = $this->redis_connect->rPop($this->key);
        if (empty($data)) {
            return '';
        }
        return $this->decodemessage($data);
    }

    /**
     * @info cove to message data
     * @return false|string
     */
    public function brPop()
    {
        $data = $this->redis_connect->brPop($this->key, 3600);
        if (empty($data)) {
            return '';
        }
        return $this->decodemessage($data[1]);
    }


    private function getUnixTime()
    {
        return time();
    }

}