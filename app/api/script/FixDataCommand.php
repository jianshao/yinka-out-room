<?php
/**
 * 定时任务
 * del redis
 */

namespace app\api\script;

use app\common\YunxinCommon;
use app\domain\gift\dao\GiftModelDao;
use app\domain\rank\dao\RankModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\WalletService;
use app\domain\user\UserRepository;
use app\utils\CommonUtil;
use think\console\Command;
use think\facade\Db;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;
use think\facade\Log;

ini_set('set_time_limit', 0);

class FixDataCommand extends Command
{


    protected function configure()
    {
        $this->setName('FixDataCommand')->setDescription('FixDataCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        //等级
//        $arr =
//            [
//                '1268679' => '1213049',
//                '1436880' => '1212616',
//                '1296193' => '1159942',
//                '1330871' => '1212167',
//                '1257298' => '1139441',
//                '1445675' => '1187472',
//                '1055750' => '1128717',
//                '1425323' => '1212152',
//                '1280794' => '1188258',
//                '1424786' => '1133042',
//                '1247860' => '1213252',
//                '1026541' => '1000032',
//                '1221254' => '1205447',
//                '1346772' => '1213487',
//                '1458597' => '1212313',
//                '1298466' => '1212461'
//            ];
//
//        foreach ($arr as $key => $value) {
//            $userInfo = Db::connect('abConnection')->table('zb_member')->where('id', $key)->field('lv_dengji,level_exp')->find();
//            if (!empty($userInfo)) {
//                UserModelDao::getInstance()->updateDatas($value, $userInfo);
//            }
//        }

        //vip
        $arr = [
            '1162220' => 327,
            '1128717' => 284,
            '1205447' => 443,
        ];
        $timestamp = time();
        foreach ($arr as $userId => $count) {
            $this->addVip($userId, $count, $timestamp);
        }
    }

    private function addVip($userId, $count, $timestamp)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        $vip = $user->getVip($timestamp);
        $vip->addVip($count, $timestamp);
    }
}