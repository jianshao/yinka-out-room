<?php

namespace app\api\shardingScript;

use think\console\Command;

ini_set('set_time_limit', 0);


class BaseCommand extends Command
{
    public $baseDb = 'dbOld';
}
