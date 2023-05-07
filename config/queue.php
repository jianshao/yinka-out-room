<?php

return [
    'default'     => 'sync',  //默认是sync，改成redis
    'connections' => [
        'redis'    => [
            'type'       => 'redis',
            'queue'      => 'default',
            'host'       => env('queue.host', 'r-2zep27hvk4ys3nypqu.redis.rds.aliyuncs.com'),
            'port'       => env('queue.port', 6379),
            'password'   => env('queue.password', 'nPyOOousxrIT7IQq'),
            'select'     => 5,      // 使用哪一个 db，默认为 db0
            'timeout'    => 0,      // redis连接的超时时间
            'persistent' => true,  // 是否是长连接
        ],
    ],

];
