<?php

namespace app\view;

use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\user\service\UserRegisterService;
use app\query\backsystem\dao\MarketChannelModelDao;
use app\domain\user\service\UserService;
use app\query\backsystem\dao\PromoteRoomConfModelDao;
use app\query\user\dao\DaiChongModelDao;
use app\service\HuaBanService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use constant\FirstChargeConstant;
use think\facade\Db;

class UserView
{
    public static function getRegisterRoomId($user)
    {
        // 设置注册进房间
        if ($user->isRegister) {
            if (!empty($user->getUserModel()->inviteCode)) {
                $inviteRoomId = MarketChannelModelDao::getInstance()->getRoomIdByInviteCode($user->getUserModel()->inviteCode);
                if (!$inviteRoomId) {
                    $inviteRoomId = PromoteRoomConfModelDao::getInstance()->getRoomIdByInviteCode(['id' => $user->getUserModel()->inviteCode]);
                }
                if ($inviteRoomId) {
                    UserRegisterService::getInstance()->recordMemberInvitcodeLog($user->getUserModel()->inviteCode, $user->getUserId(), $inviteRoomId);
                    return [$inviteRoomId];
                }
            }
            $userId = $user->getUserId();
            if ($user->getUserModel()->registerChannel == 'appStore') {
                $dnName = Sharding::getInstance()->getDbName('biMaster', $userId);
                $res = Db::connect($dnName)->table('bi_channel_appstore')->where(['user_id' => $userId])->find();
                if ($res) {
                    $roomIds = RedisCommon::getInstance()->getRedis()->sMembers('puton:come_room:type:ios_channel');
                    if (!empty($roomIds)) {
                        return $roomIds;
                    }
                }
            } else {
                $dnName = Sharding::getInstance()->getDbName('biMaster', $userId);
                $res = Db::connect($dnName)->table('bi_channel_huawei')->where(['user_id' => $userId])->find();
                if ($res) {
                    $roomIds = RedisCommon::getInstance()->getRedis()->sMembers('puton:come_room:type:huawei_channel');
                    if (!empty($roomIds)) {
                        return $roomIds;
                    }
                }
            }
            $registerChannel = $user->getUserModel()->registerChannel;
            if (!empty(RedisCommon::getInstance()->getRedis()->SMEMBERS(sprintf('channel_puton:come_room:type:%s', $registerChannel)))) {
                return RedisCommon::getInstance()->getRedis()->SMEMBERS(sprintf('channel_puton:come_room:type:%s', $registerChannel));
            }
            return RedisCommon::getInstance()->getRedis()->SMEMBERS('regist_roomid');
        }
        return [];
    }

    public static function viewUser($user, $source, $version, $channel)
    {
        $userModel = $user->getUserModel();
        $ret = [
            'userid' => $user->getUserId(),
            'username' => $userModel->username,
            'nickname' => $userModel->nickname,
            'intro' => $userModel->intro,
            'status' => $userModel->status,
            'birthday' => $userModel->birthday,
            'token' => $user->getToken(),
            'roomnumber' => $userModel->roomId == 0 ? null : $userModel->roomId,
            'accid' => $userModel->accId,
            'sex' => $userModel->sex,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'regist_roomid' => self::getRegisterRoomId($user),
            'is_pwd' => $userModel->password ? 1 : 0,
            'mobile' => $userModel->mobile,
            'zy_uid' => '',
            'login_time' => TimeUtil::timeToStr($userModel->loginTime),
            'attestation' => $userModel->attestation,
            'is_vip' => $userModel->vipLevel,
            'pwdlayer' => $user->pwdLayer,
        ];
        $yunxinRes = YunxinCommon::getInstance()->updateUserToken($user->getUserId());
        if (isset($yunxinRes['info']['token'])) {
            $ret['yxtoken'] = $yunxinRes['info']['token']?$yunxinRes['info']['token']:"";
        } else {
            $ret['yxtoken'] = '';
        }

        //画板
        $ret['tls'] = HuaBanService::getInstance()->getTls($user->getUserId());

        if ($user->getUserModel()->vipLevel == 1) {
            $ret['upload_num'] = 10;
            $ret['vip_exp_time'] = $userModel->vipExpiresTime == 0 ? '' : TimeUtil::timeToStr($userModel->vipExpiresTime, "Y-m-d");
        } elseif ($user->getUserModel()->vipLevel == 2) {
            $ret['upload_num'] = 10;
            $ret['vip_exp_time'] = $userModel->svipExpiresTime == 0 ? '' : TimeUtil::timeToStr($userModel->svipExpiresTime, "Y-m-d");
        } else {
            $ret['upload_num'] = 3;
            $ret['vip_exp_time'] = '';
        }
        $ret['heartInterval'] = config('config.heartInterval');
        //获取用户送豆权限
        $sendPermission = DaiChongModelDao::getInstance()->getPermission($user->getUserId());
        if ($channel == 'appStore' && version_compare($version,'2.9.3', '<=')) {
            $ret['sendOneself'] = true;
        } else {
            $ret['sendOneself'] = false;
        }
        $ret['isShowSendMd'] = empty($sendPermission) ? false : true;
        //获取用户是否可以送自己礼物的权限

        //获取用户状态
        $ret['accountState'] = UserService::getInstance()->getUserStatus($userModel);
        $ret['useRN'] = 1;

        // 获取是否请求首充条件
        $firstChargeFinish = RedisCommon::getInstance()->getRedis()->hget(sprintf('userinfo_%s', $user->getUserId()), FirstChargeConstant::FIRST_CHARGE_FINISH);
        $ret['is_request_first_charge'] = !$firstChargeFinish;
        return $ret;
    }

}


