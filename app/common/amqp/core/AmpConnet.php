<?php

namespace app\common\amqp\core;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Wire\AMQPTable;

class AmpConnet
{

    const WAIT_BEFORE_RECONNECT_uS = 1000000;

// Assume we have a cluster of nodes on ports 5672, 5673 and 5674.
// This should be possible to start on localhost using RABBITMQ_NODE_PORT
    const PORT1 = 5672;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }


    /*
        To handle arbitrary node restart you can use a combination of connection
        recovery and mulltiple hosts connection.
    */

    public function handler(\Closure $callback)
    {
        $connection = null;
        while (true) {
            try {
                $connection = $this->connect();
                register_shutdown_function([$this, 'shutdown'], $connection);
                // Your application code goes here.
                $this->do_something_with_connection($connection, $callback);
            } catch
            (AMQPRuntimeException $e) {
                echo $e->getMessage() . PHP_EOL;
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            } catch (\RuntimeException $e) {
                echo 'Runtime exception ' . PHP_EOL;
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            } catch (\ErrorException $e) {
                echo 'Error exception ' . PHP_EOL;
                $this->cleanup_connection($connection);
                usleep(self::WAIT_BEFORE_RECONNECT_uS);
            }
        }
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    private function connect()
    {
        // If you want a better load-balancing, you cann reshuffle the list.
        return AMQPStreamConnection::create_connection([
//            ['host' => HOST, 'port' => self::PORT1, 'user' => self::USER, 'password' => self::PASS, 'vhost' => self::VHOST]
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'user' => $this->config['user'], 'password' => $this->config['pass'], 'vhost' => $this->config['vhost']]
        ],
            [
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => 10.0,
                'read_write_timeout' => 10.0,
                'context' => null,
                'keepalive' => false,
                'heartbeat' => 0
            ]);
    }

    private function cleanup_connection($connection)
    {
        // Connection might already be closed.
        // Ignoring exceptions.
        try {
            if ($connection !== null) {
                $connection->close();
            }
        } catch (\ErrorException $e) {
        }
    }


    private function do_something_with_connection($connection, $callback)
    {
        $queue = $this->config['queue_name'];
        $consumerTag = 'consumer';
        $channel = $connection->channel();
        $channel->queue_declare($queue, false, true, false, false);
        $channel->basic_consume($queue, $consumerTag, false, false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }


    /**
     * @param \PhpAmqpLib\Connection\AbstractConnection $connection
     */
    private function shutdown($connection)
    {
        $connection->close();
    }


}


