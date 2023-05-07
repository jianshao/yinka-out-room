<?php
namespace app\domain\queue;

abstract class Connector
{

    /**
     * The connector name for the queue.
     *
     * @var string
     */
    protected $connection;

    protected $options = [];

    abstract public function size($queue);
    abstract public function push($job, $data = '', $queue = null);
    abstract public function pushRaw($payload, $queue = null, array $options = []);
    abstract public function pop($queue = null);

    protected function createPayload($job, $data = '')
    {
        $payload = $this->createPayloadArray($job, $data);
        $payload = json_encode($payload);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Unable to create payload: ' . json_last_error_msg());
        }

        return $payload;
    }

    protected function createPayloadArray($job, $data = '')
    {
        return is_object($job)
            ? $this->createObjectPayload($job)
            : $this->createPlainPayload($job, $data);
    }


    protected function createObjectPayload($job)
    {
        return [
            'job'       => 'think\queue\CallQueuedHandler@call',
            'maxTries'  => $job->tries ?? null,
            'timeout'   => $job->timeout ?? null,
            'timeoutAt' => $this->getJobExpiration($job),
            'data'      => [
                'commandName' => get_class($job),
                'command'     => serialize(clone $job),
            ],
        ];
    }

    protected function createPlainPayload($job, $data)
    {
        $time=time();
        return [
            'job'      => $job,
            'maxTries' => 3,
            'timeout'  => null,
            'timeoutAt'  => $time+30,
            'data'     => $data,
            'createTime'=>$time,
        ];
    }

}
