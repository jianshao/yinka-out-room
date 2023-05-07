<?php

use think\facade\Env;


return [
//    DEFAULT_TIMEZONE
    'version' => Env::get('attire.version', '1617694687'),
    'prop_nav' => [
        'avatar' => ['type' => 'avatar', 'name' => '头像框'],
        'bubble' => ['type' => 'bubble', 'name' => '消息气泡'],
        'voiceprint' => ['type' => 'voiceprint', 'name' => '麦位光圈'],
        'mount' => ['type' => 'mount', 'name' => '坐骑']
    ]
];

