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

class ExportUserTaskCommand extends BaseCommand
{
    private $limit = 4000;
    private $userTableNames = [
        'zb_task_activebox', # 数据量太多 超千万条
        'zb_task_daily', # 数据量太多 超千万条
        'zb_task_checkin', # 数据量太多 超千万条
        'zb_task_newer', # 数据量太多 超千万条
    ];

    protected function configure()
    {
        $this->setName('ExportUserTaskCommand')->setDescription('ExportUserTaskCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\ExportUserTaskCommand execute start date:%s', TimeUtil::timeToStr(time())));
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);

        foreach ($this->userTableNames as $userTableName){
            $output->writeln(sprintf('app\command\ExportUserTaskCommand userTableName:%s date:%s',
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

        $output->writeln(sprintf('app\command\ExportUserTaskCommand execute end date:%s', TimeUtil::timeToStr(time())));
    }

    public function doExecute($redis, $userTableName, $fp){
        for ($number = 0; $number <= 10000; $number++) {
            $start = $number*$this->limit;
            # 新手任务所有的都需要查询，不是新手任务，查最近一周就行
            if ($userTableName == 'zb_task_newer'){
                $where1= [
                    ['finishCount', '>=', 1]
                ];
                $where2= [
                    ['gotReward', '>=', 1]
                ];
                $where3= [
                    ['progress', '>=', 1]
                ];
                $datas = Db::connect($this->baseDb)->table($userTableName)->whereOr($where1)->whereOr($where2)->whereOr($where3)->limit($start, $this->limit)->select()->toArray();
            }else{
                $where= [
                    ['updateTime', '>=', time()-7*24*3600]
                ];
                $datas = Db::connect($this->baseDb)->table($userTableName)->where($where)->limit($start, $this->limit)->select()->toArray();
            }
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