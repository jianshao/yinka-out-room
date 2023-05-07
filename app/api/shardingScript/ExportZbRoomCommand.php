<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class ExportZbRoomCommand extends BaseCommand
{
    private $lastId = 0;
    private $limit = 1000;

    protected function configure()
    {
        $this->setName('ExportZbRoomCommand')->setDescription('ExportZbRoomCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $redis->del('room_info_user_set');
        $redis->del('room_info_user_warning_set');
        $redis->del('room_info_guild_set');
        $redis->del('room_info_guild_warning_set');


        $fpMap = fopen(app()->getBasePath().'core/database/room/insertmap.sql','a');
        fwrite($fpMap, "SET NAMES utf8mb4;"."\r\n");
        $fpRoom = fopen(app()->getBasePath().'core/database/room/insertroom.sql','a');
        fwrite($fpRoom, "SET NAMES utf8mb4;"."\r\n");
        $this->doExecute($redis, $fpMap, $fpRoom);
    }

    public function doExecute($redis, $fpMap, $fpRoom){
        for ($number = 1; $number <= 10000; $number++) {
            $where[] = ['id', '>', $this->lastId];
            $datas = Db::connect($this->baseDb)->table('zb_languageroom')->where($where)->limit($this->limit)->order('id asc')->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
//                    $this->createDbsSql($data, $redis, $fpMap);
                    $this->createMapSql($data, $redis, $fpRoom);
                    $this->lastId = $data['id'];
                }
            } else {
                break;
            }
        }
    }

    public function createMapSql($data, \Redis $redis, $fpMap) {
        try {

            $roomId = $data['id'];
            $userId = $data['user_id'];
            $guildId = $data['guild_id'];
            $database = Sharding::getInstance()->getDbName('commonMaster', $userId);
            $databaseName = config("database.connections.$database.database");

            $userSql = null;
            $guildSql = null;

            if ($redis->zScore('room_info_user_set', $userId)) {
                $redis->zAdd('room_info_user_warning_set', $roomId, $userId);
            }else{
                $redis->zAdd('room_info_user_set', $roomId, $userId);
                $userSql = "insert into `$databaseName`."."`zb_room_info_map` (`room_id`, `type`, `value`) values ($roomId, 'user_id', "."'$userId'".");";
            }

            if (intval($guildId) > 0) {
                $guildSql = "insert into `$databaseName`."."`zb_room_info_map` (`room_id`, `type`, `value`) values ($roomId, 'guild_id', "."'$guildId'".");";
                $redis->sAdd('room_info_guild_set', $roomId);
            }

            if ($userSql) {
                fwrite($fpMap, $userSql."\r\n");
            }
            if ($guildSql) {
                fwrite($fpMap, $guildSql."\r\n");
            }

        }catch (\Exception $e) {
            Log::error(sprintf("ExportZbRoomCommand createMapSql error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }

    public function createDbsSql($data, \Redis $redis, $fpRoom) {
        try {
            $roomId = $data['id'];
            $userId = $data['user_id'];
            if ($redis->sIsMember('room_user_set', $roomId . '-' . $userId)) {
                $redis->sAdd('room_user_set', $roomId . '-' . $userId);
            } else{
                $redis->sAdd('room_user_set', $roomId. '-'. $userId);
                $database = Sharding::getInstance()->getDbName('roomMaster', $roomId);
                $databaseName = config("database.connections.$database.database");
                $arr_keys = array_keys($data);
                $arr_values = array_values($data);
                $sql = "insert into `$databaseName`."."zb_languageroom (" . implode(',' ,$arr_keys) . ") values";
                $sql .= " ('" . implode("','" ,$arr_values) . "');";
                fwrite($fpRoom, $sql."\r\n");
            }

        }catch (\Exception $e) {
            Log::error(sprintf("ExportZbRoomCommand createDbsSql error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}