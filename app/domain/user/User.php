<?php

namespace app\domain\user;

use app\common\RedisCommon;
use app\domain\asset\UserAssets;
use app\domain\duke\Duke;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\task\UserTasks;
use app\domain\user\dao\UserLastInfoDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\model\MemberDetailAuditModel;
use app\domain\user\model\UserModel;
use app\domain\user\service\UserService;
use app\domain\vip\Vip;
use app\query\user\cache\CachePrefix;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class User
{
    // UserAssets
    private $assets = null;
    // UserTasks
    private $tasks = null;
    // 数据
    private $userModel = null;
    // 用户token
    private $token = '';
    private $clientInfo = null;

    private $duke = null;
    private $vip = null;

    private $todayEarnings = null;

    public $isRegister = false;
    public $pwdLayer = 0;
    protected $user_info_key = "userinfo_";


    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function getAssets()
    {
        if ($this->assets == null) {
            $this->assets = new UserAssets($this);
        }
        return $this->assets;
    }

    public function getTasks()
    {
        if ($this->tasks == null) {
            $this->tasks = new UserTasks($this);
        }
        return $this->tasks;
    }

    public function getDuke($timestamp)
    {
        if ($this->duke == null) {
            $duke = new Duke($this);
            $duke->load($timestamp);
            $this->duke = $duke;
        }
        return $this->duke;
    }

    public function getVip($timestamp)
    {
        if ($this->vip == null) {
            $vip = new Vip($this);
            $vip->load($timestamp);
            $this->vip = $vip;
        }
        return $this->vip;
    }

    public function getTodayEarnings($timestamp)
    {
        if ($this->todayEarnings == null) {
            $todayEarnings = new TodayEarnings($this);
            $todayEarnings->load($timestamp);
            $this->todayEarnings = $todayEarnings;
        }
        return $todayEarnings;
    }

    public function getUserId()
    {
        return $this->userModel->userId;
    }

    public function getUserModel()
    {
        return $this->userModel;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setClientInfo($clientInfo)
    {
        $this->clientInfo = $clientInfo;
    }

    public function getClientInfo()
    {
        return $this->clientInfo;
    }

    public function getInviteCode()
    {
        return $this->userModel->inviteCode;
    }

    public function updateAccId($accId)
    {
        $this->userModel->accId = $accId;

        $updateDatas = [
            'accid' => $accId,
        ];

        UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateDatas);

        Log::info(sprintf('UpdateAccIdOk: userId=%d accId=%d', $this->getUserId(), $accId));
    }


    /**
     * @param null $confirm 是否强制注销，不过滤注销状态:true 强制  null 不强制
     * @throws FQException
     */
    public function checkCancelUser($confirm = null)
    {
        if (is_null($confirm)) {
            if ($this->getUserModel()->cancelStatus != 0) {
                throw new FQException('您的账号已经注销', 500);
            }
        }

        if ($this->getUserModel()->guildId > 0) {
            throw new FQException('公会成员⽆法申请注销账号', 101);
        }

        $timestamp = time();
        $assets = $this->getAssets();
        $bean = $assets->getBean($timestamp);
        $diamond = $assets->getDiamond($timestamp);

        if ($bean->balance($timestamp) > 0
            || $diamond->balance($timestamp) >= 10000) {
            throw new FQException('账号内余额不符合申请条件', 101);
        }
    }

    public function cancelUser()
    {
        // 检查是否可以注销
        $this->checkCancelUser();

        // 状态切换到申请状态
        $this->getUserModel()->cancelStatus = 2;

        $timeUnix = time();
        UserModelDao::getInstance()->updateDatas($this->getUserId(), ['cancel_user_status' => 2, 'cancellation_time' => $timeUnix]);
    }


    /**
     * @info 老用户reset 重至用户的注销状态，重置申请注销记录表
     */
    public function resetCancelUser()
    {
        $this->getUserModel()->cancelStatus = 0;
        UserModelDao::getInstance()->resetCancellation($this->getUserId());
    }

    public function updateMobile($mobile)
    {
        $this->userModel->mobile = $mobile;
        $this->userModel->usernmae = $mobile;

        $updateDatas = [
            'username' => $mobile,
            'mobile' => $mobile
        ];

        UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateDatas);

        Log::info(sprintf('UpdateMobileOk: userId=%d mobile=%s', $this->getUserId(), $mobile));
    }

    public function updatePassword($password)
    {
        $this->userModel->password = $password;
        $updateDatas = [
            'password' => $password
        ];
        UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateDatas);

        Log::info(sprintf('UpdatePasswordOk: userId=%d password=%s', $this->getUserId(), $password));
    }

    /**
     * @info:修改用户审核状态逻辑
     * @param MemberDetailAuditModel $memberDetailAuditModel
     * @return UserModelDao|bool
     * @throws FQException
     */
    public function updateAuditProfile(MemberDetailAuditModel $memberDetailAuditModel)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        Log::info(sprintf('UpdateProfile entry: userId=%d memberDetailAuditModel=%s', $this->getUserId(), json_encode($memberDetailAuditModel)));

        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $this->getUserId()));

        switch ($memberDetailAuditModel->action) {

            case MemberDetailAuditActionModel::$avatar:
                $this->userModel->avatar = $memberDetailAuditModel->content;
                $updateData['avatar'] = $memberDetailAuditModel->content;
                $result = UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateData);
                return $result;

            case MemberDetailAuditActionModel::$nickname:
                $this->userModel->nickname = $memberDetailAuditModel->content;
                $this->userModel->nickname_hash = md5($memberDetailAuditModel->content);
                return UserModelDao::getInstance()->updateUserNickname($this->getUserId(), $memberDetailAuditModel->content);

            case MemberDetailAuditActionModel::$intro:
                $this->userModel->intro = $memberDetailAuditModel->content;
                $updateData['intro'] = $memberDetailAuditModel->content;
                return UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateData);

            case MemberDetailAuditActionModel::$wall:
                $userKey = $this->user_info_key . $this->getUserId();
                $redis = RedisCommon::getInstance()->getRedis();
                $result = $redis->hset($userKey, 'album', $memberDetailAuditModel->content);
                if ($result !== false) {
                    return true;
                }
                return $result;

            case MemberDetailAuditActionModel::$voice:
                $voice = UserService::getInstance()->formatContentToVoice($memberDetailAuditModel->content);
                $this->userModel->voiceIntro = ArrayUtil::safeGet($voice, 'voiceIntro', '');
                $this->userModel->voiceTime = ArrayUtil::safeGet($voice, 'voiceTime', '');
                $updateData['pretty_avatar'] = $this->userModel->voiceIntro;
                $updateData['pretty_avatar_svga'] = $this->userModel->voiceTime;
                return UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateData);

        }
        return false;
    }


    public function updateLoginInfo($loginTime, $loginIp, $imei)
    {
        $this->userModel->loginTime = $loginTime;
        $this->userModel->loginIp = $loginIp;
        $updateDatas = [
            'login_ip' => $loginIp,
            'login_time' => TimeUtil::timeToStr($loginTime, '%Y-%m-%d %H:%M:%S'),
            'imei' => $imei
        ];
        UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateDatas);
        Log::info(sprintf('UpdateLoginInfoOk: userId=%d info=%s', $this->getUserId(), json_encode($updateDatas)));
    }

    public function updateAvatar($avatar)
    {
        $this->userModel->avatar = $avatar;
        $updateDatas = [
            'avatar' => $avatar
        ];
        UserModelDao::getInstance()->updateDatas($this->getUserId(), $updateDatas);

        Log::info(sprintf('UpdateAvatarOk: userId=%d avatar=%s', $this->getUserId(), $avatar));
    }

    public function updateUserLastInfo($clientInfo, $userId)
    {
        $data = [
            'user_id' => $userId,
            'login_ip' => $clientInfo->clientIp,
            'channel' => $clientInfo->channel,
            'device' => $clientInfo->device,
            'deviceid' => $clientInfo->deviceId,
            'platform' => $clientInfo->platform,
            'version' => $clientInfo->version,
            'edition' => $clientInfo->edition,
            'imei' => $clientInfo->imei,
            'idfa' => $clientInfo->idfa,
            'appid' => $clientInfo->appId,
            'source' => $clientInfo->source,
            'simulator_info' => $clientInfo->simulatorInfo,
            'update_time' => time()
        ];
        if (!empty(UserLastInfoDao::getInstance()->getUserInfo($userId))) {
            UserLastInfoDao::getInstance()->saveData($userId, $data);
        } else {
            UserLastInfoDao::getInstance()->addData($userId, $data);
        }
        Log::info(sprintf('updateUserLastInfo: userId=%d info=%s', $userId, json_encode($data)));
    }


    /**
     * @Info 申请注销不足15天登录了， 撤销申请
     * @param UserModel $userModel
     */
    public function resetCancellation(UserModel $userModel)
    {
        if ($userModel->cancelStatus != 2) {
            return;
        }
//        申请注销不足15天登录了， 撤销申请
        $result = UserModelDao::getInstance()->resetCancellation($userModel->userId);
        if (intval($result) >= 1) {
            $msg = '您的注销申请已撤销，若注销账号请重新提交。';
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userModel->userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
            Log::info(sprintf('user::resetCancellation userId=%d resMsg=%s', $userModel->userId, $resMsg));
        }
        return;
    }


    /**
     * @Info 用户已注销:清除原token，报错
     * @param UserModel $userModel
     * @throws FQException
     */
    public function checkCancel(UserModel $userModel)
    {
        if ($userModel->cancelStatus != 1) {
            return;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $headToken = $redis->get($userModel->userId);
        if (!is_null($headToken)) {
            $redis->del($headToken);
            throw new FQException('登陆已失效请重新登陆', 500);
        }
        return;
    }


}



