<?php


namespace app\api\shardingScript2\user;

use app\core\mysql\Sharding;
use app\utils\ArrayUtil;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);


class DelDataCommand extends Command
{
    protected function configure()
    {
        $this->setName('DelDataCommand')
            ->addArgument('dbname', Argument::OPTIONAL, "database name")
            ->addArgument('tableName', Argument::OPTIONAL, 'table name')
            ->setDescription('DelDataCommand');
    }

    protected $serviceName = 'userMaster';

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $dbname = $input->getArgument('dbname');
        $tableName = $input->getArgument('tableName');
        $userId = $this->getUserId($tableName);
        switch ($dbname) {
            case 'userMaster1':
                $where = " where $userId % 4 != 0";
                break;
            case 'userMaster2':
                $where = " where $userId % 4 != 1";
                break;
            case 'userMaster3':
                $where = " where $userId % 4 != 2";
                break;
            case 'userMaster4':
                $where = " where $userId % 4 != 3";
                break;
            default:
                echo "error database name";die;
        }
        $sql = "delete from $tableName". $where;
        $startTime = microtime(true);
        echo sprintf("dbName:%s tableName:%s sql:%s startTime:%s", $dbname, $tableName, $sql, $startTime).PHP_EOL;
        Db::connect($dbname)->query($sql);
        echo sprintf("dbName:%s tableName:%s sql:%s execTime:%s", $dbname, $tableName, $sql, microtime(true) - $startTime).PHP_EOL;
    }

    private function getUserId ($tableName) {
        $tableArr = [
            'zb_member' => 'id',
            'zb_user_bank' => 'uid',
            'zb_duke_log' => 'uid',
            'zb_gashapon_reward' => 'uid',
            'zb_user_extend' => 'uid',
            'zb_pack' => 'user_id',
            'zb_user_gift_wall' => 'uid',
            'zb_reward_record' => 'uid',
            'zb_member_music' => 'user_id',
            'zb_first_charge_reward' => 'user_id',
            'zb_user_charge_statics' => 'uid',
            'zb_user_prop_bag' => 'uid',
            'zb_user_props' => 'uid',
            'zb_attire_user' => 'user_id',
            'zb_room_follow' => 'user_id',
            'zb_user_attention' => 'user_id',
            'zb_user_fans' => 'user_id',
            'zb_user_friend' => 'user_id',
            'zb_member_detail' => 'user_id',
            'zb_today_earnings' => 'uid',
        ];
        $userId = ArrayUtil::safeGet($tableArr, $tableName);
        if (empty($userId)) {
            echo "error table name";die;
        }
        return $userId;
    }

}