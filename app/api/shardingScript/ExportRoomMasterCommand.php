<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\SnsTypes;
use app\query\user\cache\CachePrefix;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class ExportRoomMasterCommand extends BaseCommand
{
    private $limit = 1000;
    private $userTableNames = [
        'zb_room_music',
        'zb_room_black',
        'zb_room_wall'
    ];

    protected function configure()
    {
        $this->setName('ExportRoomMasterCommand')->setDescription('ExportRoomMasterCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);

        foreach ($this->userTableNames as $userTableName){
            $output->writeln(sprintf('app\command\ExportRoomMasterCommand userTableName:%s', sprintf('core/database/user/db/%s.sql',$userTableName)));
            $fileName = app()->getBasePath().sprintf('core/database/room/db/%s.sql',$userTableName);
            if (file_exists($fileName)){
                unlink($fileName);
            }
            $fp = fopen($fileName,'a');
            fwrite($fp, "SET NAMES utf8mb4;"."\r\n");
            $redis->del($userTableName.'_warning_list');
            $this->doExecute($redis, $userTableName, $fp);
        }
    }

    public function doExecute($redis, $userTableName, $fp){
        for ($number = 0; $number <= 10000; $number++) {
            $start = $number*$this->limit;
            $datas = Db::connect($this->baseDb)->table($userTableName)->limit($start, $this->limit)->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
                    $this->createSql($data, $redis,$userTableName, $fp);
                }
            } else {
                break;
            }
        }
    }

    public function createSql($data, $redis, $userTableName, $fp) {
        try {
            $roomId = ArrayUtil::safeGet($data, 'room_id');
            if (empty($roomId)){
                $redis->lPush($userTableName.'_warning_list', json_encode($data));
                return;

            }

            foreach ($data as $k => $v){
                if (!empty($v) && is_string($v)){
                    $data[$k] = addslashes($data[$k]);
                }
            }

            $database = Sharding::getInstance()->getDbName('roomMaster', $roomId);
            $databaseName = config("database.connections.$database.database");
            $arr_keys = array_keys($data);
            $arr_values = array_values($data);
            $sql = "insert into `$databaseName`."."$userTableName (" . implode(',' ,$arr_keys) . ") values";
            $sql .= " ('" . implode("','" ,$arr_values) . "');";

            fwrite($fp, $sql."\r\n");

        }catch (\Exception $e) {
            Log::error(sprintf("ExportRoomMasterCommand createSql error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}