<?php


namespace app\event\handler;


use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\event\PerfectUserInfoEvent;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\model\UserModel;
use app\domain\user\service\UserService;
use app\domain\user\UserRepository;
use app\event\MemberDetailAuditEvent;
use app\event\SendGiftEvent;
use app\event\TradeUnionAgentEvent;
use app\event\UserLoginEvent;
use app\query\pay\dao\OrderModelDao;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;


class YunxinHandler
{
    public function onVipWillExpiresEvent($event)
    {
        try {
            Log::info(sprintf('YunxinHandler::onVipWillExpiresEvent userId=%d vipLevel=%d nDay=%d',
                $event->userId, $event->vipLevel, $event->nDay));

            $msg = $avatarMsg = '';
            $vipStr = $event->vipLevel == 2 ? 'SVIP' : 'VIP';
            // 7日、3日、1日提示会员过期消息
            if (in_array($event->nDay, [1, 3, 7])) {
                $msg = sprintf('您的%s会员将在%d天后到期，为了不影响您的权益想用，请您及时续费！', $vipStr, $event->nDay);
            }
            // 7日提示头像过期信息
            if (in_array($event->nDay, [7])) {
                $avatarMsg = '您的会员将在七天后到期，会员到期后您上传的动态头像将会丢失，您的头像将变成系统默认头像。为了不影响您的使用，请您记得及时续费哦!';
            }

            if ($msg || $avatarMsg) {
                // 自动续费用户不提示
                $productTypes = $event->vipLevel == 2 ? 3 : 2;
                $vipAutoPay = AutoRenewalService::getInstance()->processVipAgreementStatus($event->userId, $productTypes);
                if (!$vipAutoPay && $msg){
                    //queue YunXinMsg
                    $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
                    Log::info(sprintf('YunxinHandler::onVipWillExpiresEvent userId=%d vipLevel=%d resMsg=%s',
                        $event->userId, $event->vipLevel, $resMsg));
                }
                if (!$vipAutoPay && $avatarMsg){
                    //queue YunXinMsg
                    $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $avatarMsg]]);
                    Log::info(sprintf('YunxinHandler::onVipWillExpiresEvent userId=%d vipLevel=%d resMsg=%s',
                        $event->userId, $event->vipLevel, $resMsg));
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onVipWillExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('YunxinHandler::onVipExpiresEvent userId=%d vipLevel=%d',
                $event->userId, $event->vipLevel));

            $vipStr = $event->vipLevel == 2 ? 'SVIP' : 'VIP';
            $msg = sprintf('您的%s会员已到期，您的%s特权已失效，快去开通会员享受权益吧！', $vipStr, $vipStr);
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
            Log::info(sprintf('YunxinHandler::onVipExpiresEvent userId=%d vipLevel=%d resMsg=%s',
                $event->userId, $event->vipLevel, $resMsg));
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function onBuyVipEvent($event)
    {
        try {
            Log::info(sprintf('YunxinHandler::onBuyVipEvent userId=%d vipLevel=%d isOpen=%d',
                $event->userId, $event->vipLevel, $event->isOpen));
            $vipStr = $event->vipLevel == 2 ? 'SVIP' : 'VIP';
            $vipExpStr = date('Y年m月d日', $event->expiresTime);
            $msg = sprintf('恭喜您已开通%s会员，有效期至%s，赶快享受您的特权之旅吧！', $vipStr, $vipExpStr);
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
            Log::info(sprintf('YunxinHandler::onBuyVipEvent userId=%d vipLevel=%d resMsg=%s',
                $event->userId, $event->vipLevel, $resMsg));
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onBuyVipEvent $userId=%d ex=%s',
                $event->userId, $e->getTraceAsString()));
        }
    }

    public function calcLongTimeStr($longTime)
    {
        if ($longTime == 600) {
            return '10min';
        } elseif ($longTime == 1800) {
            return '30min';
        } elseif ($longTime == 3600) {
            return '60min';
        }
        return '永久';
    }

    public function onRoomBanUserEvent($event)
    {
        try {
            if ($event->ban == true) {
                $minute = $this->calcLongTimeStr($event->longTime);
                $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
                $roomModelName = $roomModel->name ?? "";
                $opUserNickname = UserModelDao::getInstance()->findNicknameByUserId($event->opUserId) ?? "";

                $msg = sprintf('亲爱的用户，于  %s  被 %s 设置禁止在 %s房间发言 %s，时间结束后可发言，温馨提示：房间互动时需严格遵守平台规定，共同营造良好平台氛围。祝您玩的愉快！',
                    date('Y-m-d H:i:s', $event->timestamp),$opUserNickname, $roomModelName, $minute);
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
                Log::info(sprintf('YunxinHandler::onRoomBanUserEvent userId=%d roomId=%d longTime=%d opUserId=%d msg=%s resMsg=%s',
                    $event->userId, $event->roomId, $event->longTime, $event->opUserId, $msg, $resMsg));

                $banUserNickname = UserModelDao::getInstance()->findNicknameByUserId($event->userId) ?? "";
                $msg = sprintf('%s于  %s  被 %s 设置禁止在 %s房间发言 %s', $banUserNickname, date('Y-m-d H:i:s', $event->timestamp), $opUserNickname, $roomModelName, $minute);
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $roomModel->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
                Log::info(sprintf('YunxinHandler::onRoomBanUserEvent userId=%d roomId=%d longTime=%d roomUserId=%d msg=%s resMsg=%s',
                    $event->userId, $event->roomId, $event->longTime, $roomModel->userId, $msg, $resMsg));
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onRoomBanUserEvent userId=%d roomId=%d longTime=%d opUserId=%d ex=%s',
                $event->userId, $event->roomId, $event->longTime, $event->opUserId, $e->getTraceAsString()));
        }
    }

    public function onRoomBlackUserEvent($event)
    {
        try {
            $opUserNickname = UserModelDao::getInstance()->findNicknameByUserId($event->opUserId) ?? "";
            $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
            $roomModelName = $roomModel->name ?? "";
            $minute = $this->calcLongTimeStr($event->longTime);
            $msg = sprintf('您于  %s  被 %s 移出 %s房间 %s，时间结束后可进入。温馨提示：房间互动时需严格遵守平台规定，共同营造良好平台氛围。祝您玩的愉快！', date('Y-m-d H:i:s', $event->timestamp), $opUserNickname, $roomModelName, $minute);
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
            Log::info(sprintf('YunxinHandler::onRoomBlackUserEvent userId=%d roomId=%d longTime=%d opUserId=%d msg=%s resMsg=%s',
                $event->userId, $event->roomId, $event->longTime, $event->opUserId, $msg, $resMsg));

            $blackUserNickname = UserModelDao::getInstance()->findNicknameByUserId($event->userId) ?? "";
            $msg = sprintf('%s于  %s  被 %s 移出 %s房间 %s', $blackUserNickname, date('Y-m-d H:i:s', $event->timestamp), $opUserNickname, $roomModelName, $minute);
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $roomModel->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
            Log::info(sprintf('YunxinHandler::onRoomBlackUserEvent userId=%d roomId=%d longTime=%d roomUserId=%d msg=%s resMsg=%s',
                $event->userId, $event->roomId, $event->longTime, $roomModel->userId, $msg, $resMsg));
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onRoomBlackUserEvent userId=%d roomId=%d longTime=%d opUserId=%d ex=%s',
                $event->userId, $event->roomId, $event->longTime, $event->opUserId, $e->getTraceAsString()));
        }
    }

    public function onThreeLootNoticeEvent($event)
    {
        try {
            Log::info(sprintf('YunxinHandler::onThreeLootNoticeEvent order=%s tableId=%d',
                json_encode($event->order), $event->tableId));
            $seatInfos = $event->order->seatInfos;
            $winnerIndex = $event->order->winnerIndex;
            for ($i = 0; $i < count($seatInfos); $i++) {
                $seatInfo = $seatInfos[$i];
                $grabUserId = $seatInfo->userId;
                if ($i != $winnerIndex) {
                    $msg = ['msg' => '很遗憾！您在抢占幸运位' . $event->tableId . '号桌中没有获奖。再接再厉，下个幸运儿就是您！'];
                } else {
                    $msg = ['msg' => '恭喜您！您在抢占幸运位' . $event->tableId . '号桌中获得了' . $event->order->giftName . '礼物，您可在我的-背包内查看。'];
                }
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $grabUserId, 'type' => 0, 'msg' => $msg]);
                Log::info(sprintf('YunxinHandler::onThreeLootNoticeEvent userId=%d tableId=%d resMsg=%s',
                    $grabUserId, $event->tableId, $resMsg));
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onBuyVipEvent $userId=%d ex=%s',
                $event->userId, $e->getTraceAsString()));
        }
    }

    public function onChargeEvent($event)
    {
        try {
            $redis = RedisCommon::getInstance()->getRedis();
            $firstPayNotice = $redis->hget(sprintf('userinfo_%s', $event->userId), 'first_pay_notice');
            if ($firstPayNotice == false) {
                Log::info(sprintf('YunxinHandler::onChargeEvent event=%s',
                    json_encode($event)));
                $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
                $msg = ["msg" => '亲爱的' . $userModel->nickname . '，为保证您在平台内有良好的体验，您可添加客服官方微信fanqiepaidui02，在平台内遇到的问题均可向客服咨询。祝您玩的愉快！'];
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => $msg]);
                Log::info(sprintf('YunxinHandler::onChargeEvent userId=%d resMsg=%s',
                    $event->userId, $resMsg));
                $redis = RedisCommon::getInstance()->getRedis();
                $redis->hset(sprintf('userinfo_%s', $event->userId), 'first_pay_notice', 1);
            }
            if ($event->payChannel == 22) {  //ios充值
                $timestamp = $event->timestamp;
                $start_time = date('Y-m-d 00:00:00', $timestamp);
                $end_time = date('Y-m-d 23:59:59', $timestamp);
                $where[] = ['addtime', '>=', $start_time];
                $where[] = ['addtime', '<=', $end_time];
                $where[] = ['uid', '=', $event->userId];
                $where[] = ['status', '=', 2];
                $count = OrderModelDao::getInstance()->getChargeCount($where);
                if (in_array($count, [3, 5])) {
                    Log::info(sprintf('YunxinHandler::onChargeEvent event=%s',
                        json_encode($event)));
                    $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
                    $msg = ["msg" => '亲爱的' . $userModel->nickname . '，为保证您在平台内有良好的体验，您可添加客服官方微信fanqiepaidui02，在平台内遇到的问题均可向客服咨询。祝您玩的愉快！'];
                    //queue YunXinMsg
                    $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => $msg]);
                    Log::info(sprintf('YunxinHandler::onChargeEvent userId=%d resMsg=%s',
                        $event->userId, $resMsg));
                }
            } else {    //安卓充值
                $timestamp = $event->timestamp;
                $start_time = date('Y-m-1 00:00:00', $timestamp);
                $mdays = date('t', $timestamp);
                $end_time = date('Y-m-' . $mdays . ' 23:59:59', $timestamp);
                $where[] = ['addtime', '>=', $start_time];
                $where[] = ['addtime', '<=', $end_time];
                $where[] = ['uid', '=', $event->userId];
                $where[] = ['status', '=', 2];
                $count = OrderModelDao::getInstance()->getChargeCount($where);
                if (in_array($count, [3, 10, 50])) {
                    Log::info(sprintf('YunxinHandler::onChargeEvent event=%s',
                        json_encode($event)));
                    $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
                    $msg = ["msg" => '亲爱的' . $userModel->nickname . '，为保证您在平台内有良好的体验，您可添加客服官方微信fanqiepaidui02，在平台内遇到的问题均可向客服咨询。祝您玩的愉快！'];
                    //queue YunXinMsg
                    $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => $msg]);
                    Log::info(sprintf('YunxinHandler::onChargeEvent userId=%d resMsg=%s',
                        $event->userId, $resMsg));
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onChargeEvent $userId=%d ex=%s',
                $event->userId, $e->getTraceAsString()));
        }
    }

    public function onIosChargeEvent($event)
    {
        try {
            $redis = RedisCommon::getInstance()->getRedis();
            $firstWakePay = $redis->hget(sprintf('userinfo_%s', $event->userId), 'first_wake_pay_for_ios');
            if ($firstWakePay == false) {
                Log::info(sprintf('YunxinHandler::onIosChargeEvent event=%s',
                    json_encode($event)));
                $msg = ["msg" => '如果在支付过程中遇到问题，可添加客服微信 fanqiepaidui02，我们将尽快解决'];
                //queue YunXinMsg
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => $msg]);
                Log::info(sprintf('YunxinHandler::onIosChargeEvent userId=%d resMsg=%s',
                    $event->userId, $resMsg));
                $redis = RedisCommon::getInstance()->getRedis();
                $redis->hset(sprintf('userinfo_%s', $event->userId), 'first_wake_pay_for_ios', 1);
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onIosChargeEvent $userId=%d ex=%s',
                $event->userId, $e->getTraceAsString()));
        }

    }

    /**
     * @info 送礼推送消息给指定的
     * @param SendGiftEvent $event
     * @return
     */
    public function onSendGiftEvent(SendGiftEvent $event)
    {
        Log::info(sprintf('YunxinHandler::onSendGiftEvent entry event=%s',
            json_encode($event)));
        if (!$event->sendNotice || $event->roomId != 0) {
            return true;
        }
        try {
            $fromUserId = $event->fromUserId;
            $toUid = current($event->receiveUsers)->userId;
            $toName = UserModelDao::getInstance()->findNicknameByUserId($toUid);
            $giftModel = $event->giftKind;
            $giftCount = $event->count;
            $resMsg = YunXinMsg::getInstance()->sendGift($fromUserId, $toUid, $giftModel, $giftCount, $toName);
            Log::info(sprintf('YunxinHandler::onSendGiftEvent sendGift event=%s  resMsg=%s',
                json_encode($event), $resMsg));
            return true;
        } catch (\Exception $e) {
            Log::info(sprintf('YunxinHandler::onSendGiftEvent Exception event=%s ex=%s',
                json_encode($event), $e->getTraceAsString()));
            return false;
        }
    }


    public function onTradeUnionAgentEvent(TradeUnionAgentEvent $event)
    {
//        Log::info(sprintf('YunxinHandler::onTradeUnionAgentEvent entry event=%s',json_encode($event)));
//        if (empty($event->uid) || empty($event->toUid)) {
//            return true;
//        }
//        try {
//            $userModel = UserModelDao::getInstance()->getNicknameData($event->toUid);
//            if (empty($userModel)) {
//                throw new FQException("onTradeUnionAgentEvent not find userData");
//            }
//            $toName=isset($userModel->nickname)?$userModel->nickname:"";
//            $resMsg = YunXinMsg::getInstance()->sendTradeUnionAgent($event->uid, $event->toUid, $toName, $event->bean);
//            Log::info(sprintf('YunxinHandler::onTradeUnionAgentEvent info event=%s  resMsg=%s',
//                json_encode($event), $resMsg));
//            return true;
//        } catch (\Exception $e) {
//            Log::info(sprintf('YunxinHandler::onSendGiftEvent Exception event=%s ex=%s',
//                json_encode($event), $e->getTraceAsString()));
//            return false;
//        }
    }

    /**
     * @Info 完善用户资料事件
     * @param PerfectUserInfoEvent $event
     */
    public function onPerfectUserInfoEvent(PerfectUserInfoEvent $event)
    {
        try {
            UserService::getInstance()->upYunxinUserInfo($event->userModel);
        } catch (\Exception $e) {
            Log::warning(sprintf('YunxinHandler::onPerfectUserInfoEvent userId=%d ex=%d:%s',
                $event->userModel->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 用户信息审核 通知网易云信更新用户名片信息
     * @param MemberDetailAuditEvent $event
     */
    public function onMemberDetailAuditEvent(MemberDetailAuditEvent $event)
    {
        try {
            if (in_array($event->memberDetailAuditModel->action, [MemberDetailAuditActionModel::$avatar, MemberDetailAuditActionModel::$nickname, MemberDetailAuditActionModel::$intro])) {
                UserService::getInstance()->upYunxinUserInfo($event->userModel);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('YunxinHandler::onMemberDetailAuditEvent userId=%d ex=%d:%s',
                $event->userModel->userId, $e->getCode(), $e->getMessage()));
        }
    }


    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
            if ($userModel === null) {
                throw new FQException("not find user model", 500);
            }
            $result = $this->refreshUserInfo($userModel);
            Log::info(sprintf('YunxinHandler::onUserLoginEvent success userId=%d responseRe:%s', $userModel->userId, json_encode($result)));
        } catch (Exception $e) {
            Log::warning(sprintf('YunxinHandler::onUserLoginEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    //刷新用户的网易云数据
    private function refreshUserInfo(UserModel $userModel)
    {
        $result = YunxinCommon::getInstance()->updateUinfo($userModel->userId, $userModel->nickname, CommonUtil::buildImageUrl($userModel->avatar), $userModel->intro, "", $userModel->birthday, $userModel->mobile, $userModel->sex);
        return $result;
    }

    /**
     * @desc 维护云信用户关系
     * @param $event
     */
    public function onFocusFriendDomainEvent($event)
    {
        try {
            $userId = $event->user->getUserId();
            Log::info(sprintf('YunxinHandler::onFocusFriendDomainEvent userId=%d friendId=%d  isFocus=%s ',
                $userId, $event->friendId, $event->isFocus));
            // 关注
            if ($event->isFocus == 1) {
                YunxinCommon::getInstance()->addFriend($userId, $event->friendId);
            }
            // 取关
            if ($event->isFocus == 2) {
                YunxinCommon::getInstance()->deleteFriend($userId, $event->friendId, true);
            }
        } catch (Exception $e) {
            Log::error(sprintf('YunxinHandler::onFocusFriendDomainEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->user->getUserId(), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}