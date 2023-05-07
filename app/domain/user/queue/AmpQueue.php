<?php

namespace app\domain\user\queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;

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


    private function getAmpqConf()
    {
        return config("config.ampq_login_detail_message");
    }

    /**
     * @param $messageBody
     * @return bool
     * @throws \Exception
     * @example
     */
    public function publisher(string $messageBody)
    {
        $config = $this->getAmpqConf();
        if (empty($config)){
            Log::error(sprintf("AmpQueue publisher not ampq_login_detail_message config"));
            return false;
        }
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
            Log::info(sprintf("AmpQueue publisher error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return true;
    }
}