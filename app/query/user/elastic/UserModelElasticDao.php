<?php


namespace app\query\user\elastic;


use app\core\elasticSearch\ElasticSearchBase;
use app\domain\duke\DukeSystem;
use app\query\user\QueryUser;
use app\utils\TimeUtil;

class UserModelElasticDao extends ElasticSearchBase
{
    public $index = 'zb_member';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * TODO https://www.yuque.com/docs/share/fef658b4-2c22-4174-94c6-5243c3b3e638?#%20%E3%80%8Aes%E7%AE%80%E5%8D%95%E4%BD%BF%E7%94%A8%E3%80%8B
     * DemoModel constructor.
     */
    private function __construct()
    {
        parent::__construct($this->index);
    }


    /**
     * @param $data
     * @return QueryUser
     */
    public function dataToModel($data)
    {
        $model = new QueryUser();
        $model->userId = $data['id'];
        $model->prettyId = $data['pretty_id'];
        $model->username = $data['username'];
        $model->password = $data['password'];
        $model->nickname = $data['nickname'];
        $model->sex = $data['sex'];
        $model->intro = $data['intro'];
        $model->avatar = $data['avatar'];
        $model->prettyAvatar = $data['pretty_avatar'];
        $model->prettyAvatarSvga = $data['pretty_avatar_svga'];
        $model->voiceIntro = (string) $data['pretty_avatar'];
        $model->voiceTime = (int) $data['pretty_avatar_svga'];
        $model->status = $data['status'];
        $model->role = $data['role'];
        $model->birthday = $data['birthday'];
        $model->city = $data['city'];
        $model->lvDengji = $data['lv_dengji'];
        $model->mobile = $data['mobile'];
        $model->accId = $data['accid'];
        $model->levelExp = $data['level_exp'];
        $model->vipLevel = $data['is_vip'] ?? 0;
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

    /**
     * @param $model
     * @return array
     */
    public function modelToData($model)
    {
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
            'is_vip' => $model->vipLevel,
            'lv_dengji' => $model->lvDengji,
            'mobile' => $model->mobile,
            'accid' => $model->accId,
            'level_exp' => $model->levelExp,
            'vip_exp' => $model->vipExpiresTime,
            'svip_exp' => $model->svipExpiresTime,
            'register_time' => TimeUtil::timeToStr($model->registerTime),
            'register_ip' => $model->registerIp,
            'login_time' => TimeUtil::timeToStr($model->loginTime),
            'login_ip' => $model->loginIp,
            'is_cancel' => $model->isCancel,
            'deviceid' => $model->deviceId,
            'cancel_user_status' => $model->cancelStatus,
            'cancellation_time' => $model->cancellationTime,
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
            'duke_value' => $model->dukeValue,
            'duke_expires' => $model->dukeExpiresTime,
            'push_notice' => $model->pushNotice,
            'unablechat' => $model->unablechat,
            'source' => $model->source,
            'guild_id' => $model->guildId,
            'online' => $model->online,
        ];
        return $data;
    }

    /**
     * @param $id
     * @param $model
     * @return bool
     */
    public function storeData($id, $model)
    {
        $data = $this->modelToData($model);
        return $this->doCreateOrUpdate($id, $data);
    }


    /**
     * @param $userIds
     * @param int $offset
     * @param int $limit
     * @return array|null
     */
    public function loadUserModelForNotMatch($userIds, $offset = 0, $limit = 20)
    {
        $sdata = $this
            ->setMustNotTerm('id', $userIds)
            ->orderBy(['id' => 'desc'])
            ->from($offset)
            ->size($limit)
            ->search();
        if (empty($sdata)) {
            return null;
        }
        $datas = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $list = [];
        foreach ($datas as $data) {
            $list[] = $this->dataToModel($data);
        }
        return $list;
    }


    /**
     * @Info 房间号 和房间靓号搜索房间
     * @param $id
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchUserForId($id, $offset, $count)
    {
        $sdata = $this
            ->setShouldTerm('id', $id)
            ->setShouldTerm('pretty_id', $id)
            ->setMustTerm('cancel_user_status',0)
            ->from($offset)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [[], 0];
        }
        $dataList = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return [$listModel, $total];
    }

    /**
     * @param $name
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchUserForNickname($name, $offset, $count)
    {
        $sdata = $this
            ->setMustMatch('nickname', $name)
            ->setMustTerm('cancel_user_status',0)
            ->from($offset)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [[], 0];
        }
        $dataList = $this->getData($sdata);
        $total = $this->getTotal($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return [$listModel, $total];
    }

    public function searchUserByIdfa($idfa, $count) {
        $sdata = $this->setMustTerm('idfa', $idfa)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [];
        }
        $dataList = $this->getData($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return $listModel;
    }

    public function searchUserByDieviceId($idfa, $count) {
        $sdata = $this->setMustTerm('device_id', $idfa)
            ->size($count)
            ->search();
        if (empty($sdata)) {
            return [];
        }
        $dataList = $this->getData($sdata);
        $listModel = [];
        foreach ($dataList as $data) {
            $listModel[] = $this->dataToModel($data);
        }
        return $listModel;
    }

}
