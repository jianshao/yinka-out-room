<?php

namespace app\domain\queue\job;

use app\domain\queue\Job;
use app\domain\queue\connector\Redis as RedisQueue;

class Redis extends Job
{

    /**
     * The redis queue instance.
     * @var RedisQueue
     */
    protected $redis;

    /**
     * The database job payload.
     * @var Object
     */
    protected $job;

    /**
     * The JSON decoded version of "$job".
     *
     * @var array
     */
    protected $decoded;

    /**
     * The Redis job payload inside the reserved queue.
     *
     * @var string
     */
    protected $reserved;

    public function __construct(RedisQueue $redis, $job, $reserved, $connection, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->connection = $connection;
        $this->redis = $redis;
        $this->reserved = $reserved;

        $this->decoded = $this->payload();
    }

    /**
     * Get the number of times the job has been attempted.
     * @return int
     */
    public function attempts()
    {
        return ($this->decoded['attempts'] ?? null) + 1;
    }

    /**
     * Get the raw body string for the job.
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }

    /**
     * 删除任务
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->redis->deleteReserved($this->queue, $this);
    }

    /**
     * 重新发布任务
     *
     * @param int $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->redis->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->decoded['id'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getJobTime()
    {
        return $this->decoded['createTime'] ?? null;
    }
    /**
     * Get the underlying reserved Redis job.
     *
     * @return string
     */
    public function getReservedJob()
    {
        return $this->reserved;
    }



}
