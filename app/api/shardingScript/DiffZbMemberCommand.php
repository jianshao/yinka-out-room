<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class DiffZbMemberCommand extends BaseCommand
{
    private $lastId = 0;
    private $limit = 2000;
    protected function configure()
    {
        $this->setName('DiffZbMemberCommand')->setDescription('DiffZbMemberCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $fileOld = fopen(app()->getBasePath().'core/database/user/member/diffOld.txt','a');
        fwrite($fileOld, "userId      totalcoin    freecoin    diamond    exchange_diamond    free_diamond"."\r\n");
        $fileNew = fopen(app()->getBasePath().'core/database/user/member/diffNew.txt','a');
        fwrite($fileNew, "userId      totalcoin    freecoin    diamond    exchange_diamond    free_diamond"."\r\n");
        $this->doExecute($fileOld, 'dbOld');
        $this->doExecute($fileNew, 'dbNew');
    }

    public function doExecute($file, $dbName){
        for ($number = 1; $number <= 10000; $number++) {
            $where[] = ['id', '>', $this->lastId];
            $datas = Db::connect($dbName)->table('zb_member')->where($where)->limit($this->limit)->order('id asc')->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
                    $this->createFile($data, $file);
                    $this->lastId = $data['id'];
                }
            } else {
                $this->lastId = 0;
                break;
            }
        }
    }

    public function createFile($data, $file) {
        try {
            $txt = "$data[id]    $data[totalcoin]    $data[freecoin]    $data[diamond]    $data[exchange_diamond]    $data[free_diamond]";
            fwrite($file, $txt."\r\n");
        }catch (\Exception $e) {
            Log::error(sprintf("DiffZbMemberCommand createFile error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
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