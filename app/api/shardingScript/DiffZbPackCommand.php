<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class DiffZbPackCommand extends BaseCommand
{
    private $offset = 0;
    private $start = 0;
    private $limit = 2000;
    protected function configure()
    {
        $this->setName('DiffZbPackCommand')->setDescription('DiffZbPackCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $fileOld = fopen(app()->getBasePath().'core/database/user/pack/diffOld.txt','a');
        fwrite($fileOld, "userId    giftId    packNum"."\r\n");
        $fileNew = fopen(app()->getBasePath().'core/database/user/pack/diffNew.txt','a');
        fwrite($fileNew, "userId    giftId    packNum"."\r\n");
        $this->doExecute($fileOld, 'dbOld');
        $this->doExecute($fileNew, 'dbNew');
    }

    public function doExecute($file, $dbName){
//        $lastMember = Db::connect($dbName)->table('zb_member')->order('id desc')->limit(1)->find();
//        $lastUserId = $lastMember['id'];
//        for ($i = 1000001; $i <= $lastUserId; $i++) {
//            $datas = Db::connect($dbName)->table('zb_pack')->where(['user_id' => $i])->select()->toArray();
//            if (!empty($datas)) {
//                foreach ($datas as $data) {
//                    $this->createFile($data, $file);
//                }
//            }
//        }
        for ($number = 1; $number <= 10000; $number++) {
            $datas = Db::connect($dbName)->table('zb_pack')->limit($this->offset + $this->start, $this->limit)->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
                    $this->createFile($data, $file);
                }
                $this->start = $this->offset + ($number * $this->limit);
            } else {
                $this->start = 0;
                break;
            }
        }
    }

    public function createFile($data, $file) {
        try {
            $txt = "$data[user_id]    $data[gift_id]    $data[pack_num]";
            fwrite($file, $txt."\r\n");
        }catch (\Exception $e) {
            Log::error(sprintf("DiffZbPackCommand createFile error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}