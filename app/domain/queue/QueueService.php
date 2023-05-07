<?php


namespace app\domain\queue;


use app\common\RedisCommon;
use app\domain\queue\consumer\GetuiMessage;
use app\domain\queue\consumer\NotifyMessage;
use app\domain\queue\consumer\YunXinMsg;
use think\facade\Log;

class QueueService
{
    protected static $instance;
    protected $redis = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new QueueService();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    /**
     * 发布消息
     * @param $list
     */
    public function pushList($list) {
        $this->redis->lPush(config('config.queueKey'), json_encode($list));
        Log::info(sprintf('QueueService::pushList list=%s', json_encode($list)));
    }

    /**
     * 获取消息
     */
    public function popList() {
        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $message = $this->redis->brPop([config('config.queueKey')], 0);
        if (empty($message)) {
            Log::warning(sprintf('QueueService::popList warning=%s', 'empty message'));
        } else {
            $messageData = json_decode($message[1], true);
            Log::info(sprintf('QueueService::popList info=%s', $message[1]));
            $this->ConsumerMessage($messageData);
        }
    }

    /**
     * 获取消息
     */
    public function popListShort() {
        $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $message = $this->redis->brPop([config('config.queueKey')], 5);
        if (empty($message)) {
            Log::warning(sprintf('QueueService::popList warning=%s', 'empty message'));
        } else {
            $messageData = json_decode($message[1], true);
            Log::info(sprintf('QueueService::popList info=%s', $message[1]));
            $this->ConsumerMessage($messageData);
            $this->popListShort();
        }
    }

    /**
     * 消费消息
     * @param $message
     */
    private function ConsumerMessage($message) {
        try {
            switch ($message['topic']) {
                case 'YunXinMsg':
                    YunXinMsg::getInstance()->sendMsg($message);
                    break;
                case 'NotifyMessage':
                    NotifyMessage::getInstance()->notify($message);
                    break;
                case 'GetuiMessage':
                    GetuiMessage::getInstance()->notify($message);
                default:
                    Log::warning('QueueService::ConsumerMessage message=%s error=%s', json_encode($message), 'undefined topic type');
                    break;
            }
        }catch (\Exception $e) {
            Log::error(sprintf('QueueService::ConsumerMessage message=%s errorLine=%d errorMsg=%s', json_encode($message), $e->getLine(), $e->getMessage()));
        }
    }
}