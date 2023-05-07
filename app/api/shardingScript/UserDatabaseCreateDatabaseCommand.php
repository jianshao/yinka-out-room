<?php


namespace app\api\shardingScript;

use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);


class UserDatabaseCreateDatabaseCommand extends Command
{
    protected function configure()
    {
        $this->setName('UserDatabaseCreateTableCommand')->setDescription('UserDatabaseCreateTable');
    }

    protected $serviceName = 'userMaster';

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $dbMap = Sharding::getInstance()->getDbMap($this->serviceName);
        foreach ($dbMap as $dbName) {
            $sql = $this->getCreateDatabaseSql($dbName);
            Db::query($sql);
        }
    }
    public function getCreateDatabaseSql($dbName) {
        $databaseName = config('database.connections.'. $dbName. '.database');
        return "create database $databaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    }

}