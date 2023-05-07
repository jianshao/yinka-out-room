<?php

namespace app\domain\im\service;

use app\common\RedisCommon;
use app\domain\dao\ImCheckMessageModelDao;
use app\domain\exceptions\FQException;
use app\domain\exceptions\ImException;
use app\domain\forum\dao\ForumBlackModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\im\queue\AmpQueue;
use app\domain\models\ImCheckMessageModel;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserService;
use app\event\ImChatPointEvent;
use app\query\weshine\service\WeShineService;
use think\facade\Log;

class ImService
{
    protected static $instance;

    private $filterCheckVersionMessageKey = "ImService_checkVersionMessage_filter";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ImService();
        }
        return self::$instance;
    }

    /**
     * @info imCheckUser 检查用户数据是否正常，是否发送消息
     * 主播->主播----->
     * 私聊条数无限制
     *
     * 用户->主播----->
     * 未实名:
     * 1级：可私聊3名主播，回复等于主动私聊；
     * 2级以上无限制；
     * 已实名：无限制
     *
     * 用户->用户----->
     * 未实名：
     *  1级：主动和回复用户私聊数量0人；
     *  2-5级： 主动和回复用户私聊数量3人；
     *  6级以上无限制；
     * 已实名：
     *  无限制
     */
    public function imCheckUserSecond($fromUid, $toUid, $deviceId = '')
    {
        $resCode = 200;
        $resMessage = "发送成功";
        $apiResponse = "发送成功";
        try {
            $userModel = null;
            $userModel = UserModelDao::getInstance()->loadUserModel($fromUid);
            if ($userModel === null) {
                throw new FQException("用户信息错误", 500);
            }
            $userModel->lvDengji = (int)$userModel->lvDengji;
            $userModel->attestation=(int)$userModel->attestation;
//            如果是客服
            if (in_array($fromUid, [101, 102, 103, 104, 1000004]) || in_array($toUid, [101, 102, 103, 104, 1000004])) {
                return [201, $resMessage, $apiResponse];
            }
//            用户已注销
            if (UserService::getInstance()->getUserStatus($userModel) == 0) {
                throw new FQException("该用户已注销", 500);
            }
            //判断双方关系
            $blackModel = ForumBlackModelDao::getInstance()->getBlackModel($fromUid, $toUid);
            if ($blackModel) {
                throw new FQException("您已将对方拉黑", 500);
            }

            $blackModel = ForumBlackModelDao::getInstance()->getBlackModel($toUid, $fromUid);
            if ($blackModel) {
                throw new FQException("对方已将您拉黑", 500);
            }

            //主播发消息无限制
            $fromUserRole = MemberSocityModelDao::getInstance()->getUserRole($fromUid);
            if ($fromUserRole) {
                return [$resCode, $resMessage, $apiResponse];
            }

            $toUserRole =  MemberSocityModelDao::getInstance()->getUserRole($toUid);
            if ($toUserRole) {
                return [$resCode, $resMessage, $apiResponse];
            }

            if ($userModel->lvDengji < 10) {
                throw new ImException("等级大于等于10级才可以私聊哦",9);
            }
            return [$resCode, $resMessage, $apiResponse];
//            //判断消息触发方式（2主动私聊/1被动回复）
//            $triggerMode = ImCheckMessageModelDao::getInstance()->getTriggerMode($fromUid, $toUid);
//            //获取被私聊对象角色（0用户 / 1主播）
//            $toUserRole = MemberSocityModelDao::getInstance()->getUserRole($toUid);
//            if ($triggerMode == 1) {
//                if ($toUserRole == 0) {
//                    if ($userModel->attestation == 1) {
//                        return [$resCode, $resMessage, $apiResponse];
//                    } else {
//                        //判断用户是否充值
//                        $bean = BeanModelDao::getInstance()->loadBean($fromUid);
//                        if ($bean->total > 0) {
//                            return [$resCode, $resMessage, $apiResponse];
//                        } else {
//                            $replyUserIds = $this->getReplyUserIds($fromUid, $toUserRole);
//                            if (count($replyUserIds) >= 5) {
//                                if (in_array($toUid, $replyUserIds)) {
//                                    return [$resCode, $resMessage, $apiResponse];
//                                } else {
//                                    throw new ImException("您需要进行实名认证或等级≥【6级】才可以发送聊天",9);
//                                }
//                            } else{
//                                $this->addReplyUserId($fromUid, $toUid, $toUserRole);
//                                return [$resCode, $resMessage, $apiResponse];
//                            }
//                        }
//                    }
//                } else {
//                    return [$resCode, $resMessage, $apiResponse];
//                }
//            } else {
//                if ($toUserRole == 0) {
//                    if ($userModel->attestation == 1) {
//                        $redis = RedisCommon::getInstance()->getRedis();
//                        $isExists = $redis->hExists('userinfo_'. $fromUid, 'auth_deviceId');
//                        if (!$isExists) {
//                            return [$resCode, $resMessage, $apiResponse];
//                        } else {
//                            if ($deviceId == $redis->hGet('userinfo_' . $fromUid,'auth_deviceId')) {
//                                return [$resCode, $resMessage, $apiResponse];
//                            } else {
//                                if ($userModel->lvDengji < 2) {
//                                    throw new ImException("等级达到【2级】，解锁更多私聊机会",1);
//                                } else if ($userModel->lvDengji >= 2 && $userModel->lvDengji <= 5) {
//                                    $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                                    if (count($activeChatUserIds) >= 1) {
//                                        if (in_array($toUid, $activeChatUserIds)) {
//                                            return [$resCode, $resMessage, $apiResponse];
//                                        } else {
//                                            throw new ImException("等级达到【6级】，解锁更多私聊机会",2);
//                                        }
//                                    } else {
//                                        $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                                        return [$resCode, $resMessage, $apiResponse];
//                                    }
//                                } else if ($userModel->lvDengji >= 6 && $userModel->lvDengji <= 10) {
//                                    $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                                    if (count($activeChatUserIds) >= 3) {
//                                        if (in_array($toUid, $activeChatUserIds)) {
//                                            return [$resCode, $resMessage, $apiResponse];
//                                        } else {
//                                            throw new ImException("等级达到【11级】，解锁更多私聊机会",3);
//                                        }
//                                    } else {
//                                        $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                                        return [$resCode, $resMessage, $apiResponse];
//                                    }
//                                } else {
//                                    return [$resCode, $resMessage, $apiResponse];
//                                }
//                            }
//                        }
//                    } else {
//                        if ($userModel->lvDengji < 2) {
//                            throw new ImException("您需要进行实名认证或等级≥【2级】才可以主动发起聊天",4);
//                        } elseif ($userModel->lvDengji >=2 && $userModel->lvDengji <= 5) {
//                            $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                            if (count($activeChatUserIds) >= 3) {
//                                if (in_array($toUid, $activeChatUserIds)) {
//                                    return [$resCode, $resMessage, $apiResponse];
//                                } else {
//                                    throw new ImException("您需要进行实名认证或等级≥【6级】才可以主动发起聊天",5);
//                                }
//                            } else {
//                                $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                                return [$resCode, $resMessage, $apiResponse];
//                            }
//                        } else {
//                            return [$resCode, $resMessage, $apiResponse];
//                        }
//                    }
//                } else {
//                    if ($userModel->lvDengji < 2) { //10
//                        $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                        if (count($activeChatUserIds) >= 10) {
//                            if (in_array($toUid, $activeChatUserIds)) {
//                                return [$resCode, $resMessage, $apiResponse];
//                            } else {
//                                throw new ImException("等级达到【2级】，解锁更多私聊机会",6);
//                            }
//                        } else {
//                            $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                            return [$resCode, $resMessage, $apiResponse];
//                        }
//                    } elseif ($userModel->lvDengji >= 2 && $userModel->lvDengji <= 5) { //11
//                        $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                        if (count($activeChatUserIds) >= 11) {
//                            if (in_array($toUid, $activeChatUserIds)) {
//                                return [$resCode, $resMessage, $apiResponse];
//                            } else {
//                                throw new ImException("等级达到【6级】，解锁更多私聊机会",7);
//                            }
//                        } else {
//                            $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                            return [$resCode, $resMessage, $apiResponse];
//                        }
//                    } elseif ($userModel->lvDengji >= 6 && $userModel->lvDengji <= 10) { //14
//                        $activeChatUserIds = $this->getActiveChatUserIds($fromUid, $toUserRole);
//                        if (count($activeChatUserIds) >= 14) {
//                            if (in_array($toUid, $activeChatUserIds)) {
//                                return [$resCode, $resMessage, $apiResponse];
//                            } else {
//                                throw new ImException("等级达到【11级】，解锁畅聊模式",8);
//                            }
//                        } else {
//                            $this->addActiveChatUserId($fromUid, $toUid, $toUserRole);
//                            return [$resCode, $resMessage, $apiResponse];
//                        }
//                    } else {
//                        return [$resCode, $resMessage, $apiResponse];
//                    }
//                }
        } catch (FQException $e) {
            return [$e->getCode(), $e->getMessage(), $e->getMessage()];
        } catch (ImException $e) {
            event(new ImChatPointEvent($userModel, $e->getCode(), time()));
            return [500, $e->getMessage(), $e->getMessage()];
        }
    }

    //获取用户回复的人数 role:(0用户 / 1主播)
    public function getReplyUserIds($userId, $role) {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $key = sprintf('%s:reply:%s', $userId, $role == 0 ? 'user' : 'host');
        if (!$redis->exists($key)) {
            $replyUserIds = ImCheckMessageModelDao::getInstance()->getReplyUserIds($userId);
            $redis->sAdd($key, ...$replyUserIds);
            return $replyUserIds;
        } else {
            return $redis->sMembers($key);
        }
    }

    //写入用户回复的人
    public function addReplyUserId($userId, $toUserId, $role) {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $key = sprintf('%s:reply:%s', $userId, $role == 0 ? 'user' : 'host');
        $redis->sAdd($key, $toUserId);
    }
    //获取用户主动私聊的人数 role (0用户 / 1主播)
    public function getActiveChatUserIds($userId, $role) {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $key = sprintf('%s:activechat:%s', $userId, $role == 0 ? 'user' : 'host');
        if (!$redis->exists($key)) {
            $activeChatUserIds = ImCheckMessageModelDao::getInstance()->getActiveChatUserIds($userId, $role);
            $redis->sAdd($key, ...$activeChatUserIds);
            return $activeChatUserIds;
        } else {
            return $redis->sMembers($key);
        }
    }
    //写入用户主动私聊的人
    public function addActiveChatUserId($userId, $toUserId, $role) {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $key = sprintf('%s:activechat:%s', $userId, $role == 0 ? 'user' : 'host');
        $redis->sAdd($key, $toUserId);
    }


    /**
     * @info 是否发送版本太低的提示消息
     * @param $channel
     * @param $version
     */
    public function checkVersionMessage($channel, $version, $toUid, $message)
    {
        try {
            $re = WeShineService::getInstance()->checkWeshineImages($message);
            if (!$re) {
                return true;
            }
            $sendMsg = false;
            if ($channel === 'appStore' && version_compare($version, '2.9.3', '<=')) {
                $sendMsg = true;
            }

            if ($channel !== 'appStore' && version_compare($version, '3.2.7', '<=')) {
                $sendMsg = true;
            }

            if ($sendMsg) {
                $strdate = date("Ymd");
                $this->filterCheckVersionMessage($toUid, $strdate);

                $msg = "亲爱的用户，您的版本过低无法接收表情包消息，升级版本解锁更优聊天体验！";
                YunXinMsg::getInstance()->sendAssistantMsg($toUid, $msg);
            }
            return true;
        } catch (\Exception $e) {
            Log::info(sprintf('ImController:checkVersionMessage: uid:%s, message:%s error msg%s:error code:%s', $toUid, $message, $e->getMessage(), $e->getCode()));
            return false;
        }
    }

    /**
     * @info 过滤版本兼容的用户今日是否需要推送小秘书通知
     * @param $toUid
     * @param $strdate
     * @return bool
     * @throws FQException
     */
    private function filterCheckVersionMessage($toUid, $strdate)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf("%s_date:%s_touid:%d", $this->filterCheckVersionMessageKey, $strdate, $toUid);
        $incrNumber = $redis->incr($cacheKey);
        if ($incrNumber > 1) {
            throw new FQException("filterCheckVersionMessage", 500);
        }
        $redis->expire($cacheKey, 86410);
        return true;
    }


    /**
     * @info 用户对主播
     * @param $userModel
     * @param $fromUid
     * @param $toUid
     * @return bool
     * @throws FQException
     */
    private function checkUserToGuild($userModel, $fromUid, $toUid)
    {
        if ($userModel->lvDengji >= 2) {
            return true;
        }
        $imEdUidArr = ImCheckMessageModelDao::getInstance()->getTalkData($fromUid, 'to_uid'); //发送给多少个用户私信
        $imEdUidCount = count($imEdUidArr);
        if ($imEdUidCount >= 3) {
            if (!in_array($toUid, $imEdUidArr)) {
                throw new FQException('您需要进行实名认证或等级≥6级才可以主动发起聊天', 500);
            }
        }
        return true;
    }

    /**
     * @info 用户对用户
     * @param $userModel
     * @param $fromUid
     * @param $toUid
     * @return bool
     * @throws FQException
     */
    private function checkUserToUser($userModel, $fromUid, $toUid)
    {
        if ($userModel->lvDengji >= 6) {
            return true;
        }
        if ($userModel->lvDengji === 1) {
            throw new FQException('您需要进行实名认证或等级≥6级才可以主动发起聊天', 500);
        }

        $imEdUidArr = ImCheckMessageModelDao::getInstance()->getTalkData($fromUid, 'to_uid'); //发送给多少个用户私信
        $imEdUidCount = count($imEdUidArr);
        if ($imEdUidCount >= 3) {
            if (!in_array($toUid, $imEdUidArr)) {
                throw new FQException('您需要进行实名认证或等级≥6级才可以主动发起聊天', 500);
            }
        }
        return true;
    }

    /**
     * @desc 产生新消息推送到队列中
     * @param ImCheckMessageModel $model
     * @param $messageId
     * @return bool
     * @throws \Exception
     */
    public function createMessageQueue(ImCheckMessageModel $model, $messageId)
    {
        $messageData = $model->encodeData();
        $messageData['id'] = (int)$messageId;
        $strData = json_encode($messageData);
        Log::info(sprintf("ImService entry createQueue:%s", $strData));
        return AmpQueue::getInstance()->publisher($strData);
    }

    /**
     * @desc 修改消息状态
     * @param $userId
     * @param $messageId
     * @param $status
     * @return int
     * @throws FQException
     */
    public function updateRecordStatus($userId, $messageId, $status)
    {
        $imModel = ImCheckMessageModelDao::getInstance()->loadRecord($userId, $messageId);
        if (!$imModel) {
            throw new FQException('消息不存在', 500);
        }
        $imModel->status = $status;
        $imModel->updatedTime = time();
        $res = ImCheckMessageModelDao::getInstance()->updateRecord($imModel, $messageId);
        // 发送队列
        $this->createMessageQueue($imModel, $messageId);
        return $res;
    }
}


