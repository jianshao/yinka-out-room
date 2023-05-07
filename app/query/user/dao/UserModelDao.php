<?php


namespace app\query\user\dao;


use app\core\mysql\ModelDao;
use app\domain\duke\DukeSystem;
use app\query\user\QueryUser;
use app\utils\TimeUtil;

class UserModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected $serviceName = 'userSlave';
    protected static $instance;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new QueryUser();
        $model->userId = $data['id'];
        $model->prettyId = $data['pretty_id'];
        $model->username = $data['username'];
        $model->password = $data['password'];
        $model->nickname = $data['nickname'];
        $model->sex = $data['sex'] ?? 2;
        $model->intro = $data['intro'];
        $model->avatar = $data['avatar'];
        $model->prettyAvatar = $data['pretty_avatar'];
        $model->prettyAvatarSvga = $data['pretty_avatar_svga'];
        $model->voiceIntro = (string)$data['pretty_avatar'];
        $model->voiceTime = (int)$data['pretty_avatar_svga'];
        $model->status = $data['status'];
        $model->role = $data['role'];
        $model->birthday = $data['birthday'];
        $model->city = $data['city'];
        $model->lvDengji = $data['lv_dengji'];
        $model->mobile = $data['mobile'];
        $model->accId = $data['accid'];
        $model->levelExp = $data['level_exp'];
        $model->vipLevel = $data['is_vip'];
        $model->vipExpiresTime = $data['vip_exp'];
        $model->svipExpiresTime = $data['svip_exp'];
        $model->registerTime = TimeUtil::strToTime($data['register_time']);
        $model->registerIp = $data['register_ip'];
        $model->loginTime = TimeUtil::strToTime($data['login_time']);
        $model->loginIp = $data['login_ip'];
        $model->isCancel = $data['is_cancel'];
        $model->deviceId = $data['deviceid'];
        $model->cancelStatus = $data['cancel_user_status'];
        $model->cancellationTime = $data['cancellation_time'] ?? 0;
        $model->attestation = $data['attestation'];
        $model->inviteCode = $data['invitcode'];
        $model->registerChannel = $data['register_channel'];
        $model->registerVersion = $data['regist_version'];
        $model->imei = $data['imei'];
        $model->idfa = $data['idfa'];
        $model->qopenid = $data['qopenid'];
        $model->wxopenid = $data['wxopenid'];
        $model->wxunionid = $data['wxunionid'];
        $model->appleid = $data['appleid'];
        $model->roomId = $data['roomnumber'];
        $model->zyUid = $data['zy_uid'];
        $model->dukeValue = $data['duke_value'] ?? 0;
        $model->dukeLevel = DukeSystem::getInstance()->calcDukeLevel($data['duke_id'], $model->dukeValue, $data['duke_expires'], time());
        $model->dukeExpiresTime = $data['duke_expires'];
        $model->pushNotice = $data['push_notice'];
        $model->unablechat = $data['unablechat'];
        $model->source = $data['source'];
        $model->guildId = $data['guild_id'] ?? 0;
        $model->online = $data['online'] ?? 0;
        return $model;
    }

    public function loadUserData($userId)
    {
        $where[] = ['id', '=', $userId];
        $where[] = ['cancel_user_status', '<>', 1];
        return $this->getModel($userId)->where($where)->find();
    }

}