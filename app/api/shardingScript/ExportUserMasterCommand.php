<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class ExportUserMasterCommand extends BaseCommand
{
    private $limit = 2000;
    private $userTableNames = [
        'zb_user_bank',
//        'zb_user_asset_log', # 数据量太多 超千万条
//        'zb_user_asset_log_202205', # 数据量太多 超千万条
//        'zb_user_asset_log_202204', # 数据量太多 超千万条
//        'zb_user_asset_log_202203', # 数据量太多 超千万条
//        'zb_login_detail', # 数据量太多 超千万条
//        'zb_login_detail_new', # 数据量太多 超千万条
        'zb_duke_log',
        'zb_gashapon_reward',
        'zb_user_extend',
        'zb_pack',
        'zb_user_gift_wall',
        'zb_member_music',
        'zb_first_charge_reward',
        'zb_user_charge_statics',
        'zb_user_prop_bag',
        'zb_user_props',
        'zb_attire_user',
        'zb_room_follow',
        'zb_member_detail',
        'zb_today_earnings',
//        'zb_user_last_info', # 数据量太多 超千万条
//        'zb_user_online_census', # 数据量太多 超千万条
//        'zb_user_online_room_census', # 数据量太多 超千万条
    ];

    protected function configure()
    {
        $this->setName('ExportUserMasterCommand')->setDescription('ExportUserMasterCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\ExportUserMasterCommand execute start date:%s', TimeUtil::timeToStr(time())));
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);

        foreach ($this->userTableNames as $userTableName){
            $output->writeln(sprintf('app\command\ExportUserMasterCommand userTableName:%s date:%s',
                $userTableName, TimeUtil::timeToStr(time())));
            $fileName = app()->getBasePath().sprintf('core/database/user/db/%s.sql',$userTableName);
            if (file_exists($fileName)){
                unlink($fileName);
            }
            $fp = fopen($fileName,'a');
            fwrite($fp, "SET NAMES utf8mb4;"."\r\n");
            $redis->del($userTableName.'_warning_list');
            $this->doExecute($redis, $userTableName, $fp);
        }

        $output->writeln(sprintf('app\command\ExportUserMasterCommand execute end date:%s', TimeUtil::timeToStr(time())));
    }

    public function doExecute($redis, $userTableName, $fp){
        for ($number = 0; $number <= 100000; $number++) {
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
            $userId = ArrayUtil::safeGet($data, 'uid');
            if (empty($userId)){
                $userId = ArrayUtil::safeGet($data, 'user_id');
                if (empty($userId)){
                    $redis->lPush($userTableName.'_warning_list', json_encode($data));
                    return;
                }

            }

            foreach ($data as $k => $v){
                if (!empty($v) && is_string($v)){
                    $data[$k] = addslashes($data[$k]);
                }
            }

            $database = Sharding::getInstance()->getDbName('userMaster', $userId);
            $databaseName = config("database.connections.$database.database");
            $arr_keys = array_keys($data);
            $arr_values = array_values($data);
            $sql = "insert into `$databaseName`."."$userTableName (" . implode(',' ,$arr_keys) . ") values";
            $sql .= " ('" . implode("','" ,$arr_values) . "');";

            fwrite($fp, $sql."\r\n");

        }catch (\Exception $e) {
            Log::error(sprintf("ExportUserMasterCommand createSql error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}