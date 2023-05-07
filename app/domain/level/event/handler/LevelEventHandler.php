<?php


namespace app\domain\level\event\handler;


use app\common\RedisCommon;
use app\domain\activity\luckStar\LuckStarService;
use app\domain\asset\AssetKindIds;
use app\domain\level\dao\LevelModelDao;
use app\domain\level\LevelService;
use app\domain\level\LevelSystem;
use app\event\BreakBoxEvent;
use app\event\BuyGoodsEvent;
use Exception;
use think\facade\Log;

class LevelEventHandler
{
    /**
     * @param $event BuyGoodsEvent
     */
    public function onBuyGoodsEvent($event){
        try {
            Log::info(sprintf('LevelHandler::onBuyGoodsEvent userId=%d goodsId=%d', $event->userId, $event->goodsId));
            $count = 0;
            if ($event->consumeAsset->assetId == AssetKindIds::$BEAN) {
                $count += $event->consumeAsset->count;
            }
            if ($count > 0) {
                LevelService::getInstance()->onLevelUpdatEvent($event->userId, $count);
            }

        } catch (Exception $e) {
            Log::error(sprintf('LevelHandler::onBuyGoodsEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    // 用户送礼增加等级经验值
    public function onSendGiftEvent($event) {
        try{
            $beanValue = $event->calcSenderConsumeCountSpec(AssetKindIds::$BEAN);// $this->calcBeanValueByGiftDetails($event->sendDetails);
            if ($beanValue > 0) {
                LevelService::getInstance()->onLevelUpdatEvent($event->fromUserId, $beanValue);
            }
        }catch (Exception $e) {
            Log::error(sprintf('LevelHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }

    /**
     * @param $event BreakBoxEvent
     */
    public function onBreakBoxEvent($event){
        try{
            Log::info(sprintf('LevelHandler::onBreakBoxEvent userId=%d  boxId=%s', $event->userId, $event->boxId));
            $count = 0;
            foreach ($event->consumeAssetList as $consumeAsset) {
                if ($consumeAsset->assetId == AssetKindIds::$BEAN) {
                    $count += $consumeAsset->count;
                }
            }

            if($count > 0){
                LevelService::getInstance()->onLevelUpdatEvent($event->userId, $count);
            }

//            //new add 瓜分番茄豆活动
//            $redis = RedisCommon::getInstance()->getRedis();
//            $luckStarConfig = $redis->hGetAll('luck_star_config');
//            if (!empty($luckStarConfig)) {
//                if($event->timestamp >= strtotime($luckStarConfig['start_time']) && $event->timestamp <= strtotime($luckStarConfig['end_time'])) {
//                    LuckStarService::getInstance()->luckyStarComes($event->boxId, $event->count, $luckStarConfig);
//                }
//            }
        }catch (Exception $e) {
            Log::error(sprintf('LevelHandler::onBreakBoxEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function onUserLoginDomainEvent($event) {
        try {
            Log::debug(sprintf('LevelEventHandler onUserLoginDomainEvent $userId=%d', $event->user->getUserId()));

            $userId = $event->user->getUserId();
            $levelModel = LevelModelDao::getInstance()->loadLevel($userId);
            if ($levelModel->levelExp <= 300){
                $newLevel = LevelSystem::getInstance()->getLevelByExp($levelModel->levelExp);
                $levelModel->level = $newLevel;
                LevelModelDao::getInstance()->saveLevel($userId, $levelModel);

                $redis = RedisCommon::getInstance()->getRedis();
                $redis->hset('user_info_'.$userId, 'lv_dengji', $levelModel->level);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('LevelEventHandler::onUserLoginDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }
}