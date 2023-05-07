<?php
/**
 * 定时任务
 * 踢出与禁言用户自动卸载
 */

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\room\dao\RoomBlackModelDao;
use app\domain\room\model\RoomBlackModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class RoomBlackCommand extends Command
{

    protected function configure()
    {
        $this->setName('RoomBlackCommand')->setDescription('RoomBlackCommand');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return bool
     * @throws \app\domain\exceptions\FQException
     */
    protected function execute(Input $input, Output $output)
    {
        $roomBlackModelList = RoomBlackModelDao::getInstance()->loadAllTempModelList();
        $redis = RedisCommon::getInstance()->getRedis();
        if (empty($roomBlackModelList)) {
            return false;
        }
        $unixTime = time();
        foreach ($roomBlackModelList as $key => $roomBlackModel) {
            $tmp = $roomBlackModel->diffForTime($unixTime);
//            没到期 continue
            if ($tmp > 0) {
                continue;
            }
            if ($roomBlackModel->type == 2) {
                /**
                 * @info 禁言
                 */
//                    通知py服务
                $this->noticeService($roomBlackModel);
                //拉黑
                RoomBlackModelDao::getInstance()->removeForRoomUserType($roomBlackModel->roomId, $roomBlackModel->userId, $roomBlackModel->type);
                //缓存禁言
                $this->cleanBlackCache($roomBlackModel, $redis);
            } else {
                /**
                 * @info 拉黑
                 */
                RoomBlackModelDao::getInstance()->removeForRoomUserType($roomBlackModel->roomId, $roomBlackModel->userId, $roomBlackModel->type);
            }
        }
        return true;
    }

    /**
     * @param RoomBlackModel $roomBlackModel
     */
    private function noticeService(RoomBlackModel $roomBlackModel)
    {
        $str = ['roomId' => (int)$roomBlackModel->roomId, 'duration' => ['longtime'], 'toUserId' => (string)$roomBlackModel->userId, 'isDisabled' => (int)$roomBlackModel->type];
        $socket_url = config('config.socket_url_base') . 'iapi/disableMsg';
        $msgData = json_encode($str);
        $resmsg = curlData($socket_url, $msgData, 'POST', 'json');
        Log::record("禁言消息参数-----" . $msgData, "info");
        Log::record("禁言消息发送-----" . $resmsg, "info");
    }


    /**
     * @param $id
     * @param $userId
     * @return string
     */
    private function getBlackKey($id, $userId)
    {
        return sprintf('room_user_disable_msg_%d_%d', $id, $userId);
    }

    /**
     * @param RoomBlackModel $roomBlackModel
     * @param $redis
     */
    private function cleanBlackCache(RoomBlackModel $roomBlackModel, $redis)
    {
        $black_key = $this->getBlackKey($roomBlackModel->id, $roomBlackModel->userId);
        $redis->del($black_key);
    }

}