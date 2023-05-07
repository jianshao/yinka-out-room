<?php

namespace app\domain\user\model;

class UserCacheModel extends UserModel
{
    // 用户ID

    public $onlineStatus=0;   //在线为1 不在线为2
    private static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserCacheModel();
        }
        return self::$instance;
    }

    public function modelToData(UserCacheModel $model) {
        $data = [
            'id' => $model->userId,
            'pretty_id' => $model->prettyId,
            'username' => $model->username,
            'password' => $model->password,
            'nickname' => $model->nickname,
            'sex' => $model->sex,
            'intro' => $model->intro,
            'avatar' => $model->avatar,
            'pretty_avatar' => $model->prettyAvatar,
            'pretty_avatar_svga' => $model->prettyAvatarSvga,
            'status' => $model->status,
            'role' => $model->role,
            'birthday' => $model->birthday,
            'city' => $model->city,
            'lv_dengji' => $model->lvDengji,
            'mobile' => $model->mobile,
            'accid' => $model->accId,
            'level_exp' => $model->levelExp,
            'vip_exp' => $model->vipExpiresTime,
            'svip_exp' => $model->svipExpiresTime,
            'register_time' => $model->registerTime,
            'register_ip' => $model->registerIp,
            'login_time' => $model->loginTime,
            'login_ip' => $model->loginIp,
            'is_cancel' => $model->isCancel,
            'deviceid' => $model->deviceId,
            'cancel_user_status' => $model->cancelStatus,
            'attestation' => $model->attestation,
            'invitcode' => $model->inviteCode,
            'register_channel' => $model->registerChannel,
            'regist_version' => $model->registerVersion,
            'imei' => $model->imei,
            'idfa' => $model->idfa,
            'qopenid' => $model->qopenid,
            'wxopenid' => $model->wxopenid,
            'wxunionid' => $model->wxunionid,
            'appleid' => $model->appleid,
            'roomnumber' => $model->roomId,
            'zy_uid' => $model->zyUid,
            'duke_id' => $model->dukeLevel,
            'duke_expires' => $model->dukeExpiresTime,
            'push_notice' => $model->pushNotice,
            'unablechat' => $model->unablechat,
            'source' => $model->source,
            'online_status' => $model->onlineStatus,
        ];
        return $data;
    }
}


