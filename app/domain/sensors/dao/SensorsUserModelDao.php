<?php

namespace app\domain\sensors\dao;


use app\domain\sensors\model\SensorsUserModel;
use app\utils\TimeUtil;

class SensorsUserModelDao
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SensorsUserModelDao();
        }
        return self::$instance;
    }

    public function getSex($sex)
    {
        if($sex == 1){
            return '男';
        }else if($sex == 2){
            return '女';
        }else if($sex == 3){
            return '保密';
        }else{
            return'其他';
        }
    }

    public function dataToModel($data)
    {
        $model = new SensorsUserModel();
        $model->userId = $data['userId'];
        $model->guildId = $data['guild_id'];
        $model->prettyRoomId = $data['pretty_room_id'];
        $model->roomName = $data['room_name'];
        $model->roomType = $data['room_type'];
        $model->roomMode = $data['room_mode'];
        $model->isHot = $data['is_hot'];
        $model->roomTypeName = $data['room_type_name'];
        $model->lock = $data['room_lock'];
        $model->visitorNumber = $data['visitor_number'];
        $model->visitorExternNumber = $data['visitor_externnumber'];
        $model->visitorUsers = $data['visitor_users'];
        $model->isLive = $data['is_live'];
        $model->hxRoom = $data['hx_room'];
        $model->image = $data['room_image'];
        $model->tagImage = $data['tag_image'];
        $model->tagId = $data['tag_id'];
        $model->tabIcon = $data['tab_icon'];
        $model->ownerUserId = $data['user_id'];
        $model->ownerNickname = $data['nickname'];
        $model->ownerAvatar = $data['avatar'];
        return $model;
    }

    public function modelToData($model)
    {
        return [
            'signup_time'       => (string)$model->registerTime,
            'DownloadChannel'   => (string)$model->channel,
            'gender'            => (string)$model->sex,
            'birthday'          => empty($model->birthday) ? '1970-01-01' : $model->birthday,
            'nick_name'         => (string)$model->nickname,
            'constellation'     => (string)$model->constellation,
            'information'       => (int)$model->information,
            'age'               => (int)$model->age,
            'roletype'          => (string)$model->role,
            'follows'           => (int)$model->followNum,
            'charm_level'       => (string)$model->charmLevel,
            'fans'              => (int)$model->fansNum,
            'vip_duetime'       => $model->vipEndTime > 0 ? TimeUtil::timeToStr($model->vipEndTime) : '1970-01-01',
            'vip_level'         => (string)$model->vipLevel,
            'vip_type'          => (string)$model->vipType,
            'group'             => $model->guild,
            'teenager'          => (bool)$model->isOpenTeenagers,
            'voice_on'          => (bool)$model->isOpenSound,
            'total_release'     => (int)$model->addForumNum,
            'total_like'        => (int)$model->likeNum,
            'total_checkin'     => (int)$model->signInNum,
            'balance'           => (int)$model->balance,
            'push_id'           => (string)$model->geTuiId,
            'iPhone'            => (string)$model->mobile,
            'friends'           => (int)$model->friendNum
        ];
    }

}