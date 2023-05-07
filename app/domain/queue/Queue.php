<?php

namespace app\domain\queue;

use app\domain\exceptions\FQException;

class Queue
{

    protected $driver = "";

    private $drivers=[];

    /**
     * 默认驱动
     * @return string
     */
    public function getDefaultDriver()
    {
        $this->driver = config("queue.default", '');
        if (empty($queue)) {
            throw new FQException("get queue config error");
        }
        return $this->driver;
    }

    protected function resolveType(string $name)
    {
        return config("queue.connections.{$name}.type", 'sync');
    }

    protected function resolveConfig(string $name)
    {
        return config("queue.connections.{$name}");
    }

    /**
     * @param null|string $name
     * @return Connector
     */
    public function connection($name = null)
    {
        return ClassRegister::getInstance()->handle($name);
    }




}