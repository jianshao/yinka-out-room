<?php

namespace app\event\handler;
use app\domain\asset\AssetKindIds;
use app\domain\pay\ProductSystem;
use app\domain\sensors\service\SensorsService;
use app\domain\sensors\SensorsTypes;
use app\domain\sensors\service\SensorsUserService;
use app\domain\user\model\MemberDetailAuditActionModel;
use think\facade\Log;
use Exception;


class SensorsDataHandler
{

    /**
     * 领取任务奖励成功后
     * @param $event
     */
    public function onGetTaskRewardEvent($event)
    {
        try {
            if($event->rewardItems != null){
                foreach($event->rewardItems as $reward){
                    if($reward->assetId == AssetKindIds::$COIN){
                        SensorsService::getInstance()->getTaskRewards($event->userId,$reward->count,SensorsTypes::$GET_TASK,$event->timestamp);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onGetTaskRewardEvent Exception userId=%d ex=%d:%s trace=%s',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * 音豆兑换金币成功后
     * @param $event
     */
    public function onBeanExchangeCoinDomainEvent($event)
    {
        try {
            SensorsService::getInstance()->beanExchangeCoin($event);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onBeanExchangeCoinDomainEvent Exception userId=%d ex=%d:%s trace=%s',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * 房间内送礼成功后
     * @param $event
     */
    public function onSendGiftEvent($event)
    {
        try{
            $roomId = $event->roomId;
            $fromUid = $event->fromUserId;
            $fromUserBeanBalance = $event->fromUserBeanBalance;
            $receiverUserDiamondBalance = $event->receiverUserDiamondBalance;
            SensorsService::getInstance()->sendGift($fromUid,$fromUserBeanBalance,$receiverUserDiamondBalance,$event->giftKind,$roomId);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 扭蛋机消耗金币成功后
     * @param $event
     */
    public function onDoLotteryEvent($event)
    {
        try{
            $userId = $event->userId;
            $totalPrice = $event->totalPrice;
            $balance = $event->balance;
            if($totalPrice > 0){
                SensorsService::getInstance()->doLottery($userId,$totalPrice,$balance);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onDoLotteryEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 购买商品成功后
     * @param $event
     */
    public function onBuyGoodsEvent($event)
    {
        try {
            if ($event->consumeAsset->assetId == AssetKindIds::$BEAN) {
                SensorsService::getInstance()->beanBuyIntegral($event->userId,$event->roomId,$event->consumeAsset->count,$event->balance,$event->timestamp);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onBuyGoodsEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 用户注册成功后
     * @param $event
     */
    public function onUserLoginEvent($event)
    {
        try {
            if ($event->isRegister) {
                SensorsUserService::getInstance()->userRegisterSensors($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onUserLoginEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 修改用户信息成功后
     */
    public function onUserUpdateProfileEvent($event)
    {
        try {
            SensorsUserService::getInstance()->editUserAttribute($event->userId,$event->profile);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onUserUpdateProfileEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * 主播魅力等级变更
     * @param $event
     */
    public function onLevelChangeEvent($event)
    {
//        try {
//            SensorsUserService::getInstance()->levelChange($event->userId,$event->oldLevel,$event->newLevel,$timestamp);
//        } catch (Exception $e) {
//            Log::error(sprintf('SensorsDataHandler::onLevelChangeEvent userId=%d ex=%d:%s',
//                $event->userId, $e->getCode(), $e->getMessage()));
//        }
    }

    /**
     * 发布动态-更新神策用户表 动态数
     * @param $event
     */
    public function onReleaseDynamicEvent($event)
    {
        try {
            SensorsUserService::getInstance()->addForum($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onReleaseDynamicEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 点赞 type 1点赞
     * @param $event
     */
    public function onEnjoyForumEvent($event)
    {
        try {
            if ($event->type == 1) {
                SensorsUserService::getInstance()->enjoyForum($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onEnjoyForumEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 充值成功后 增加的资产
     * @param $event
     */
    public function onChargeEvent($event)
    {
        try {
            $product = ProductSystem::getInstance()->findProduct($event->productId);
            if ($product->deliveryAssets && count($product->deliveryAssets) > 0) {
                foreach ($product->deliveryAssets as $assetItem) {
                    SensorsService::getInstance()->recharge($event->userId,$event->orderId,$assetItem->assetId,$assetItem->count,$event->timestamp);
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onChargeEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 会员到期后
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            SensorsUserService::getInstance()->editUserVip($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onVipExpiresEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 注册后 完善用户信息
     * @param $event
     */
    public function onPerfectUserInfoEvent($event)
    {
        try {
            SensorsUserService::getInstance()->editUserAttribute($event->userModel->userId,[
                'sex' => $event->userModel->sex,
                'nickname' => $event->userModel->nickname,
                'birthday' => $event->userModel->birthday,
            ]);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onPerfectUserInfoEvent userId=%d ex=%d:%s',
                $event->userModel->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 后台审核昵称成功后
     * @param $event
     */
    public function onMemberDetailAuditEvent($event)
    {
        try {
            if($event->status == 1 && in_array($event->memberDetailAuditModel->action, [MemberDetailAuditActionModel::$nickname])){
                SensorsUserService::getInstance()->editUserAttribute($event->userId,[
                    'nickname' => $event->memberDetailAuditModel->content
                ]);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onMemberDetailAuditEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 钻石兑换音豆
     */
    public function onDiamondExchangeBeanDomainEvent($event)
    {
        try {
            SensorsService::getInstance()->diamondExchangeCoin($event);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onDiamondExchangeBeanDomainEvent event=%s ex=%d:%s',
                json_encode($event), $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 绑定手机号
     */
    public function onUserUpdateMobileEvent($event)
    {
        try {
            SensorsUserService::getInstance()->setMobile($event->userId,$event->mobile);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onUserUpdateMobileEvent event=%s ex=%d:%s',
                json_encode($event), $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 任务签到
     * @param $event
     */
    public function onUserTaskWeekSignEvent($event)
    {
        try {
            SensorsUserService::getInstance()->weekSign($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onUserTaskWeekSignEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 青少年模式开关
     * @param $event
     */
    public function onSwitchMonitorEvent($event)
    {
        try {
            SensorsUserService::getInstance()->switchMonitor($event->userId,$event->switch);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onSwitchMonitorEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 关注用户
     * @param $event
     */
    public function onAttentionUserEvent($event)
    {
        try {
            SensorsUserService::getInstance()->careUser($event->userId,$event->attentionUserIds);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onAttentionUserEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 用户加入退出公会
     * @param $event
     */
    public function onGhAuditMemberEvent($event)
    {
        try {
            SensorsUserService::getInstance()->editUserAttribute($event->userId,['roleType'=>1]);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onGhAuditMemberEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 发红包成功后
     */
    public function onSendRedPacketEvent($event)
    {
        try {
            SensorsService::getInstance()->sendRedPacket($event);
        } catch (Exception $e) {
            Log::error(sprintf('SensorsDataHandler::onSendRedPacketEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }
}