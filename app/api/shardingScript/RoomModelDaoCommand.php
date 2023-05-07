<?php

namespace app\api\shardingScript;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\model\RoomModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  zb_languageroom 分库
 */
class RoomModelDaoCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('RoomModelDaoCommand')->setDescription('RoomModelDaoCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $offset = $redis->get("test_room_fenku_offset");
        $offset = empty($offset) ? 0 : intval($offset);
        $this->doExecute($offset, 500);
//        $this->doExecute($offset, 3);
    }

    private function doExecute($offset, $count)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = Db::connect($this->baseDb)->table('zb_languageroom')->limit($offset, $count)->select()->toArray();
        if (!empty($datas)) {
            # 存数据
            foreach ($datas as $data) {
                $model = RoomModelDao::getInstance()->dataToModel($data);
                $this->saveMap($model);
                $this->saveRoom($model);
                Log::info(sprintf("UserModelDaoCommand doExecut roomId=%s ", $model->roomId));
            }
            $redis->set("test_room_fenku_offset", $offset);
            $offset += $count;
            $this->doExecute($offset, $count);
        }

        return true;
    }

    private function saveMap(RoomModel $model)
    {
        try {
            Sharding::getInstance()->getConnectModel('commonMaster',0)->transaction(function() use($model){
                RoomModelDao::getInstance()->saveRoomModel($model);
            });
        } catch (\Exception $e) {
            Log::error(sprintf("UserModelDaoCommand saveUser error user:%s,strace:%s", json_encode($model), $e->getTraceAsString()));
        }
    }

    private function saveRoom($model) {
        try {
            RoomModelDao::getInstance()->createRoom($model);
        } catch (\Exception $e) {
            Log::error(sprintf("UserModelDaoCommand saveUser error user:%s,strace:%s", json_encode($model), $e->getTraceAsString()));
        }
    }
}
