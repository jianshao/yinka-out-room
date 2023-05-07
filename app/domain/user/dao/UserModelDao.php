<?php

namespace app\domain\user\dao;

use app\common\RedisCommon;
use app\core\model\BaseModel;
use app\core\mysql\ModelDao;
use app\domain\duke\DukeSystem;
use app\domain\user\model\UserModel;
use app\query\user\cache\CachePrefix;
use app\utils\TimeUtil;

class UserModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new UserModel();
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

    public function modelToData(UserModel $model)
    {
        $data = [
            'id' => $model->userId,
            'pretty_id' => $model->prettyId,
            'username' => $model->username,
            'password' => $model->password,
            'nickname' => $model->nickname,
            'nickname_hash' => $model->nicknameHash,
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
            'is_vip' => $model->vipLevel,
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

    public function saveUserModel($model)
    {
        $data = $this->modelToData($model);
        $this->getModel($model->userId)->save($data);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $model->userId));

        UserInfoMapDao::getInstance()->addByPretty($model->prettyId, $model->userId);
        UserInfoMapDao::getInstance()->addByNickname($model->nickname, $model->userId);
    }

    public function loadUserModelWithLock($userId)
    {
        return $this->loadUserModelImpl($userId, true);
    }

    public function loadUserModel($userId)
    {
        return $this->loadUserModelImpl($userId, false);
    }

    public function findUserModelMapByUserIds($userIds)
    {
        $ret = [];
        $dbModels = $this->getModels($userIds);
        foreach ($dbModels as $dbModel) {
            $datas = $dbModel->getModel()->where([['id', 'in', $dbModel->getList()]])->select()->toArray();
            foreach ($datas as $data) {
                $model = $this->dataToModel($data);
                $ret[$model->userId] = $model;
            }
        }

        return $ret;
    }

    public function findUserModelsByUserIds($userIds)
    {
        $ret = [];
        $dbModels = $this->getModels($userIds);
        foreach ($dbModels as $dbModel) {
            $datas = $dbModel->getModel()->where([['id', 'in', $dbModel->getList()]])->select()->toArray();
            foreach ($datas as $data) {
                $model = $this->dataToModel($data);
                $ret[] = $model;
            }
        }

        return $ret;
    }

    private function loadUserModelImpl($userId, $lock = true)
    {
        $where = ['id' => $userId];
        if ($lock) {
            $data = $this->getModel($userId)->lock(true)->where($where)->find();
        } else {
            $data = $this->getModel($userId)->where($where)->find();
        }
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @param $userId
     * @param $datas
     * @return BaseModel
     * @throws \app\domain\exceptions\FQException
     */
    public function updateDatas($userId, $datas)
    {
        $res = $this->getModel($userId)->where(['id' => $userId])->update($datas);
        //删除userInfoCache
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
        return $res;
    }

    /**
     * @info 修改用户昵称
     * @param $userId
     * @param $nickname
     */
    public function updateUserNickname($userId, $nickname)
    {
        $updateDatas['nickname'] = $nickname;
        $updateDatas['nickname_hash'] = md5($nickname);
        NicknameLibraryDao::getInstance()->updateUseNickName($nickname);
        return $this->updatedatas($userId, $updateDatas);
    }

    public function findNicknameByUserId($userId)
    {
        return $this->getModel($userId)->where(array("id" => $userId))->value("nickname");
    }

    public function findPasswordByUserId($userId)
    {
        return $this->getModel($userId)->where(array("id" => $userId))->value("password");
    }

    public function findAvatarByUserId($userId)
    {
        return $this->getModel($userId)->where(array("id" => $userId))->value("avatar");
    }


    public function findRegisterTimeByUserId($userId)
    {
        $register_time = $this->getModel($userId)->where(array("id" => $userId))->value("register_time");
        return TimeUtil::strToTime($register_time);
    }

    /**
     * @Info 查询用户是否存在
     * @param $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isUserIdExists($userId)
    {
        $where[] = ['id', '=', $userId];
        $res = $this->getModel($userId)->field('username')->where($where)->find();
        return !empty($res);
    }

    /**
     * @info 查询没有注销状态的用户
     * @param $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isUserIdExistsNotCancel($userId)
    {
        $where[] = ['id', '=', $userId];
        $where[] = ['cancel_user_status', '=', 0];
        $res = $this->getModel($userId)->field('id')->where($where)->find();
        return !empty($res);
    }

    /**
     * @Info 查询没有注销成功的用户
     * @param $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isUserIdNotCancel($userId)
    {
        $where[] = ['id', '=', $userId];
        $where[] = ['cancel_user_status', '<>', 1];
        $res = $this->getModel($userId)->field('id')->where($where)->find();
        return !empty($res);
    }

    public function getBindMobile($userId)
    {
        $model = $this->getModel($userId)->field('username')->where(['id' => $userId])->find();
        if (empty($model)) {
            return null;
        }
        $res = $model->toArray();
        return $res['username'];
    }


    public function resetCancellation($userId)
    {
        return $this->updateDatas($userId, ['cancel_user_status' => 0, 'cancellation_time' => 0]);
    }

    /**
     * @info 获取申请注销时间为15天之前的用户
     * @param BaseModel $dbModel
     * @param $offset
     * @param $unixTime
     * @return array
     */
    public function getCancellationUserIds($dbModel, $offset, $unixTime)
    {
        $data = $dbModel->where('cancel_user_status', 2)->where('id', '>', $offset)->where('cancellation_time', "<", $unixTime)->order("id asc")->limit(400)->column('id');
        if (empty($data)) {
            return [];
        }
        return array_values($data);
    }

    /**
     * @info 更新注销成功,标记三方登陆字段为已废弃
     * @param $userId
     */
    public function updateCancellation(UserModel $userModel)
    {
        $data["qopenid"] = $userModel->qopenid;
        $data["wxopenid"] = $userModel->wxopenid;
        $data["appleid"] = $userModel->appleid;
        $data['username'] = $userModel->username;
        $data['nickname'] = $userModel->nickname;
        $data['nickname_hash'] = $userModel->nicknameHash;
        $data['cancel_user_status'] = $userModel->cancelStatus;
        $data['pretty_id'] = $userModel->prettyId;
        return $this->updateDatas($userModel->userId, $data);
    }

    /**
     * @param BaseModel $dbModel
     * @param $userIds
     * @param $loginTimeStart
     * @param int $limit
     * @return array
     */
    public function getCancelGuildMemberFilterUids($dbModel, $userIds, $loginTimeStart, $limit = 200)
    {
        $where[] = ['login_time', '<', $loginTimeStart];
        $where[] = ['guild_id', '<>', 0];
        $data = $dbModel->where($where)->whereNotIn("id", $userIds)->limit($limit)->column("guild_id", "id");
        if (empty($data)) {
            return [];
        }
        return $data;
    }


    /**
     * @param $userId
     * @return bool
     */
    public function quitGuildForId($userId)
    {
        if (empty($userId)) {
            return false;
        }

        return $this->getModel($userId)->where('id', $userId)->save(['guild_id' => 0, 'socity' => 0]);
    }

    /**
     * @param BaseModel $dbModel
     * @param $limit
     * @param null $minId
     * @return array
     */
    public function getLangTimeNotLoginUserIds($dbModel, $limit, $minId = null)
    {
        $whereLoginTime = strtotime('-30 day');
        $where[] = ['login_time', "<", date("Y-m-d H:i:s", $whereLoginTime)];
        $where[] = ['cancel_user_status', '=', 0];
        if ($minId !== null) {
            $where[] = ['id', '<', $minId];
        }
        $object = $dbModel->where($where)->field('id,login_time')->order("id desc")->limit($limit)->select();
        if ($object === null) {
            return [[], null];
        }
        $data = $object->toArray();
        $result = [];
        foreach ($data as $itemData) {
            if (isset($itemData['login_time']) && $itemData['login_time']) {
                $result[$itemData['id']] = strtotime($itemData['login_time']);
            }
            $minId = $itemData['id'];
        }
        return [$result, $minId];
    }

    /**
     * id和靓号 查询用户
     * @param $userId
     * @param $field
     * @return mixed
     */
    public function findPrettyIdLoadUserModel($userId, $field)
    {
        $where = ['id' => $userId];
        $userInfo = $this->getModel($userId)->field($field)->where($where)->find();
        if (empty($userInfo)) {
            $where = ['pretty_id' => $userId];
            $userInfo = $this->getModel($userId)->field($field)->where($where)->find();
        }
        return $userInfo;
    }

    /**
     * 查询用户
     * @param $userId
     * @param $field
     * @return mixed
     */
    public function findLoadUserModel($userId, $field)
    {
        $where = ['id' => $userId];
        $userInfo = $this->getModel($userId)->field($field)->where($where)->find();
        return $userInfo;
    }

    /**
     * @param $userId
     * @return BaseModel|int
     * @throws \app\domain\exceptions\FQException
     */
    public function resetUserAttention($userId)
    {
        if (empty($userId)){
            return 0;
        }
        $datas['attestation'] = 0;
        return $this->updateDatas($userId,$datas);
    }
}


