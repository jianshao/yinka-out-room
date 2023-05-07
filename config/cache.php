<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Env;

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => 'redis',

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        'redis' => [
            // 驱动方式
            'type'       => 'redis',
            'host'      => Env::get('redis.hostname', 'r-2zep27hvk4ys3nypqu.redis.rds.aliyuncs.com'),
            'port'      => intval(Env::get('redis.port', 6379)),
            'password'  => Env::get('redis.password', 'nPyOOousxrIT7IQq'),
        ],
        'aliredis' => [
            // 驱动方式
            'type'       => 'redis',
            'host'      => Env::get('redis.hostname', 'r-2zep27hvk4ys3nypqu.redis.rds.aliyuncs.com'),
            'port'      => intval(Env::get('redis.port', 6379)),
            'password'  => Env::get('redis.password', 'nPyOOousxrIT7IQq'),
        ],
        'cacheredis' => [
            // 驱动方式
            'type'       => 'redis',
            'host'      => Env::get('redis.hostname', 'r-2zep27hvk4ys3nypqu.redis.rds.aliyuncs.com'),
            'port'      => intval(Env::get('redis.port', 6379)),
            'password'  => Env::get('redis.password', 'nPyOOousxrIT7IQq'),
        ],
        // 更多的缓存连接
    ],
];
