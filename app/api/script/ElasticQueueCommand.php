<?php

namespace app\api\script;

use app\common\amqp\core\AmpQueue;
use app\common\amqp\core\AmpService;
use app\common\amqp\event\EventService;
use app\common\amqp\model\AmpMessageModel;
use app\common\amqp\model\AmpTag;
use app\common\amqp\model\AmpTopic;
use app\domain\exceptions\FQException;
use app\utils\Error;
use PhpAmqpLib\Message\AMQPMessage;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  elastic queue（room）
 * Class ElasticQueueCommand
 * @package app\api\script
 * @command  php think ElasticQueueCommand consumerUser >> /tmp/RecallQueueConsumerUser.log 2>&1
 * @command  php think ElasticQueueCommand consumerRoom >> /tmp/RecallQueueConsumer.log 2>&1
 */
class ElasticQueueCommand extends Command
{
    private $offset = 0;
    private $endOffset = 0;
    private $appDev;

    const COMMAND_NAME = "ElasticQueueCommand";

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\ElasticQueueCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->addArgument('queuename', Argument::OPTIONAL, "queuename")
            ->setDescription('ElasticQueueCommand');
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getDateTime()
    {
        return date("Y-m-d H:i:s", $this->getUnixTime());
    }

    protected function execute(Input $input, Output $output)
    {
        $func = $input->getArgument('func') ?: "handler";
        $output->writeln(sprintf('app\command\ElasticQueueCommand entry func:%s offset:%d endOffset:%d date:%s', $func, $this->offset, $this->endOffset, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\ElasticQueueCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
        }
        // 指令输出
        $output->writeln(sprintf('app\command\ElasticQueueCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @info 启动召回-任务解析消费者 member_recall_test
     * @param Output $output
     * @return int
     */
    private function handler()
    {
        echo 'success';
        die;
//        echo 'elastic room handler';die;
//        $tag = AmpTag::$messageBusElasTicUser;
        $tag = AmpTag::$messageBusElasTicUser;
//        初始化所有build
        $body = '{"user_id":123,"room_id":1233453,"timestamp":1650009589}';
        EventService::getInstance()->doEvent($tag, $body);
    }

    private function tagHandler($tag)
    {
        //        对象映射
        $namespace = "app\\domain\\elastic\\event\\handler\\ElasticSyncHandler";
        $method = sprintf("on%s", $tag);
        $reflModel = $this->isReflection($namespace, $method);
        if ($method === false) {
            throw new FQException("reflection error", 500);
        }
        return $this->doReflection($reflModel);
    }


    private function doReflection(\ReflectionMethod $reflModel)
    {
        if ($reflModel === false) {
            return false;
        }
        $name = $reflModel->name;
        $obj = new $reflModel->class;
        return $obj->$name();
    }

    /**
     * @param $namespace
     * @param $method
     * @return false|\ReflectionMethod
     */
    private function isReflection($namespace, $method)
    {
        try {

            $class = new \ReflectionClass($namespace);
            $model = $class->getMethod($method);
        } catch (\ReflectionException $e) {
            return false;
        }
        return $model;
    }

    private function testPublisherMessageBus()
    {
        $strData = '{"push_recall_conf":"{\"id\":4,\"push_when\":{\"charge_max\":500,\"charge_min\":100,\"time\":3600},\"push_type\":\"getuipush\",\"template_ids\":\"[102,109]\"}","user_ids":"[1456410,1456408,1456402]"}';
        $unixTime = AmpService::getInstance()->loadUnixTime();
        $message = new AmpMessageModel();
        $message->runType = 0;
        $message->topic = AmpTopic::$GENERAL;
        $message->tag = AmpTag::$messageBusElasTicUser;
        $message->msgId = AmpService::getInstance()->loadMsgId();
        $message->partition = AmpService::getInstance()->loadPartition(null, null, $unixTime);
        $message->timestamp = $unixTime;
        $message->body = $strData;
        $re = AmpQueue::getInstance()->publisherMessageBusModel($message);
        dd($re);
    }

    /**
     * @param $output
     * @param $msg
     */
    private function storeLog($output, $msg)
    {
        $logmsg = sprintf("ElasticQueueCommand entry msgBody:%s", $msg);
        $output->info($logmsg);
        Log::info($logmsg);
    }

    /**
     * @param $output
     * @param $msg
     * @param \Exception $e
     */
    private function storeLogErr($output, $msg, \Exception $e)
    {
        $logmsg = sprintf("ElasticQueueCommand error msgBody:%s errmsg:%s errstrace:%s", $msg->body, $e->getMessage(), $e->getTraceAsString());
        $output->info($logmsg);
        Log::info($logmsg);
    }

    private function consumerUser()
    {
        $output = $this->output;
        $callback = function (AMQPMessage $msg) use ($output) {
            try {
                $this->storeLog($output, $msg->body);
                if (!$msg->body) {
                    return;
                }
                $model = new AmpMessageModel;
                $model->fromJson(json_decode($msg->body, true));
                AmpService::getInstance()->verifyMessage($model, AmpTopic::$GENERAL, AmpTag::$messageBusElasTicUser);
//                $this->storeLog($output, "entry consumerUser verifyMessage success");
                EventService::getInstance()->doEvent($model->event, $model->body);
                $msg->ack();
                usleep(200000);
            } catch (\Exception $e) {
                $code = $e->getCode();
                if ($code === Error::ERROR_AUTH_IDEMPOTENT || $code === Error::ERROR_AUTH_TOPIC_TAG || $code === Error::ERROR_AUTH_PARTITION) {
                    $msg->nack();
                    return;
                }
                $this->storeLogErr($output, $msg, $e);
            }
        };
        return AmpQueue::getInstance()->consumerElasticQueueUser($callback);
    }


    private function consumerRoom()
    {
        $output = $this->output;
        $callback = function (AMQPMessage $msg) use ($output) {
            try {
                $this->storeLog($output, $msg->body);
                if (!$msg->body) {
                    return;
                }
//                load业务模型
                $model = new AmpMessageModel;
                $model->fromJson(json_decode($msg->body, true));
                AmpService::getInstance()->verifyMessage($model, AmpTopic::$GENERAL, AmpTag::$messageBusElasticRoom);
//                $this->storeLog($output, "entry consumerRoom verifyMessage success");
                EventService::getInstance()->doEvent($model->event, $model->body);
                $msg->ack();
                usleep(200000);
            }catch (\Exception $e) {
                $code = $e->getCode();
                if ($code === Error::ERROR_AUTH_IDEMPOTENT || $code === Error::ERROR_AUTH_TOPIC_TAG || $code === Error::ERROR_AUTH_PARTITION) {
                    $msg->nack();
                    return;
                }
                $this->storeLogErr($output, $msg, $e);
            }
        };

        return AmpQueue::getInstance()->consumerElasticQueueRoom($callback);
    }


}
