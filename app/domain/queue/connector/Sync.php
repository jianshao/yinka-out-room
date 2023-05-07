<?php

namespace app\domain\queue\connector;

use app\domain\queue\Connector;
use app\domain\queue\job\Sync as SyncJob;

class Sync extends Connector
{


    public function size($queue = null)
    {
        return 0;
    }

    /**
     * @param $job
     * @param string $data
     * @param null $queue
     * @return string
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data), $queue);
        $queueJob->fire();
        return "";
    }

    protected function resolveJob($payload, $queue)
    {
        return new SyncJoB($payload, $this->connection, $queue);
    }

    protected function triggerEvent($event)
    {
        $this->app->event->trigger($event);
    }

    public function pop($queue = null)
    {

    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {

    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

}