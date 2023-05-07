<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class ExportZbAttentionCommand extends BaseCommand
{
    private $lastId = 0;
    private $limit = 1000;

    protected function configure()
    {
        $this->setName('ExportZbAttentionCommand')->setDescription('ExportZbAttentionCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $redis->del('attention_warning_set');
        $redis->del('attention_set');

        $fp = fopen(app()->getBasePath().'core/database/user/attention/insert.sql','a');
        fwrite($fp, "SET NAMES utf8mb4;"."\r\n");
        $refreshNumber = $this->doExecute($redis, $fp);
        $output->writeln(sprintf('app\command\ExportZbAttentionCommand success  refreshNumber:%d', $refreshNumber));
    }

    public function doExecute($redis, $fp){
        $refreshNumber = 0;
        for ($number = 1; $number <= 10000; $number++) {
            $where= [];
            $where[] = ['id', '>', $this->lastId];
            $where[] = ['type', '=', 0];
            $datas = Db::connect($this->baseDb)->table('zb_attention')->where($where)->limit($this->limit)->order('id asc')->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
                    $this->createSql($data, $redis, $fp);
                    $this->lastId = $data['id'];
                }
                $refreshNumber += $this->limit;
            } else {
                break;
            }
        }
        return $refreshNumber;
    }

    public function createSql($data, \Redis $redis, $fp) {
        try {
            $userId = $data['userid'];
            $userided = $data['userided'];
            $isRead = $data['status'];
            $createTime = TimeUtil::strToTime($data['attention_time']);
            $friendSql = '';
            $attentionSql = '';
            $fansSql = '';
            if ($redis->sIsMember('attention_set', $userId. '-'. $userided) || $userId == $userided) {
                $redis->sAdd('attention_warning_set', $data['id']);
            } else {
                $redis->sAdd('attention_set', $userId. '-'. $userided);
                $attentionOrFriendDatabase = Sharding::getInstance()->getDbName('userMaster', $userId);
                $fansDatabase = Sharding::getInstance()->getDbName('userMaster', $userided);
                $attentionOrFriendDatabaseName = config("database.connections.$attentionOrFriendDatabase.database");
                $fansDatabaseName = config("database.connections.$fansDatabase.database");
                if ($data['friend_status'] == 1) {
                    $friendSql = "insert into `$attentionOrFriendDatabaseName`."."`zb_user_friend` (`user_id`, `friend_id`, `create_time`) values ($userId, $userided, $createTime);";
                }
                $attentionSql = "insert into `$attentionOrFriendDatabaseName`."."`zb_user_attention` (`user_id`, `attention_id`, `create_time`) values ($userId, $userided, $createTime);";
                $fansSql = "insert into `$fansDatabaseName`."."`zb_user_fans` (`user_id`, `fans_id`, `create_time`, `is_read`) values ($userided, $userId, $createTime, $isRead);";
            }
            if ($friendSql) {
                fwrite($fp, $friendSql."\r\n");
            }
            if ($attentionSql) {
                fwrite($fp, $attentionSql."\r\n");
            }
            if ($fansSql) {
                fwrite($fp, $fansSql."\r\n");
            }
        }catch (\Exception $e) {
            Log::error(sprintf("AttentionModelDaoCommand saveData error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}