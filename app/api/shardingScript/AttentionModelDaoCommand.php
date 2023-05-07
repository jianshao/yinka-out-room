<?php

namespace app\api\shardingScript;

use app\common\RedisCommon;
use app\domain\user\dao\AttentionModelDao;
use app\domain\user\dao\FansModelDao;
use app\domain\user\dao\FriendModelDao;
use app\utils\TimeUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  zb_attention分库
 */
class AttentionModelDaoCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('AttentionModelDaoCommand')->setDescription('AttentionModelDaoCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $offset = $redis->get("test_attention_fenku_offset");
        $offset = empty($offset) ? 0 : intval($offset);
        $this->doExecute($offset, 500);
    }

    private function doExecute($offset, $count){
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = Db::connect($this->baseDb)->table('zb_attention')->limit($offset, $count)->select()->toArray();
        if (!empty($datas)){
            # 存数据
            foreach ($datas as $data){
                if ($data['type'] == 0){
                    $this->saveData($data);
                }
            }

            $redis->set("test_attention_fenku_offset", $offset);

            $offset += $count;
            $this->doExecute($offset, $count);
        }
    }

    private function saveData($data){
        try {
            $userId = $data['userid'];
            $userided = $data['userided'];
            $isRead = $data['status'];
            $createTime = TimeUtil::strToTime($data['attention_time']);

            # userId关注表
            AttentionModelDao::getInstance()->addAttention($userId, $userided, $createTime);
            # userided粉丝表
            FansModelDao::getInstance()->addFans($userided, $userId, $createTime, $isRead);
            if ($data['friend_status'] == 1){
                # userId好友表
                FriendModelDao::getInstance()->addFriend($userId, $userided, $createTime);
            }

        }catch (\Exception $e) {
            Log::error(sprintf("AttentionModelDaoCommand saveData error data:%s", json_encode($data)));
        }
    }
}
