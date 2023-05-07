queue task example


配置queue config
```

return [
    'default'     => 'redis',
    'connections' => [
        'sync'     => [
            'type' => 'sync',
        ],
        'database' => [
            'type'       => 'database',
            'queue'      => 'default',
            'table'      => 'jobs',
            'connection' => null,
        ],
        'redis'    => [
            'type'       => 'redis',
            'queue'      => 'default',
            'host'       => env('queue.host', '127.0.0.1'),
            'port'       => env('queue.port', 6379),
            'password'   => env('queue.password', ''),
            'select'     => 5,      // 使用哪一个 db，默认为 db0
            'timeout'    => 0,      // redis连接的超时时间
            'persistent' => true,  // 是否是长连接
        ],
    ],
    'failed'      => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],

];
```



入队:
```
use app\domain\queue\worker\Worker;

        $consumer = 'app\domain\queue\consumer\YunXinMsg@run';  //消费者类
        $queue = 'message'; //队列名称
        $jobData = [];
        $jobData["a"] = 'a';
        $jobData['b'] = 'b';
        $re=worker::getInstance()->push($consumer,$jobData,$queue);
```

消费

```
use app\domain\queue\worker\Worker;

        $queue="default";
        worker::getInstance()->daemon($queue);
```
