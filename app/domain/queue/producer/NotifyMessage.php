<?php


namespace app\domain\queue\producer;


use app\domain\queue\Worker;

class NotifyMessage
{
    protected static $instance;
    protected $topic;
    protected $messageId;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NotifyMessage();
        }
        return self::$instance;
    }

    public function notify($data): string
    {
        $consumer = 'app\domain\queue\consumer\NotifyMessage@notify';  //消费者类
        return Worker::getInstance()->push($consumer, $data, 'default');
    }
}