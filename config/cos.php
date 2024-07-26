<?php

use think\facade\Env;

return [
    "ACCESS_KEY_ID" => '',
    "ACCESS_KEY_SECRET" => '',
    "ENDPOINT" => 'sts.tencentcloudapi.com',
    "BUCKET" => 'like-game-1318171620',
    "Region" => 'ap-beijing',

    "STS" => [
        "Name" => 'client_name',
        "Policy" => '{
          "version": "2.0",
          "statement": [
            {
              "action": [
                "*"
              ],
              "effect": "allow",
              "resource": [
                "*"
              ]
            }
          ]
        }',
        "DurationSeconds" => 3600,
    ]
];


