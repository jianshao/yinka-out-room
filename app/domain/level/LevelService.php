<?php


namespace app\domain\level;


use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\bi\BIReport;
use app\domain\events\LevelChangeDomainEvent;
use app\domain\exceptions\FQException;
use app\domain\level\dao\LevelModelDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\User;
use app\domain\user\UserRepository;
use app\event\LevelChangeEvent;
use think\facade\Log;
use Exception;

class LevelService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LevelService();
        }
        return self::$instance;
    }

    public function onLevelUpdatEvent($userId, $count){
        list($oldLevel, $newLevel) = $this->onLevelChange($userId, $count);

        Log::info(sprintf('LevelService::onLevelUpdatEvent userId=%d count=%d oldLevel=%d newLevel=%d',
            $userId, $count, $oldLevel, $newLevel));

        if ($newLevel != $oldLevel) {
            #升级了 检查该奖励是否是特权等级 发消息
            $privileges = LevelSystem::getInstance()->getPrivilegesByLevel($oldLevel, $newLevel);
            foreach ($privileges as $privilege){
                $this->sendLevelUpdateMsg($userId, $privilege->rewardMsg);
            }

            event(new LevelChangeEvent($userId, $oldLevel, $newLevel, time()));
        }
    }


    public function onLevelChange($userId, $count){
        assert($count > 0);
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $count) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                list($oldLevel, $newLevel) = $this->levelChangeImpl($user, $count);
                return array($oldLevel, $newLevel);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 消耗番茄豆引起等级变化
     * @param $user User
     * @param $count int 消耗番茄豆的数量
     */
    private function levelChangeImpl($user, $count){
        $timestamp = time();
        $userId = $user->getUserId();
        $levelModel = LevelModelDao::getInstance()->loadLevel($userId);

        $exp = $count * $this->speedUp($levelModel->vipLevel);
        $levelModel->levelExp += $exp;

        $nowLevel = $levelModel->level;
        $newLevel = LevelSystem::getInstance()->getLevelByExp($levelModel->levelExp);
        if($newLevel > $nowLevel){

            #升级了 检查该奖励是否是特权等级 发奖励
            $privileges = LevelSystem::getInstance()->getPrivilegesByLevel($nowLevel, $newLevel);
            foreach ($privileges as $privilege){
                if($privilege->reward != null){
                    $biEvent = BIReport::getInstance()->makePrivilegeLevelBIEvent($nowLevel, $newLevel);
                    foreach ($privilege->reward as $reward){
                        $rewardItem = $reward->getItem();
                        $user->getAssets()->add($rewardItem->assetId, $rewardItem->count, $timestamp, $biEvent);
                    }
                }
            }

            $levelModel->level = $newLevel;

            $redis = RedisCommon::getInstance()->getRedis();
            $redis->hset('user_info_'.$userId, 'lv_dengji', $levelModel->level);
        }

        LevelModelDao::getInstance()->saveLevel($userId, $levelModel);
        Log::info(sprintf('LevelService levelChangeImpl userId=%d oldLevel=%d Level=%d addExp=%d, totalExp=%d',
            $userId, $nowLevel, $newLevel, $exp, $levelModel->levelExp));

        return array($nowLevel, $newLevel);
    }

    //发送用户等级变更消息
    private function sendLevelUpdateMsg($userId, $msg)
    {
        if(!$msg){
            return;
        }

        $msg = ["msg" => $msg];
        //queue YunXinMsg
        $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
        Log::info(sprintf("LevelService sendLevelUpdateMsg result userId=%d resMsg=%s", $userId, $resMsg));
    }

    private function speedUp($vipLevel)
    {
        //svip消费1.05倍等级加速
        return $vipLevel == 2 ? 1.05 : 1;
    }

}