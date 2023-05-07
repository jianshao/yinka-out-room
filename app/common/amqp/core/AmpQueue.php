<?php

namespace app\common\amqp\core;

use app\common\amqp\conf\Config;
use app\common\amqp\model\AmpMessageModel;
use app\common\amqp\model\AmpTopic;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmpQueue
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AmpQueue();
        }
        return self::$instance;
    }

    /**
     * @info 推送总线消息
     * @param AmpMessageModel $model
     * @return bool
     * @throws \Exception
     */
    public function publisherMessageBusModel(AmpMessageModel $model)
    {
        $messageBody = json_encode($model->toJson());
        $config = Config::getInstance()->getMessageBusConf();
        $exchange = $config['exchange'] ?? "";
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass'], $config['vhost']);
        $channel = $connection->channel();
        $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($message, $exchange);
        $channel->close();
        $connection->close();
        return true;
    }

    /**
     * @param $tag
     * @param $event
     * @param $eventModel
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    public function storePublishTagEvent($tag, $eventModel)
    {
        $strData = $eventModel->modelToJson();
        $unixTime = AmpService::getInstance()->loadUnixTime();
        $message = new AmpMessageModel();
        $message->runType = 0;
        $message->topic = AmpTopic::$GENERAL;
        $message->tag = $tag;
        $message->event = $this->getClassName($eventModel);
        $message->msgId = AmpService::getInstance()->loadMsgId();
        $message->partition = AmpService::getInstance()->loadPartition(null, null, $unixTime);
        $message->timestamp = $unixTime;
        $message->body = $strData;
        return $this->publisherMessageBusModel($message);
    }


    /**
     * @param $event
     * @return string
     */
    private function getClassName($event)
    {
        if (empty($event)) {
            return "";
        }
        $namespaceClass = get_class($event);
        $temp = strrchr($namespaceClass, "\\");
        return ltrim($temp, "\\");
    }

    /**
     * @info elastic 消费者-es-房间
     * @param \Closure $callback
     * @return bool
     */
    public function consumerElasticQueueUser(\Closure $callback)
    {
        $config = Config::getInstance()->getElasticQueueUserConf();
        $model=new AmpConnet($config);
        $model->handler($callback);
        return true;
    }

    /**
     * @info elastic 消费者-es-房间
     * @param \Closure $callback
     * @return bool
     */
    public function consumerElasticQueueRoom(\Closure $callback)
    {
        $config = Config::getInstance()->getElasticQueueRoomConf();
        $model=new AmpConnet($config);
        $model->handler($callback);
        return true;
    }

    /**
     * @info elastic 消费者-es-房间
     * @param \Closure $callback
     * @throws \ErrorException
     */
    public function consumerTest(\Closure $callback)
    {
        $config = Config::getInstance()->getElasticQueueRoomConf();
        $queueName = $config['queue_name'] ?? "";
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass']);
        $channel = $connection->channel();
        //声明队列
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        $channel->basic_consume($queueName, '', false, $no_ack = false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }
}