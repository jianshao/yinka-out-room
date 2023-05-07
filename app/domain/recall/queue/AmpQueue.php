<?php

namespace app\domain\recall\queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;

class AmpQueue
{
    protected static $instance;
    const COMMAND_NAME = "RecallAmpQueue";

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AmpQueue();
        }
        return self::$instance;
    }


    private function getAmpqConf()
    {
        return config("config.ampq_recall");
    }

    private function getAmpqUserPushConf()
    {
        return config("config.ampq_recall_user_push");
    }

    /**
     * @param $messageBody
     * @return bool
     * @throws \Exception
     * @example {"id":4,"push_when":{"charge_max":500,"charge_min":100,"time":3600},"push_type":"getuipush","template_ids":"[102,109]"}
     */
    public function publisher(string $messageBody)
    {
        $config = $this->getAmpqConf();
        $exchange = $config['exchange'];
        $queue = $config['queue_name'];
        try {
            $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass'], $config['vhost']);
            $channel = $connection->channel();
            $channel->queue_declare($queue, false, true, false, false);
            $channel->queue_bind($queue, $exchange);
//            $messageBody = "messgeBody message data retry 22";
            $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            $channel->basic_publish($message, $exchange);
            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            Log::info(sprintf("app\command\RecallQueueCommand testPublisher error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return true;
    }

    /**
     * @info 用户召回消费者
     * @param $callback
     * @throws \ErrorException
     */
    public function consumer(\Closure $callback)
    {
        $config = $this->getAmpqConf();
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass']);
        $channel = $connection->channel();
        //声明队列
//        $channel->queue_declare($config['queue_name'], false, true, false, false);
        $channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }


    /**
     * {"push_recall_conf":"{\"id\":4,\"push_when\":{\"charge_max\":500,\"charge_min\":100,\"time\":3600},\"push_type\":\"getuipush\",\"template_ids\":\"[102,109]\"}","user_ids":"[1456410,1456408,1456402]"}
     * @param $messageBody
     * @return bool
     * @throws \Exception
     */
    public function publisherUserPush(string $messageBody)
    {
        $config = $this->getAmpqUserPushConf();
        $exchange = $config['exchange'];
        $queue = $config['queue_name'];
        try {
            $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass'], $config['vhost']);
            $channel = $connection->channel();
            $channel->queue_declare($queue, false, true, false, false);
            $channel->queue_bind($queue, $exchange);
            $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            $channel->basic_publish($message, $exchange);
            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            Log::info(sprintf("app\command\RecallQueueCommand testPublisher error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return true;
    }


    /**
     * @info 用户召回用户消息推送消费者
     * @param \Closure $callback
     * @throws \ErrorException
     */
    public function consumerUserPush(\Closure $callback)
    {
        $config = $this->getAmpqUserPushConf();
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass']);
        $channel = $connection->channel();
        //声明队列
//        $channel->queue_declare($config['queue_name'], false, true, false, false);
        $channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }


}