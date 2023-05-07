<?php

namespace app\domain\queue\job;

use app\domain\queue\Job;
use think\App;

class Sync extends Job
{
    /**
     * The queue message data.
     *
     * @var string
     */
    protected $payload;

    public function __construct($payload, $connection, $queue)
    {
        $this->connection = $connection;
        $this->queue = $queue;
        $this->payload = $payload;
    }

    /**
     * Get the number of times the job has been attempted.
     * @return int
     */
    public function attempts()
    {
        return 1;
    }

    /**
     * Get the raw body string for the job.
     * @return string
     */
    public function getRawBody()
    {
        return $this->payload;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return '';
    }

    public function getQueue()
    {
        return 'sync';
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobTime()
    {
        return "";
    }
}
