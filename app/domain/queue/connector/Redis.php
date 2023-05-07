<?php

namespace app\domain\queue\connector;

use app\common\QueueRedis;
use app\domain\queue\Connector;
use Closure;
use think\helper\Str;
use app\domain\queue\job\Redis as RedisJob;

class Redis extends Connector
{


    /** @var  \Redis */
    protected $redis;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default = 'default';

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * The maximum number of seconds to block for a job.
     *
     * @var int|null
     */
    protected $blockFor = 5;

    public function __construct($default = 'default', $retryAfter = 60, $blockFor = null)
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('redis扩展未安装');
        }
        $redis = QueueRedis::getInstance()->getRedis();
        $this->redis = $redis;
        $this->default = $default;
        $this->retryAfter = $retryAfter;
        $this->blockFor = is_null($blockFor) ? $this->blockFor : $blockFor;
    }


    public function size($queue)
    {
        echo 'redis pop';
        die;
    }

    public function pop($queue = null)
    {
        $this->migrate($prefixed = $this->getQueue($queue));
        if (empty($nextJob = $this->retrieveNextJob($prefixed))) {
            return;
        }
        [$job, $reserved] = $nextJob;
        if ($reserved) {
            return new RedisJob($this, $job, $reserved, $this->connection, $queue);
        }
    }

    /**
     * Migrate any delayed or expired jobs onto the primary queue.
     *
     * @param string $queue
     * @return void
     */
    protected function migrate($queue)
    {
        $this->migrateExpiredJobs($queue . ':delayed', $queue);

        if (!is_null($this->retryAfter)) {
            $this->migrateExpiredJobs($queue . ':reserved', $queue);
        }
    }

    /**
     * 移动延迟任务
     *
     * @param string $from
     * @param string $to
     * @param bool $attempt
     */
    public function migrateExpiredJobs($from, $to, $attempt = true)
    {
        $this->redis->watch($from);

        $jobs = $this->redis->zRangeByScore($from, '-inf', $this->currentTime());

        if (!empty($jobs)) {
            $this->transaction(function () use ($from, $to, $jobs, $attempt) {

                $this->redis->zRemRangeByRank($from, 0, count($jobs) - 1);

                for ($i = 0; $i < count($jobs); $i += 100) {

                    $values = array_slice($jobs, $i, 100);

                    $this->redis->rPush($to, ...$values);
                }
            });
        }

        $this->redis->unwatch();
    }


    /**
     * redis事务
     * @param Closure $closure
     */
    protected function transaction(Closure $closure)
    {
        $this->redis->multi();
        try {
            call_user_func($closure);
            if (!$this->redis->exec()) {
                $this->redis->discard();
            }
        } catch (\Exception $e) {
            $this->redis->discard();
        }
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime()
    {
        return time();
    }


    /**
     * @param $job
     * @param string $data
     * @param null $queue
     * @return mixed|string
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }


    /**
     * @param $payload
     * @param null $queue
     * @param array $options
     * @return mixed|string
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->redis->rPush($this->getQueue($queue), $payload);
        return json_decode($payload, true)['id'] ?? "";
    }

    /**
     * Retrieve the next job from the queue.
     *
     * @param string $queue
     * @return array
     */
    protected function retrieveNextJob($queue)
    {
        if (!is_null($this->blockFor)) {
            return $this->blockingPop($queue);
        }
        $job = $this->redis->lpop($queue);
        $reserved = false;

        if ($job) {
            $reserved = json_decode($job, true);
            $reserved['attempts']++;
            $reserved = json_encode($reserved);
            $this->redis->zAdd($queue . ':reserved', $this->availableAt($this->retryAfter), $reserved);
        }

        return [$job, $reserved];
    }


    /**
     * Retrieve the next job by blocking-pop.
     *
     * @param string $queue
     * @return array
     */
    protected function blockingPop($queue)
    {
        $rawBody = $this->redis->blpop($queue, $this->blockFor);
        if (!empty($rawBody)) {
            $payload = json_decode($rawBody[1], true);
            $payload['attempts']++;

            $reserved = json_encode($payload);
            $this->redis->zadd($queue . ':reserved', $this->availableAt($this->retryAfter), $reserved);
            return [$rawBody[1], $reserved];
        }

        return [null, null];
    }


    private function availableAt($delay = 0)
    {
        return time() + $delay;
    }


    /**
     * 获取队列名
     *
     * @param string|null $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        $queue = $queue ?: $this->default;
        return "{queues:{$queue}}";
    }


    protected function createPayloadArray($job, $data = '')
    {
        return array_merge(parent::createPayloadArray($job, $data), [
            'id' => $this->getRandomId(),
            'attempts' => 0,
        ]);
    }

    /**
     * 随机id
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }


    /**
     * 删除任务
     *
     * @param string $queue
     * @param \app\domain\queue\job\Redis $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->redis->zRem($this->getQueue($queue) . ':reserved', $job->getReservedJob());
    }


    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param string $queue
     * @param RedisJob $job
     * @param int $delay
     * @return void
     */
    public function deleteAndRelease($queue, $job, $delay)
    {
        $queue = $this->getQueue($queue);

        $reserved = $job->getReservedJob();

        $this->redis->zRem($queue . ':reserved', $reserved);

        $this->redis->zAdd($queue . ':delayed', $this->availableAt($delay), $reserved);
    }
}