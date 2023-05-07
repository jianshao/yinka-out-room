<?php
/**
 */

namespace app\common\amqp;


use app\common\amqp\conf\Config;
use app\common\amqp\model\UserRegisterRefereeMessageModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;

/**
 * 消息队列服务
 * Class AmpService
 * @package app\common\amqp
 */
class AmpService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $messageBody
     * @return bool
     * @throws \Exception
     */
    public function publisherUserRegisterReferee(UserRegisterRefereeMessageModel $model)
    {
        $messageBody=json_encode($model->toJson());
        $config = Config::getInstance()->getUserRegisterRefereeConf();
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
            Log::info(sprintf("app\common\amqp\AmpService publisherUserRegisterReferee success message:%s", $messageBody));
            return true;
        } catch (\Exception $e) {
            Log::info(sprintf("app\common\amqp\AmpService publisherUserRegisterReferee error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

}