<?php

namespace app\common\amqp\core;

use app\common\amqp\model\AmpMessageModel;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\Error;

class AmpService
{
    protected static $instance;

    private $maxRunType = 3;

    private $cachePrefix = "AmpService";

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AmpService();
        }
        return self::$instance;
    }


    public function loadMsgId()
    {
        return generateToken("AmpService");
    }

    public function loadUnixTime()
    {
        return getUnixTimeStamp();
    }

    /**
     * @param $queue
     * @param null $exchange
     * @return int
     * @throws FQException
     */
    public function loadPartition($queue = null, $exchange = null, $unixTime = 0)
    {
        $queue = is_null($queue) ? "" : $queue;
        $exchange = is_null($exchange) ? "ex_message_bus" : $exchange;
        $unixTime = $unixTime === 0 ? time() : $unixTime;
        $hashKey = sprintf("%s%s%d", $exchange, $queue, $unixTime);
        return crc32($hashKey);
    }


    /**
     * @param $topic
     * @param $tag
     * @return string
     */
    private function getBusiness($topic, $tag)
    {
        return md5(sprintf("tc:%s_t:%s", $topic, $tag));
    }

    /**
     * @param $hashKey
     * @param $modelBusiness
     * @throws FQException
     */
    public function verifyIdempotent($hashKey, $modelBusiness)
    {
        if (config("config.appDev") === "dev") {
            return;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $IdempotentKey = sprintf("%s:h:%s_b:%s", $this->cachePrefix, $hashKey, $modelBusiness);
        if ($redis->incr($IdempotentKey) >= 2) {
            throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_AUTH_IDEMPOTENT), Error::ERROR_SEND_PHONE_CODE_FAIL);
        }
    }


    /**
     * @param AmpMessageModel $model
     * @param string $topic
     * @param string $tag
     * @throws FQException
     */
    public function verifyMessage(AmpMessageModel $model, $topic = "", $tag = "")
    {
        $modelBusiness = $this->getBusiness($model->topic, $model->tag);
        $queueBusiness = $this->getBusiness($topic, $tag);
//        过滤是否为当前业务
        if (empty($topic) || empty($tag) || $modelBusiness !== $queueBusiness) {
            throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_AUTH_TOPIC_TAG), Error::ERROR_AUTH_TOPIC_TAG);
        }
//        过滤数据是否超过重试次数
        if ($model->runType >= $this->maxRunType) {
            throw new FQException("maxRunType count error", 500);
        }
//        去重
        $hashKey = $model->getHashkey();
        $this->verifyIdempotent($hashKey, $modelBusiness);
//        监测消息是否为空
        if (empty($model->body)) {
            throw new FQException("model body is empty error", 409);
        }
        return true;
    }


}