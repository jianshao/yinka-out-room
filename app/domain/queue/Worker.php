<?php

namespace app\domain\queue;


use app\common\QueueRedis;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\queue\ClassRegister;
use app\domain\queue\connector\Redis;
use app\domain\queue\connector\Sync;
use app\domain\queue\consumer\GetuiMessage;
use app\domain\queue\consumer\KuaishouMessage;
use app\domain\queue\consumer\NotifyMessage;
use app\domain\queue\consumer\YunXinMsg;
use Jobby\Exception;
use think\facade\Log;
use Throwable;


class Worker
{
    private static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new worker(new Queue);
            self::$instance->redis = QueueRedis::getInstance()->getRedis();
        }
        return self::$instance;
    }

    private $connection;

    /**
     * @info 初始化参数和配置
     * @param $sex
     */
    private function initConf()
    {
        $connection = config("queue.default", "");
        if (empty($connection)) {
            throw new FQException("queue config error");
        }
        $this->connection = $connection;

        ClassRegister::getInstance()->register('redis', Redis::class);
        ClassRegister::getInstance()->register('sync', Sync::class);
        ClassRegister::getInstance()->register('app\domain\queue\consumer\NotifyMessage', NotifyMessage::class);
        ClassRegister::getInstance()->register('app\domain\queue\consumer\YunXinMsg', YunXinMsg::class);
        ClassRegister::getInstance()->register('app\domain\queue\consumer\GetuiMessage', GetuiMessage::class);
        ClassRegister::getInstance()->register('app\domain\queue\consumer\KuaishouMessage', KuaishouMessage::class);
    }


    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
        $this->initConf();
    }

    /**
     * @param $job
     * @param $jobData
     * @param string $queue
     * @return mixed
     */
    public function push($job, $jobData, $queue = "")
    {
        return $this->queue->connection($this->connection)->push($job, $jobData, $queue);
    }


    /**
     * @param string $connection
     * @param string $queue
     * @param int $delay
     * @param int $sleep
     * @param int $maxTries
     * @param int $memory
     * @param int $timeout
     */
    public function daemon($queue, $delay = 0, $sleep = 3, $maxTries = 0, $memory = 128, $timeout = 60)
    {
        try {
            while (true) {
                $job = $this->getNextJob(
                    $this->queue->connection($this->connection), $queue
                );
                if ($job) {
                    $this->runJob($job, $this->connection, $maxTries, $delay);
                } else {
//                nothing to  jobs
                    throw  new \Exception("not find more jobs");
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die;
        }

    }


    /**
     * 执行任务
     * @param Job $job
     * @param string $connection
     * @param int $maxTries
     * @param int $delay
     * @return void
     */
    protected function runJob($job, $connection, $maxTries, $delay)
    {
        try {
            $this->process($connection, $job, $maxTries, $delay);
        } catch (Exception | Throwable $e) {
            throw new FQException($e->getMessage());
        }
    }


    /**
     * Register the worker timeout handler.
     *
     * @param Job|null $job
     * @param int $timeout
     * @return void
     */
    protected function registerTimeoutHandler($job, $timeout)
    {
        pcntl_signal(SIGALRM, function () {
            $this->kill(1);
        });

        pcntl_alarm(
            max($this->timeoutForJob($job, $timeout), 0)
        );
    }


    /**
     * Determine if the queue worker should restart.
     *
     * @param int|null $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
        });
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl');
    }

    /**
     * Process a given job from the queue.
     * @param string $connection
     * @param Job $job
     * @param int $maxTries
     * @param int $delay
     * @return void
     * @throws Exception
     */
    public function process($connection, $job, $maxTries = 0, $delay = 0)
    {
        try {
            $job->fire();
        } catch (\Exception | Throwable $e) {
            try {
                if (!$job->hasFailed()) {
//                    检查过期和retry次数
                    $this->markJobAsFailedIfWillExceedMaxAttempts($connection, $job, (int)$maxTries, $e);
                }
            } finally {
                if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
                    $job->release($delay);
                }
            }
            throw $e;


        }
    }

    /**
     * @param string $connection
     * @param Job $job
     * @param int $maxTries
     * @param Exception $e
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts($connection, $job, $maxTries, $e)
    {
        $maxTries = !is_null($job->maxTries()) ? $job->maxTries() : $maxTries;
        if ($job->timeoutAt() && $job->timeoutAt() <= time()) {
            $this->failJob($connection, $job, $e);
        }

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($connection, $job, $e);
        }
    }

    /**
     * @param string $connection
     * @param Job $job
     * @param Exception $e
     */
    protected function failJob($connection, $job, $e)
    {
        $job->markAsFailed();

        if ($job->isDeleted()) {
            return;
        }

        try {
            $job->delete();

            $job->failed($e);
        } finally {
            Log::ERROR(sprintf("failJob error payload:%s", json_encode($job->payload())));
//            $this->
//            $this->event->trigger(new JobFailed(
//                $connection, $job, $e ?: new RuntimeException('ManuallyFailed')
//            ));
        }
    }


    /**
     * 获取下个任务
     * @param Connector $connector
     * @param string $queue
     * @return Job
     */
    protected function getNextJob($connector, $queue)
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                if (!is_null($job = $connector->pop($queue))) {
                    return $job;
                }
            }
        } catch (Exception | Throwable $e) {
//            $this->handle->report($e);
            $this->sleep(1);
        }
    }

    /**
     * Sleep the script for a given number of seconds.
     * @param int $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }
}
