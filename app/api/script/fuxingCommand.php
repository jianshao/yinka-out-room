<?php
/**
 * 定时任务
 * 每日福星榜送头像框
 */

namespace app\api\script;

use app\core\mysql\Sharding;
use app\domain\bi\BIReport;
use app\domain\user\UserRepository;
use think\console\Command;
use think\facade\Log;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;


ini_set('set_time_limit', 0);

class fuxingCommand extends Command
{


    protected function configure()
    {
        $this->setName('fuxingCommand')->setDescription('fuxingCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $time = time();
        $todayTime = strtotime(date("Y-m-d 00:00:00"));
        $one = 86400;
        $today = date('Ymd');
        $yest = date("Ymd",strtotime("-1 day"));
        $redis = RedisCommon::getInstance()->getRedis();
        $listUser = $redis->zRevRange('rank_box_fuxing_'.$yest,0,2,true);
        if (!empty($listUser)) {
            $uids = array_keys($listUser);
            foreach ($uids as $k => $v) {
                switch ($k) {
                    case 0:
                        $kindId = 97;
                        break;
                    case 1:
                        $kindId = 98;
                        break;
                    case 2:
                        $kindId = 99;
                        break;
                    default:
                        $kindId = 0;
                        break;
                }
                $this->addProp($k, $v, $time, $kindId);
            }
        }

    }

    public function addProp($k, $v, $time, $kindId) {
        try {
            $flag = Sharding::getInstance()->getConnectModel('userMaster', $v)->transaction(function () use($k, $v, $time, $kindId) {
                $user = UserRepository::getInstance()->loadUser($v);
                if ($user == null) {
                    return false;
                }
                $propBag = $user->getAssets()->getPropBag($time);
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'fuxing',$k+1,1);
                $propBag->addPropByUnit($kindId,1, $time, $biEvent);
                return true;
            });

        } catch (\Exception $e) {
            $flag = false;
            Log::record("code ---".$e->getCode(), "message---".$e->getMessage(), "trace---".$e->getTraceAsString());
        }
        return $flag;
    }


}