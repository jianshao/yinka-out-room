<?php


namespace app\api\script;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\SnsTypes;
use app\domain\user\dao\AccountMapDao;
use app\query\user\cache\CachePrefix;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class FixExportZbMemberCommand extends Command
{

    protected function configure()
    {
        $this->setName('FixExportZbMemberCommand')->setDescription('FixExportZbMemberCommand');
    }

    public function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);

        $where = [];
        $where[] = ['appleid', '<>', ''];
        $where[] = ['cancel_user_status', '<>', 1];
        $datas = Db::connect("userMaster1")->table('zb_member')->field('id, appleid')->where($where)->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data){
                $userId = $data['id'];
                $appleid = $data['appleid'];
                if (!empty($appleid)){
                    $snsId = $appleid;
                    $uId1 = AccountMapDao::getInstance()->getUserIdBySnsType(SnsTypes::$APPLEID, $snsId);
                    if (!empty($uId1)){
                        $redis->hset('user_appleid_error_set1', $uId1, $snsId);
                        continue;
                    }

                    $uId2 = AccountMapDao::getInstance()->getUserIdBySnsType(SnsTypes::$WXOPENID, $snsId);
                    if (!empty($uId2)){
                        $redis->hset('user_appleid_error_set2', $uId2, $snsId);
                    }

                    AccountMapDao::getInstance()->addBySnsType(SnsTypes::$APPLEID, $snsId, $userId);
//                    AccountMapDao::getInstance()->getModel()->where(['user_id' => $userId, 'type' => SnsTypes::$WXOPENID, 'value'=>$snsId])->delete();
                }
            }
        }
    }
}