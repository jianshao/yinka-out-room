<?php
/**
 * 定时任务
 * del redis
 */

namespace app\api\script;

use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\activity\halloween\service\HalloweenService;
use app\domain\exceptions\FQException;
use app\domain\gift\dao\GiftModelDao;
use app\domain\rank\dao\RankModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\WalletService;
use app\utils\CommonUtil;
use think\console\Command;
use think\facade\Db;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;
use think\facade\Log;

ini_set('set_time_limit', 0);

class FixRankDataCommand extends Command
{


    protected function configure()
    {
        $this->setName('FixRankDataCommand')->setDescription('FixDataCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(["select" => 13]);
        $dbModelList = Sharding::getInstance()->getServiceModels('userMaster', 'zb_user_asset_log_202211');
        foreach ($dbModelList as $dbModel) {
            $data = $dbModel->where([['event_id','=','10002'], ['success_time', '>=', 1667296800],['success_time', '<', 1667448000]])->field('uid,touid,success_time,ext_4')->select();
            if (!empty($data)) {
                $data = $data->toArray();
                foreach ($data as  $info) {
                    $redis->zIncrBy(HalloweenService::getInstance()->getRichAll1RedisKey(), intval($info['ext_4']), $info['uid']);
                    $redis->zIncrBy(HalloweenService::getInstance()->getRichDay1RedisKey($info['success_time']), intval($info['ext_4']), $info['uid']);
                    $redis->zIncrBy(HalloweenService::getInstance()->getLikeAll1RedisKey(), intval($info['ext_4']), $info['touid']);
                    $redis->zIncrBy(HalloweenService::getInstance()->getLikeDay1RedisKey($info['success_time']), intval($info['ext_4']), $info['touid']);
                }
            }
        }
    }
}