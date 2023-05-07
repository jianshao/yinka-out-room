<?php


namespace app\domain\queue\producer;


use app\domain\queue\Worker;

class KuaishouMessage
{
    protected static $instance;
    protected $topic;
    protected $messageId;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KuaishouMessage();
        }
        return self::$instance;
    }

    /**
     * @throws \ReflectionException
     */
    public function __construct()
    {
    }


    public function notify($data): string
    {
        $consumer = 'app\domain\queue\consumer\KuaishouMessage@notify';  //消费者类
        return Worker::getInstance()->push($consumer, $data, 'default');
    }
}