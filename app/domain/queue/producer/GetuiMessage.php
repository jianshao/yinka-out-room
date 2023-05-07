<?php


namespace app\domain\queue\producer;


use app\domain\queue\model\ListModel;
use app\domain\queue\QueueService;
use app\domain\queue\Worker;
use app\utils\CommonUtil;

class GetuiMessage
{
    protected static $instance;
    protected $topic;
    protected $messageId;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GetuiMessage();
        }
        return self::$instance;
    }

    /**
     * @throws \ReflectionException
     */
    public function __construct()
    {
//        $class = get_class();
//        $this->topic = (new \ReflectionClass($class))->getShortName();
    }

    public function notify($data)
    {
        $consumer = 'app\domain\queue\consumer\GetuiMessage@notify';  //消费者类
        return Worker::getInstance()->push($consumer, $data, 'default');
    }

    public function notifyList($data)
    {
        $consumer = 'app\domain\queue\consumer\GetuiMessage@notifyList';  //消费者类
        return Worker::getInstance()->push($consumer, $data, 'default');
    }
}