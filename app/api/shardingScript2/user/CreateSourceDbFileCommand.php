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


class CreateSourceDbFileCommand extends Command
{

    private $dbName = 'oldDb';
    private $start = 0;
    private $limit = 1000;
    protected function configure()
    {
        $this->setName('CreateSourceDbFileCommand')
            ->addArgument('tableName', Argument::OPTIONAL, 'table name')
            ->setDescription('CreateCurrentDbFileCommand');
    }

    protected $serviceName = 'userMaster';

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $tableName = $input->getArgument('tableName');
        $column = $this->getUserId($tableName);
        $startTime = microtime(true);
        $output->writeln(sprintf('CreateSourceDbFileCommand execute start time:%s', $startTime));
        $userFile1 = fopen(app()->getRootPath()."filediff/olddb/userMaster1/$tableName.txt",'a');
        $userFile2 = fopen(app()->getRootPath()."filediff/olddb/userMaster2/$tableName.txt",'a');
        $userFile3 = fopen(app()->getRootPath()."filediff/olddb/userMaster3/$tableName.txt",'a');
        $userFile4 = fopen(app()->getRootPath()."filediff/olddb/userMaster4/$tableName.txt",'a');
        if ($tableName == 'zb_member') {
            while (true) {
                $where = [];
                $where[] = ['id', '>=', $this->start]; // 1
                $where[] = ['id', '<', $this->start + $this->limit]; // 500
                $data = Db::connect($this->dbName)->table($tableName)->field('id,totalcoin,freecoin,diamond,exchange_diamond,free_diamond')->where($where)->select()->toArray();
                if (empty($data) && $this->start >= 1000000) {
                    break;
                }
                $this->createFile($data, $column, $userFile1, $userFile2, $userFile3, $userFile4);
                $this->start += $this->limit;
            }
        } elseif ($tableName == 'zb_user_props') {
            while (true) {
                $data = Db::connect($this->dbName)->table($tableName)->limit($this->start, $this->limit)->order(['uid'=>'asc', 'kind_id' => 'asc', 'create_time'=>'asc'])->select()->toArray();
                if (empty($data)) {
                    break;
                }
                $this->createFile($data, $column, $userFile1, $userFile2, $userFile3, $userFile4);
                $this->start += $this->limit;
            }
        } else {
            while (true) {
                $data = Db::connect($this->dbName)->table($tableName)->limit($this->start, $this->limit)->select()->toArray();
                if (empty($data)) {
                    break;
                }
                $this->createFile($data, $column, $userFile1, $userFile2, $userFile3, $userFile4);
                $this->start += $this->limit;
            }
        }
        $output->writeln(sprintf('CreateSourceDbFileCommand execute end time:%s', microtime(true) - $startTime));
    }

    private function createFile($datas, $column, $userFile1, $userFile2, $userFile3, $userFile4) {
        foreach ($datas as $data) {
            $this->writeFile($data, $column, $userFile1, $userFile2, $userFile3, $userFile4);
        }
    }

    private function writeFile($data, $column, $userFile1, $userFile2, $userFile3, $userFile4) {
        if ($data[$column] % 4 == 0) {
            fwrite($userFile1, serialize($data)."\r\n");
        } elseif ($data[$column] % 4 == 1) {
            fwrite($userFile2, serialize($data)."\r\n");
        } elseif ($data[$column] % 4 == 2) {
            fwrite($userFile3, serialize($data)."\r\n");
        }else {
            fwrite($userFile4, serialize($data)."\r\n");
        }
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