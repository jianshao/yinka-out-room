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


class CreateCurrentDbFileCommand extends Command
{

    private $start = 0;
    private $limit = 1000;
    protected function configure()
    {
        $this->setName('CreateCurrentDbFileCommand')
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
        $startTime = microtime(true);
        $output->writeln(sprintf('CreateCurrentDbFileCommand execute start time:%s', $startTime));
        $userFile1 = fopen(app()->getRootPath()."filediff/newdb/userMaster1/$tableName.txt",'a');
        $userFile2 = fopen(app()->getRootPath()."filediff/newdb/userMaster2/$tableName.txt",'a');
        $userFile3 = fopen(app()->getRootPath()."filediff/newdb/userMaster3/$tableName.txt",'a');
        $userFile4 = fopen(app()->getRootPath()."filediff/newdb/userMaster4/$tableName.txt",'a');
        $dbMap = Sharding::getInstance()->getDbMap('userMaster');
        if ($tableName == 'zb_member') {
            foreach ($dbMap as $dbName) {
                if ($dbName == 'userMaster1') {
                    $userFile = $userFile1;
                } elseif($dbName == 'userMaster2') {
                    $userFile = $userFile2;
                } elseif($dbName == 'userMaster3') {
                    $userFile = $userFile3;
                } else {
                    $userFile = $userFile4;
                }
                $this->start = 1;
                while (true) {
                    $where = [];
                    $where[] = ['id', '>=', $this->start]; // 1
                    $where[] = ['id', '<', $this->start + $this->limit]; // 500
                    $data = Db::connect($dbName)->table($tableName)->field('id,totalcoin,freecoin,diamond,exchange_diamond,free_diamond')->where($where)->select()->toArray();
                    if (empty($data) && $this->start >= 1000000) {
                        break;
                    }
                    $this->createFile($data, $userFile);
                    $this->start += $this->limit;
                }
            }

        } elseif ($tableName == 'zb_user_props') {
            foreach ($dbMap as $dbName) {
                if ($dbName == 'userMaster1') {
                    $userFile = $userFile1;
                } elseif($dbName == 'userMaster2') {
                    $userFile = $userFile2;
                } elseif($dbName == 'userMaster3') {
                    $userFile = $userFile3;
                } else {
                    $userFile = $userFile4;
                }
                $this->start = 0;
                while (true) {
                    $data = Db::connect($dbName)->table($tableName)->limit($this->start, $this->limit)->order(['uid'=>'asc', 'kind_id' => 'asc', 'create_time'=>'asc'])->select()->toArray();
                    if (empty($data)) {
                        break;
                    }
                    $this->createFile($data, $userFile);
                    $this->start += $this->limit;
                }
            }
        } else {
            foreach ($dbMap as $dbName) {
                if ($dbName == 'userMaster1') {
                    $userFile = $userFile1;
                } elseif($dbName == 'userMaster2') {
                    $userFile = $userFile2;
                } elseif($dbName == 'userMaster3') {
                    $userFile = $userFile3;
                } else {
                    $userFile = $userFile4;
                }
                $this->start = 0;
                while (true) {
                    $data = Db::connect($dbName)->table($tableName)->limit($this->start, $this->limit)->select()->toArray();
                    if (empty($data)) {
                        break;
                    }
                    $this->createFile($data, $userFile);
                    $this->start += $this->limit;
                }
            }
        }
        $output->writeln(sprintf('CreateCurrentDbFileCommand execute end time:%s', microtime(true) - $startTime));
    }

    private function createFile($datas, $userFile) {
        foreach ($datas as $data) {
            fwrite($userFile, serialize($data)."\r\n");
        }
    }
}