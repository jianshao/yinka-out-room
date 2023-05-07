<?php

namespace app\api\shardingScript;

use app\common\RedisCommon;
use app\domain\SnsTypes;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\UserInfoMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\UserModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  zb_member分库
 */
class UserModelDaoCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('UserModelDaoCommand')->setDescription('UserModelDaoCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $offset = $redis->get("test_user_fenku_offset");
        $offset = empty($offset) ? 0 : intval($offset);
        $this->doExecute($offset, 500);
    }

    private function doExecute($offset, $count){
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = Db::connect($this->baseDb)->table('zb_member')->limit($offset, $count)->select()->toArray();
        if (!empty($datas)){
            # 存数据
            foreach ($datas as $data){
                $model = UserModelDao::getInstance()->dataToModel($data);

                $this->saveUser($model);

                if ($model->cancelStatus != 1){
                    $this->saveAccount($model);
                }

                Log::info(sprintf("UserModelDaoCommand doExecut userId=%s cancelStatus=%s", $model->userId, $model->cancelStatus));
            }

            $redis->set("test_user_fenku_offset", $offset);

            $offset += $count;
            $this->doExecute($offset, $count);
        }
    }

    private function saveUser($model){
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);
        $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId($model->prettyId);
        if(!empty($userId) && $userId != $model->userId){
            Log::info(sprintf("UserModelDaoCommand saveUser prettyId repeat userId:prettyId=%s:%s otherUserId:%s", $model->userId, $model->prettyId, $userId));

            $model->prettyId = $model->userId;
        }

        $userId = UserInfoMapDao::getInstance()->getUserIdByNickname($model->nickname);
        if(!empty($userId) && $userId != $model->userId){
            Log::info(sprintf("UserModelDaoCommand saveUser nickname repeat userId:nickname=%s:%s otherUserId:%s", $model->userId, $model->nickname, $userId));

            $model->nickname = "用户".$model->userId;
        }

        try {
            UserInfoMapDao::getInstance()->addByPretty($model->prettyId, $model->userId);
        }catch (\Exception $e){
            $redis->sAdd("test_user_fenku_pretty", $model->userId);
        }

        try {
            UserInfoMapDao::getInstance()->addByNickname($model->nickname, $model->userId);
        }catch (\Exception $e){
            $redis->sAdd("test_user_fenku_nickname", $model->userId);
        }
//      UserModelDao::getInstance()->saveUserModel($model);
    }

    private function saveAccount(UserModel $model){
        $redis = RedisCommon::getInstance()->getRedis(['select' => 10]);

        try {
            if ($model->qopenid){
                $snsType = SnsTypes::$QOPENID;
                $snsId = $model->qopenid;
                AccountMapDao::getInstance()->addBySnsType($snsType, $snsId, $model->userId);
            }
        }catch (\Exception $e){
            $redis->sAdd("test_user_fenku_qopenId", $model->userId);
        }

        try {
            if ($model->wxopenid){
                $snsType = SnsTypes::$WXOPENID;
                $snsId = $model->wxopenid;
                AccountMapDao::getInstance()->addBySnsType($snsType, $snsId, $model->userId);
            }
        }catch (\Exception $e){
            $redis->sAdd("test_user_fenku_wxopenId", $model->userId);
        }

        try {
            if ($model->appleid) {
                $snsType = SnsTypes::$APPLEID;
                $snsId = $model->appleid;
                AccountMapDao::getInstance()->addBySnsType($snsType, $snsId, $model->userId);
            }
        }catch (\Exception $e){
            $redis->sAdd("test_user_fenku_appleid", $model->userId);
        }

        if ($model->username){
            try {
                AccountMapDao::getInstance()->addByMobile($model->username, $model->userId);
            }catch (\Exception $e){
                $redis->sAdd("test_user_fenku_mobile", $model->userId);
            }
        }
    }
}
