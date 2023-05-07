<?php


namespace app\event\handler;


use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\domain\asset\AssetKindIds;
use app\domain\duke\DukeSystem;
use app\domain\duke\service\DukeService;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\service\RoomBlackService;
use app\domain\user\dao\UserModelDao;
use app\service\RoomNotifyService;
use Exception;
use think\facade\Log;

class DukeHandler
{
    // 用户登录，处理爵位信息
    public function onUserLoginEvent($event) {
        try {
            Log::info(sprintf('DukeHandler::onUserLoginEvent userId=%d',
                $event->userId));
            DukeService::getInstance()->processDukeWhenUserLogin($event->userId, $event->lastLoginTime, $event->timestamp);
        } catch (Exception $e) {
            Log::error(sprintf('DukeHandler::onUserLoginEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    // 用户送礼增加爵位经验值
    public function onSendGiftEvent($event) {
        try {
            $dukeValue = $event->calcSenderConsumeCountSpec(AssetKindIds::$BEAN);
            if ($dukeValue > 0) {
                DukeService::getInstance()->addDukeValue($event->fromUserId, $dukeValue, $event->roomId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('DukeHandler::onSendGiftEvent userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * @param $event BreakBoxEvent
     */
    public function onBreakBoxEvent($event){
        try{
            Log::info(sprintf('DukeHandler::onBreakBoxEvent userId=%d  boxId=%s', $event->userId, $event->boxId));
            $dukeValue = 0;
            foreach ($event->consumeAssetList as $consumeAsset) {
                if ($consumeAsset->assetId == AssetKindIds::$BEAN) {
                    $dukeValue += $consumeAsset->count;
                }
            }

            if ($dukeValue > 0) {
                DukeService::getInstance()->addDukeValue($event->userId, $dukeValue, $event->roomId);
            }
        }catch (Exception $e) {
            Log::error(sprintf('DukeHandler::onBreakBoxEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @param $event BuyGoodsEvent
     */
    public function onBuyGoodsEvent($event){
        try {
            $dukeValue = 0;
            if ($event->consumeAsset->assetId == AssetKindIds::$BEAN) {
                $dukeValue += $event->consumeAsset->count;
            }

            Log::info(sprintf('DukeHandler::onBuyGoodsEvent userId=%d goodsId=%d dukeValue=%d', $event->userId, $event->goodsId, $dukeValue));

            if ($dukeValue > 0) {
                DukeService::getInstance()->addDukeValue($event->userId, $dukeValue, $event->roomId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('DukeHandler::onBuyGoodsEvent $userId=%d goodsId=%d ex=%d:%s file=%s:%d',
                $event->userId, $event->goodsId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    private function setDukeCartoon($userId, $dukeLevel) {
        $redisKey = 'duke_cartoon';
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HSET($redisKey, $userId, $dukeLevel);
    }

    public function onDukeLevelChangeEvent($event) {
        try {
            Log::info(sprintf('DukeHandler::onDukeLevelChangeEvent userId=%d oldDukeLevel=%d newDukeLevel=%d',
                $event->userId, $event->oldDukeLevel, $event->newDukeLevel));
            $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
            if ($event->roomId != 0) {
                $notifyType = in_array($event->newDukeLevel, [1, 2, 3]) ? 1 : 2;
                RoomNotifyService::getInstance()->notifyDukeLevelChange($event->userId, $userModel, $event->roomId, $event->newDukeLevel, $notifyType);
            } else {
                $this->setDukeCartoon($event->userId, $event->newDukeLevel);
            }

            RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
//            if ($event->newDukeLevel > $event->oldDukeLevel) {
//                $this->sendDukeLevelChangedYunxin($event->userId, $userModel, $event->newDukeLevel);
//            }
        } catch (Exception $e) {
            Log::error(sprintf('DukeHandler::onDukeLevelChangeEvent userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function sendDukeLevelChangedYunxin($userId, $userModel, $dukeLevel) {
        $dukeLevelConfig = DukeSystem::getInstance()->findDukeLevel($dukeLevel);
        if ($dukeLevelConfig) {
            $msg = ["msg" => "您的贵族身份已升为" . $dukeLevelConfig->name . "，解锁多项新特权，请前往【爵位中心】查看详情"];
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
            Log::info(sprintf('DukeHandler::sendDukeLevelChangedYunxin userId=%d dukeLevel=%d resMsg=%s',
                $userId, $dukeLevel, $resMsg));
            if ($dukeLevel == 5) {
                $msg = ["msg" => "尊敬的" . $userModel->nickname . "，恭喜您成为尊贵的国王身份。您的专属客服将竭诚为您服务，您可添加客服微信fanqievip001。祝您玩的愉快！"];
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
                Log::info(sprintf('DukeHandler::sendDukeLevelChangedYunxin userId=%d dukeLevel=%d resMsg=%s',
                    $userId, $dukeLevel, $resMsg));
            }
        }
    }
}