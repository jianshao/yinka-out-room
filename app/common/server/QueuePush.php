<?php

namespace app\common\server;

use app\common\QueueRedis;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\CommonUtil;

/**
 * redis 队列入队
 * Class QueuePush
 * @package app\common\server
 */
class QueuePush
{
    protected $key = null;
    protected $redis_connect;

    /**
     * @param $key string
     */
    public function __construct()
    {
        $this->redis_connect = QueueRedis::getInstance()->getRedis();
        $this->key = config('cache.queuekey') ? config('cache.queuekey') : 'queuetasklist_master';
    }

    /**
     * @info 序列化数据
     * @param $message array 请求的数据
     * @return false|string
     */
    private function encodeMessage($message)
    {
        return json_encode($message);
    }


    /**
     * @info cove to message data
     * @param string $messageType
     * @param mixed $messageData
     * @return string
     * @throws FQException
     */
    public function SetMessageData(string $handle, string $method, $messageData)
    {
        if (empty($handle) || empty($method) || empty($messageData)) {
            throw new FQException("SetMessageData error");
        }
        $msgId = CommonUtil::createUuID();
        $data = [
            'msgId' => $msgId,
            'handle' => $handle,
            'method' => $method,
            'data' => $messageData,
            'unixTime' => $this->getUnixTime(),
        ];
        return $this->encodeMessage($data);
    }

    public function lPush($message)
    {
        if (empty($message)) {
            throw new FQException("Publish message");
        }
        return $this->redis_connect->lPush($this->key, $message);
    }


    private function getUnixTime()
    {
        return time();
    }

}