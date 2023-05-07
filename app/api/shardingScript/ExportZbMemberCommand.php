<?php


namespace app\api\shardingScript;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\SnsTypes;
use app\query\user\cache\CachePrefix;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class ExportZbMemberCommand extends BaseCommand
{
    private $lastId = 0;
    private $limit = 2000;

    protected function configure()
    {
        $this->setName('ExportZbMemberCommand')->setDescription('ExportZbMemberCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $redis->del('user_info_nickname_warning_set');
        $redis->del('user_info_nickname_set');
        $redis->del('user_info_pretty_set');
        $redis->del('user_info_pretty_warning_set');
        $redis->del('user_account_username_set');
        $redis->del('user_account_username_warning_set');
        $redis->del('user_account_wxopenid_set');
        $redis->del('user_account_wxopenid_warning_set');
        $redis->del('user_account_qopenId_set');
        $redis->del('user_account_qopenId_warning_set');
        $redis->del('user_account_appleid_set');
        $redis->del('user_account_appleid_warning_set');
        $redis->del('user_info_userId_set');
        $redis->del('user_info_userId_warning_set');

        $infofp = fopen(app()->getBasePath().'core/database/user/info/insert.sql','a');
//        $fileName = app()->getBasePath().'core/database/user/info/insert.sql';
//        if (file_exists($fileName)){
//            unlink($fileName);
//        }
        fwrite($infofp, "SET NAMES utf8mb4;"."\r\n");

        $memberfp = fopen(app()->getBasePath().'core/database/user/member/insert.sql','a');
//        $fileName = app()->getBasePath().'core/database/user/member/insert.sql';
//        if (file_exists($fileName)){
//            unlink($fileName);
//        }
        fwrite($memberfp, "SET NAMES utf8mb4;"."\r\n");

        $accountfp = fopen(app()->getBasePath().'core/database/user/account/insert.sql','a');
//        $fileName = app()->getBasePath().'core/database/user/account/insert.sql';
//        if (file_exists($fileName)){
//            unlink($fileName);
//        }
        fwrite($accountfp, "SET NAMES utf8mb4;"."\r\n");

        $output->writeln(sprintf('app\command\ExportZbMemberCommand execute start date:%s', TimeUtil::timeToStr(time())));
        $this->doExecute($redis, $memberfp, $infofp, $accountfp);
        $output->writeln(sprintf('app\command\ExportZbMemberCommand execute end date:%s', TimeUtil::timeToStr(time())));
    }

    public function doExecute($redis, $memberfp, $infofp, $accountfp){
        for ($number = 1; $number <= 10000; $number++) {
            $where= [];
            $where[] = ['id', '>', $this->lastId];
//            $where[] = ['id', '=', 2940922];
//            $where[] = ['cancel_user_status', '<>', 1];
            $datas = Db::connect($this->baseDb)->table('zb_member')->where($where)->limit($this->limit)->order('id asc')->select()->toArray();
            if (!empty($datas)) {
                foreach ($datas as $data){
                    $this->createUserSql($data, $redis, $memberfp, $infofp);
                    if ($data['cancel_user_status'] != 1) {
                        $this->createAccountSql($data, $redis, $accountfp);
                    }
                    $this->lastId = $data['id'];
                }
            } else {
                break;
            }
        }
    }

    public function createUserSql($data, \Redis $redis, $memberfp, $infofp) {
        try {
            foreach ($data as $k => $v){
                if (!empty($v) && is_string($v)){
                    $data[$k] = addslashes($data[$k]);
                }
            }

            $userId = $data['id'];
            $prettyId = $data['pretty_id'];
            $nickname = trim($data['nickname']);
            $database = Sharding::getInstance()->getDbName('commonMaster', $userId);
            $databaseName = config("database.connections.$database.database");
            if ($data['cancel_user_status'] != 1) {
                if ($redis->zScore('user_info_pretty_set', $prettyId) || empty($prettyId)) {
                    $redis->hset('user_info_pretty_warning_set', $userId, $prettyId);
                    $prettyId = $userId;
                }
                $redis->zAdd('user_info_pretty_set', $userId, $prettyId);
                $prettySql = "insert into `$databaseName`."."`zb_user_info_map` (`user_id`, `type`, `value`) values ($userId, 'pretty', "."'$prettyId'".");";

                if ($redis->zScore('user_info_nickname_set', $nickname)  || empty($nickname)) {
                    $redis->hset('user_info_nickname_warning_set', $userId, $nickname);
                    $nickname = "用户_".$userId;
                }
                $redis->zAdd('user_info_nickname_set', $userId, $nickname);
//            $nickname1 = addslashes($nickname);
                $nicknameSql = "insert into `$databaseName`."."`zb_user_info_map` (`user_id`, `type`, `value`) values ($userId, 'nickname', "."'$nickname'".");";

                if ($prettySql) {
                    fwrite($infofp, $prettySql."\r\n");
                }
                if ($nicknameSql) {
                    fwrite($infofp, $nicknameSql."\r\n");
                }
            }

//            # 插入用户数据
//            if ($redis->sIsMember('user_info_userId_set', $userId)){
//                $redis->sAdd('user_info_userId_warning_set', $userId);
//            }else{
//                $redis->sAdd('user_info_userId_set', $userId);
//                $data['pretty_id'] = $prettyId;
//                $data['nickname'] = $nickname;
////                $data['intro'] = addslashes($data['intro']);
//                $database = Sharding::getInstance()->getDbName('userMaster', $userId);
//                $databaseName = config("database.connections.$database.database");
//                $arr_keys = array_keys($data);
//                $arr_values = array_values($data);
//                $sql = "insert into `$databaseName`."."zb_member (" . implode(',' ,$arr_keys) . ") values";
//                $sql .= " ('" . implode("','" ,$arr_values) . "');";
//
//                fwrite($memberfp, $sql."\r\n");
//
//                $redis = RedisCommon::getInstance()->getRedis();
//                $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
//            }

        }catch (\Exception $e) {
            Log::error(sprintf("ExportZbMemberCommand saveData error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }

    public function createAccountSql($data, \Redis $redis, $fp) {
        try {
            $qopenidSql = '';
            $wxopenidSql = '';
            $appleidSql = '';
            $mobileSql = '';
            $aliyunSql = '';
            $getuiSql = '';
            $userId = $data['id'];
            $qopenid = $data['qopenid'];
            $wxopenid = $data['wxopenid'];
            $appleid = $data['appleid'];
            $username = $data['username'];
            $database = Sharding::getInstance()->getDbName('commonMaster', $userId);
            $databaseName = config("database.connections.$database.database");

            if (!empty($qopenid)){
                $snsType = strval(SnsTypes::$QOPENID);
                $snsId = $qopenid;
                if ($redis->zScore('user_account_qopenId_set', $snsId)) {
                    $redis->hset('user_account_qopenId_warning_set', $userId, $snsId);
                }else{
                    $redis->zAdd('user_account_qopenId_set', $userId, $snsId);
                    $qopenidSql = "insert into `$databaseName`."."`zb_user_account_map` (`user_id`, `type`, `value`) values ($userId,  "."'$snsType'".", "."'$snsId'".");";
                }
            }

            if (!empty($wxopenid)){
                $snsType = strval(SnsTypes::$WXOPENID);
                $snsId = $wxopenid;
                if ($redis->zScore('user_account_wxopenid_set', $snsId)) {
                    $redis->hset('user_account_wxopenid_warning_set', $userId, $snsId);
                }else{
                    $redis->zAdd('user_account_wxopenid_set', $userId, $snsId);
                    $wxopenidSql = "insert into `$databaseName`."."`zb_user_account_map` (`user_id`, `type`, `value`) values ($userId, "."'$snsType'".", "."'$snsId'".");";
                }
            }

            if (!empty($appleid)){
                $snsType = strval(SnsTypes::$APPLEID);
                $snsId = $appleid;
                if ($redis->zScore('user_account_appleid_set', $snsId)) {
                    $redis->hset('user_account_appleid_warning_set', $userId, $snsId);
                }else{
                    $redis->zAdd('user_account_appleid_set', $userId, $snsId);
                    $appleidSql = "insert into `$databaseName`."."`zb_user_account_map` (`user_id`, `type`, `value`) values ($userId, "."'$snsType'".", "."'$snsId'".");";
                }
            }

            if (!empty($username)){
                $snsId = $username;
                if ($redis->zScore('user_account_username_set', $snsId)) {
                    $redis->hset('user_account_username_warning_set', $userId, $snsId);
                }else{
                    $redis->zAdd('user_account_username_set', $userId, $snsId);

                    $snsType = 'mobile';
                    $mobileSql = "insert into `$databaseName`."."`zb_user_account_map` (`user_id`, `type`, `value`) values ($userId, "."'$snsType'".", "."'$snsId'".");";

                }
            }

            if ($qopenidSql) {
                fwrite($fp, $qopenidSql."\r\n");
            }
            if ($wxopenidSql) {
                fwrite($fp, $wxopenidSql."\r\n");
            }

            if ($appleidSql) {
                fwrite($fp, $appleidSql."\r\n");
            }
            if ($mobileSql) {
                fwrite($fp, $mobileSql."\r\n");
            }

            if ($aliyunSql) {
                fwrite($fp, $aliyunSql."\r\n");
            }
            if ($getuiSql) {
                fwrite($fp, $getuiSql."\r\n");
            }

        }catch (\Exception $e) {
            Log::error(sprintf("ExportZbMemberCommand saveData error data:%s,strace:%s", json_encode($data), $e->getTraceAsString()));
        }
    }
}