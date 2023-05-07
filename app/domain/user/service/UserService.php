<?php


namespace app\domain\user\service;

use app\common\AliPayEasyCommon;
use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\Config;
use app\domain\dao\LoginDetailNewModelDao;
use app\domain\dao\UserCardModelDao;
use app\domain\dao\UserIdentityModelDao;
use app\domain\events\CompleteRealUserDomainEvent;
use app\domain\events\UserUpdateProfileDomainEvent;
use app\domain\exceptions\FQException;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\models\LoginDetailNewModel;
use app\domain\models\UserIdentityModel;
use app\domain\models\UserIdentityStatusModel;
use app\domain\queue\producer\YunXinMsg;
use app\domain\sensors\service\SensorsUserService;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\SnsTypes;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\MemberDetailAuditDao;
use app\domain\user\dao\MemberDetailModelDao;
use app\domain\user\dao\NicknameLibraryDao;
use app\domain\user\dao\UserBlackModelDao;
use app\domain\user\dao\UserInfoMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\dao\UserOnlineModelDao;
use app\domain\user\dao\UserRoomOnlineModelDao;
use app\domain\user\event\PerfectUserInfoEvent;
use app\domain\user\event\UserLoginDomainEvent;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\model\MemberDetailAuditModel;
use app\domain\user\model\MemberDetailModel;
use app\domain\user\model\UserModel;
use app\domain\user\model\UserOnlineModel;
use app\domain\user\model\UserRoomOnlineModel;
use app\domain\user\queue\AmpQueue;
use app\domain\user\User;
use app\domain\user\UserRepository;
use app\domain\withdraw\dao\UserWithdrawBankInformationModelDao;
use app\domain\withdraw\dao\UserWithdrawDetailModelDao;
use app\domain\withdraw\service\AgentPayService;
use app\event\RoomStaySecondEvent;
use app\event\UserBindMobileEvent;
use app\event\UserLoginEvent;
use app\event\UserRegisterEvent;
use app\event\UserUpdateMobileEvent;
use app\event\UserUpdateProfileEvent;
use app\form\ClientInfo;
use app\query\user\cache\UserIdCache;
use app\query\user\cache\UserModelCache;
use app\service\BlackService;
use app\service\CommonCacheService;
use app\service\IdService;
use app\service\LockKeys;
use app\service\LockService;
use app\service\RoomNotifyService;
use app\service\ThirdLoginService;
use app\service\TokenService;
use app\service\VerifyCodeService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use app\utils\TimeUtil;
use Exception;
use think\facade\Log;

/**
 * 用户服务接口
 */
class UserService
{
    protected static $instance;
    protected $user_info_key = "userinfo_";

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserService();
        }
        return self::$instance;
    }

    /**
     * 绑定个推
     *
     * @param $userId
     * @param $gtToken
     * @param $gtUid
     * @throws FQException
     */
    public function bindGetui($userId, $gtToken, $gtUid, $config)
    {
        try {
            $snsInfo = ThirdLoginService::getInstance()->getuiLogin($gtToken, $gtUid, $config);
        } catch (FQException $e) {
            throw new FQException('手机号绑定失败请重试', 500);
        }

        $mobile = $snsInfo['username'];
        $this->bindMobileImpl($userId, $mobile);

        Log::info(sprintf('UserService::bindGetui ok userId=%d gtUid=%d',
            $userId, $gtUid));

        event(new UserBindMobileEvent($userId, $mobile, time()));
        return $mobile;
    }

    /**
     * 绑定手机
     *
     * @param $userId
     * @param $mobile
     * @param $verifyCode
     * @throws FQException
     */
    public function bindMobile($userId, $mobile, $verifyCode)
    {
        $mobile = trim($mobile);
        CommonUtil::validateMobile($mobile);
        $this->authVerifyCode($mobile, $verifyCode);

        $this->bindMobileImpl($userId, $mobile);
        Log::info(sprintf('UserService::bindMobile ok userId=%d mobile=%d',
            $userId, $mobile));

        event(new UserBindMobileEvent($userId, $mobile, time()));
    }

    /**
     * @info 验证
     * @param $mobile
     * @param $verifyCode
     * @return bool
     * @throws FQException
     */
    private function authVerifyCode($mobile, $verifyCode)
    {
        return VerifyCodeService::getInstance()->checkVerifyCodeAdapter($mobile, $verifyCode);
    }

    /**
     * 完善用户资料
     *
     * @param $userId
     * @param $profile
     * @return User
     * @throws Exception
     */
    public function perfectUserInfo($userId, $profile)
    {
        if (!is_integer($userId)) {
            throw new FQException('参数错误', 500);
        }

        if (array_key_exists('birthday', $profile)) {
            $this->checkBirthday($userId, $profile['birthday']);
        }

        if (array_key_exists('nickname', $profile)) {
            $this->checkNickname($userId, $profile['nickname']);
        }

        if (array_key_exists('avatar', $profile)) {
            $this->checkAvatar($userId, $profile['avatar']);
        }

        if (array_key_exists('sex', $profile)) {
            if (!is_integer($profile['sex'])) {
                throw new Exception('错误的性别', 500);
            }
        }

        // 未审核状态不能修改
        $this->isAuditEditProfile($userId, $profile);

        try {
            $user = $this->perfectProfile($userId, $profile);
        } catch (Exception $e) {
            Log::error(sprintf('PerfectUserInfoException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        event(new PerfectUserInfoEvent($user->getUserModel(), time()));

        Log::info(sprintf('UserService::perfectUserInfo ok userId=%d profile=%s',
            $userId, json_encode($profile)));

        return $user;
    }

    /**
     * 后台设置用户资料
     *
     * @param $userId
     * @param $datas
     * @throws Exception
     */
    public function innerSetUserInfo($userId, $datas)
    {
        if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
            throw new FQException('用户不存在', 500);
        }

        $profile = [];
        if (array_key_exists('prettyId', $datas)) {
            $existsUserId = UserInfoMapDao::getInstance()->getUserIdByPrettyId($datas['prettyId']);
            if ($existsUserId != null && $existsUserId != $userId) {
                throw new FQException('该靓号已被使用', 500);
            }
            $profile['pretty_id'] = $datas['prettyId'];
        }

        if (array_key_exists('mobile', $datas)) {
            $existsUserId = AccountMapDao::getInstance()->getUserIdByMobile($datas['mobile']);
            if ($existsUserId != null && $existsUserId != $userId) {
                throw new FQException('手机号已经存在', 500);
            }
            $profile['username'] = $datas['mobile'];
            $profile['mobile'] = $datas['mobile'];
        }

        if (array_key_exists('intro', $datas)) {
            $this->checkIntro($userId, $datas['intro']);
            $profile['intro'] = $datas['intro'];
        }

        if (array_key_exists('nickname', $datas)) {
            $this->checkNickname($userId, $datas['nickname']);
            $profile['nickname'] = $datas['nickname'];
        }

        if (array_key_exists('avatar', $datas)) {
            $this->checkAvatar($userId, $datas['avatar']);
            $profile['avatar'] = $datas['avatar'];
        }

        if (array_key_exists('pretty_avatar', $datas)) {
            $this->checkAvatar($userId, $datas['pretty_avatar']);
            $profile['pretty_avatar'] = $datas['pretty_avatar'];
        }

        try {
            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $profile) {
                if (ArrayUtil::safeGet($profile, 'nickname') != null) {
                    UserInfoMapDao::getInstance()->updateNickname($profile['nickname'], $userId);
                }

                if (ArrayUtil::safeGet($profile, 'pretty_id') != null) {
                    UserInfoMapDao::getInstance()->updatePretty($profile['pretty_id'], $userId);
                }

                if (ArrayUtil::safeGet($profile, 'mobile') != null) {
                    AccountMapDao::getInstance()->updateMobile($profile['mobile'], $userId);
                }
            });

            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $profile) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 5000);
                }

                UserModelDao::getInstance()->updateDatas($userId, $profile);
            });

            if (array_key_exists('nickname', $profile)
                || array_key_exists('pretty_id', $profile)
                || array_key_exists('avatar', $profile)
                || array_key_exists('pretty_avatar', $profile)) {
                RoomNotifyService::getInstance()->notifySyncUserData($userId);
            }
//            if (array_key_exists('nickname', $profile)) {
//                SensorsUserService::getInstance()->editUserAttribute($userId, ['nickname' => $profile['nickname']]);
//            }
//            if (array_key_exists('mobile', $profile)) {
//                SensorsUserService::getInstance()->editUserAttribute($userId, ['mobile' => $profile['mobile']]);
//            }

        } catch (Exception $e) {
            Log::error(sprintf('innerSetUserInfoException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::innerSetUserInfo ok userId=%d profile=%s',
            $userId, json_encode($profile)));

        event(new UserUpdateProfileEvent($userId, $profile, time()));
    }

    //换绑手机号
    public function setMobile($userId, $mobile, $verifyCode)
    {
        $mobile = trim($mobile);

        // 检查验证码适配
        $this->authVerifyCode($mobile, $verifyCode);
        $user = $this->bindMobileImpl($userId, $mobile);

        Log::info(sprintf('UserService::setMobile ok userId=%d mobile=%s', $userId, $mobile));

        event(new UserUpdateMobileEvent($userId, $mobile, time()));

        return $user;
    }

    /**
     * 忘记密码
     *
     * @param $mobile
     * @param $verifyCode
     * @param $password
     * @throws Exception
     */
    public function forgetPassword($mobile, $verifyCode, $password)
    {
        if (empty($mobile) || empty($verifyCode) || empty($password)) {
            throw new FQException('参数错误', 500);
        }

        $userId = AccountMapDao::getInstance()->getUserIdByMobile($mobile);
        if ($userId == 0) {
            throw new FQException('您还没有注册', 5003);
        }

        $user = null;
        try {
            $user = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $mobile, $password, $verifyCode) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('您还没有注册', 5003);
                }

                CommonUtil::validatePassword($password);

                if (config('config.VERTIFYCODE')
                    && !VerifyCodeService::getInstance()->checkVerifyCode($mobile, $verifyCode)) {
                    throw new FQException('验证码错误', 500);
                }

                $user->updatePassword(md5($password));
                return $user;
            });

        } catch (Exception $e) {
            Log::error(sprintf('ForgetPasswordException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::forgetPassword ok userId=%d', $userId));

        return $user;
    }

    /**
     * 设置密码
     * @param $userId
     * @param $oldPassword
     * @param $newPassword
     * @return User
     * @throws FQException
     */
    public function setPassword($userId, $oldPassword, $newPassword)
    {
        if (empty($newPassword)) {
            throw new FQException('参数错误', 500);
        }

        CommonUtil::validatePassword($newPassword);

        $user = null;
        try {
            $user = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $oldPassword, $newPassword) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                if (empty($user->getUserModel()->password) || empty($oldPassword)) {
                    $user->updatePassword(md5($newPassword));
                } else {
                    if (md5($oldPassword) != $user->getUserModel()->password) {
                        throw new FQException('原密码错误', 500);
                    }
                    $user->updatePassword(md5($newPassword));
                }
                return $user;
            });
        } catch (Exception $e) {
            Log::error(sprintf('SetPasswordException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::setPassword ok userId=%d', $userId));

        return $user;
    }

    /**
     * 用户信息编辑
     *
     * @param $userId
     * @param $profile
     * @return User
     * @throws Exception
     */
    public function editProfile($userId, $profile, $channel, $version)
    {
        if (!is_integer($userId)) {
            throw new FQException('参数错误', 500);
        }

        if (array_key_exists('birthday', $profile)) {
            $this->checkBirthday($userId, $profile['birthday']);
        }

        //todo 文本检测
        if (array_key_exists('nickname', $profile)) {
            $this->checkNickname($userId, $profile['nickname']);
        }

        //todo 文本检测
        if (array_key_exists('intro', $profile)) {
            $this->checkIntro($userId, $profile['intro']);
        }

        //todo 文本检测
        if (array_key_exists('city', $profile)) {
            $this->checkCity($userId, $profile['city']);
        }

        if (array_key_exists('voiceIntro', $profile)) {
            if (!array_key_exists('voiceTime', $profile)) {
                throw new FQException('参数错误', 500);
            }
            if ($channel != 'appStore' && version_compare($version, '3.2.14', '<=')) {
                $msg = ['msg' => '很抱歉，您发布的语音介绍音频格式存在问题，已被清空。请您在我们的官网中下载安卓最新版本后录制。由此给您带来的不良体验，敬请谅解！本次录制的音频不保存。'];
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
                throw new FQException('当前版本异常，请更新至最新版本后录制～', 500);
            }
            $this->checkVoiceTro($userId, $profile['voiceIntro']);
        }

        if (array_key_exists('voiceTime', $profile)) {
            $voice_time = $profile['voiceTime'];
            if ($voice_time) {
                if ($voice_time > 30 || $voice_time < 3) {
                    throw new FQException('语音时长不能小于3秒且不能超限30秒', 500);
                }
            }
        }

        // 未审核状态不能修改
        $this->isAuditEditProfile($userId, $profile);

        try {
            $user = $this->updateProfile($userId, $profile);

            if (array_key_exists('nickname', $profile)) {
                $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$nickname);
                $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
                YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
            }

            if (array_key_exists('avatar', $profile)) {
                $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$avatar);
                $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
                YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
            }

            if (array_key_exists('intro', $profile)) {
                $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$intro);
                $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
                YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
            }

            if (array_key_exists('voiceIntro', $profile)) {
                $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$voice);
                $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
                YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
            }

            Log::info(sprintf('UserService::editProfile ok userId=%d profile=%s',
                $userId, json_encode($profile)));

            event(new UserUpdateProfileEvent($userId, $profile, time()));

            return $user;
        } catch (Exception $e) {
            Log::error(sprintf('EditProfileException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 第三方登录
     *
     * @param $snsId
     * @param $snsType
     * @param $snsInfo
     * @param $clientInfo
     * @return User|null
     * @throws Exception
     */
    public function loginBySnsId($snsId, $snsType, $snsInfo, $clientInfo)
    {
        $isRegister = false;
        $userId = AccountMapDao::getInstance()->getUserIdBySnsType($snsType, $snsId);
        $userId = $this->checkUserCancelByRegister($userId);
        if ($userId <= 0) {
            $userId = $this->registerBySnsId($snsId, $snsType, $snsInfo, $clientInfo);
            $isRegister = true;
        }

        $user = null;
        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);

            list($user, $lastLoginTime) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $isRegister, $clientInfo) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $lastLoginTime = $user->getUserModel()->loginTime;

                $this->loginImpl($user, $clientInfo, $isRegister);
                return [$user, $lastLoginTime];
            });

        } catch (Exception $e) {
            Log::error(sprintf('LoginBySnsIdException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::loginBySnsId ok userId=%d snsType=%s snsId=%s',
            $userId, $snsType, $snsId));

        event(new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo, $isRegister));

        return $user;
    }

    /**
     * 账号密码登录
     *
     * @param $username
     * @param $password
     * @param $clientInfo
     */
    public function loginByPassword($username, $password, $clientInfo)
    {
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($username);
        if (empty($userId)) {
            throw new FQException('账号不存在', 5003);
        }

        $userId = $this->checkUserCancelByRegister($userId);
        if ($userId == 0) {
            throw new FQException('账号已注销', 500);
        }

        $md5Password = md5($password);
        $curPassword = UserModelDao::getInstance()->findPasswordByUserId($userId);
        if ($curPassword != $md5Password) {
            throw new FQException('账号密码错误', 500);
        }

        if (config("config.appDev") != 'dev') {
            //二次验证判断
            $deviceId = $clientInfo->deviceId;
            $startTime = date('Y-m-d', strtotime('-15 days'));
            $endTime = date('Y-m-d H:i:s', time());
            $loginCount = LoginDetailNewModelDao::getInstance()->getLoginNumberByDevice($userId, $startTime, $endTime, $deviceId); //用户该设备15天内是否登录过
            if ($loginCount == 0 && substr($username, 0, 2) != '12') {
                throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_LOGIN_AUTH_FAIL), Error::ERROR_LOGIN_AUTH_FAIL);
            }
        }

        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);

            list($user, $lastLoginTime) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $clientInfo) {
                $user = UserRepository::getInstance()->loadUser($userId);

                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $lastLoginTime = $user->getUserModel()->loginTime;

                $this->loginImpl($user, $clientInfo, false);
                return [$user, $lastLoginTime];
            });

        } catch (Exception $e) {
            Log::error(sprintf('LoginByPasswordException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::loginByPassword ok userId=%d username=%s',
            $userId, $username));

        event(new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo, false));

        return $user;
    }

    /**
     * 手机一键登录登录
     *
     * @param $mobile
     * @param $verifyCode
     * @param $clientInfo
     * @return User|null
     * @throws Exception
     */
    public function loginByAutoMobile($mobile, $clientInfo)
    {
        $mobile = trim($mobile);
        if (empty($mobile)) {
            throw new FQException('参数错误', 500);
        }

        CommonUtil::validateMobile($mobile);

        $isRegister = false;
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($mobile);
        $userId = $this->checkUserCancelByRegister($userId);
        if ($userId <= 0) {
            $userId = $this->registerByMobile($mobile, $clientInfo);
            $isRegister = true;
        }

        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);

            list($user, $lastLoginTime) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $isRegister, $clientInfo) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $lastLoginTime = $user->getUserModel()->loginTime;

                $this->loginImpl($user, $clientInfo, $isRegister);
                return [$user, $lastLoginTime];
            });

        } catch (Exception $e) {
            Log::error(sprintf('loginByAutoMobileException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::loginByAutoMobile ok userId=%d mobile=%s',
            $userId, $mobile));

        event(new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo, $isRegister));

        return $user;
    }

    /**
     * 手机验证码登录
     *
     * @param $mobile
     * @param $verifyCode
     * @param $clientInfo
     * @return User|null
     * @throws Exception
     */
    public function loginByMobile($mobile, $verifyCode, $clientInfo)
    {
        $mobile = trim($mobile);
        if (empty($mobile) || empty($verifyCode)) {
            throw new FQException('参数错误', 500);
        }

        CommonUtil::validateMobile($mobile);

        $this->authVerifyCode($mobile, $verifyCode);
        $isRegister = false;
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($mobile);
        $userId = $this->checkUserCancelByRegister($userId);
        if ($userId <= 0) {
            $userId = $this->registerByMobile($mobile, $clientInfo);
            $isRegister = true;
        }

        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);

            list($user, $lastLoginTime) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $isRegister, $clientInfo) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $lastLoginTime = $user->getUserModel()->loginTime;

                $this->loginImpl($user, $clientInfo, $isRegister);
                return [$user, $lastLoginTime];
            });

        } catch (Exception $e) {
            Log::error(sprintf('LoginByMobileException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

        Log::info(sprintf('UserService::loginByMobile ok userId=%d mobile=%s',
            $userId, $mobile));

        event(new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo, $isRegister));

        return $user;
    }

    /**
     * token登录
     * @param $token
     * @param $clientInfo
     * @return User
     * @throws Exception
     */
    public function loginByToken($token, $clientInfo)
    {
        $userId = TokenService::getInstance()->getUserIdByToken($token);
        if ($userId <= 0) {
            throw new FQException('token不存在', 5000);
        }

        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);
            list($user, $lastLoginTime) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $token, $clientInfo) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                if ($user->getToken() != $token) {
                    Log::info(sprintf('TokenNotMatch %d %s %s', $user->getUserId(), $user->getToken(), $token));
                    throw new FQException('token不匹配', 500);
                }

                $lastLoginTime = $user->getUserModel()->loginTime;
                $this->loginImpl($user, $clientInfo, false, false);
                return [$user, $lastLoginTime];
            });

        } catch (Exception $e) {
            Log::error(sprintf('LoginByTokenException userId=%d ex=%d:%s ef=%s el=%s',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            throw $e;
        }

        Log::info(sprintf('UserService::loginByToken ok userId=%d', $userId));

        event(new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo, false));

        return $user;
    }

    /**
     * 检查用户是否可以注销
     *
     * @param $mobile
     * @param $verifyCode
     * @return int
     * @throws FQException
     */
    public function checkCancelUser($userId)
    {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $user->checkCancelUser();
            });

        } catch (Exception $e) {
            Log::error(sprintf('CheckCancelUser userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 注销用户
     *
     * @param $userId
     * @param $mobile
     * @param $verifyCode
     */
    public function cancelUser($userId, $mobile, $verifyCode, $headToken = null)
    {
        $this->authVerifyCode($mobile, $verifyCode);
        $user = $this->cancelUserImpl($userId);
//        注销成功后踢出用户token
        if (!is_null($headToken)) {
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->del($headToken);
        }
        Log::info(sprintf('UserService::cancelUser ok userId=%d', $userId));
    }


    /**
     * @param $nickname
     * @return bool  true是默认昵称，false不是
     */
    public function isDefaultNickname($nickname)
    {
        if (empty($nickname)) {
            return false;
        }
        return NicknameLibraryDao::getInstance()->issetNickName($nickname);
    }


    /**
     * @Info 检测是否存在默认头像
     * @param $avatarUrl
     * @return bool
     */
    public function isDefaultAvatar($avatarUrl)
    {
        if (empty($avatarUrl)) {
            return false;
        }
        if (strpos($avatarUrl, 'Public/Uploads/image/male.png') !== false) {
            return true;
        }

        if (strpos($avatarUrl, 'Public/Uploads/image/female.png') !== false) {
            return true;
        }

        if (strpos($avatarUrl, 'images/manhead/') === 0) {
            return true;
        }

        if (strpos($avatarUrl, 'images/head/') === 0) {
            return true;
        }

        return false;
    }


    /**
     * @info 用户注销申请15天后的到期节点时间
     * @param $user
     * @return false|int
     */
    public function getCancelExpiresTime($user)
    {
        $t = intval($user->cancellationTime) + 1382400;
        return strtotime(date("Y-m-d", $t));
    }


    /**
     * 更新用户在线时长
     *
     * @param $userId
     */
    public function updateUserOnlineTime($userId, $onlineSecond)
    {
        $date = date('Y-m-d 00:00:00');
        $model = UserOnlineModelDao::getInstance()->loadUserOnline($userId, $date);
        if ($model == null) {
            $model = new UserOnlineModel($userId, $date, $onlineSecond);;
            UserOnlineModelDao::getInstance()->addData($model);
        } else {
            $model->date = $date;
            $model->onlineSecond = $onlineSecond;
            UserOnlineModelDao::getInstance()->incOnlineSecond($model);
        }

        $time = time();
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        $this->setOnlineCache($userId, $userModel->sex, $time);
    }

    protected function setOnlineCache($userId, $sex, $time)
    {
        //每个用户至少加入到一个key
        $redis = RedisCommon::getInstance()->getRedis();
        switch ($sex) {
            case 1:
            case 2: //1男 2女
                $redis->zAdd(sprintf('user_online_%s_list', $sex), $time, $userId);
                $redis->zAdd(sprintf('user_online_history_%s_list', $sex), $time, $userId);
                break;
            default:
                break;
        }
        $redis->zAdd(sprintf('user_online_%s_list', 'all'), $time, $userId);
        $redis->zAdd(sprintf('user_online_history_%s_list', 'all'), $time, $userId);
    }


    /**
     * @info 通过userID获取用户在线状态
     * @param $userId
     * @return false|int
     */
    public function getUserOnline($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->zRank(sprintf('user_online_%s_list', 'all'), $userId);
    }


    public function getUserOnlineStatus($userId, $queryUserId, $curTime)
    {
        if ($userId == $queryUserId) {
            return [true, $curTime];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $onlineFlag = $redis->zScore(sprintf('user_online_%s_list', 'all'), $queryUserId);

        //获取用户最后一次心跳时间
        $lastOnlineTime = $redis->zScore(sprintf('user_online_history_%s_list', 'all'), $queryUserId);
        return [!empty($onlineFlag), empty($lastOnlineTime) ? 0 : $lastOnlineTime];
    }

    /**
     * 更新用户房间的在线时长
     *
     * @param $userId
     */
    public function updateUserRoomOnlineTime($userId, $roomId, $onlineSecond)
    {
        $date = date('Y-m-d 00:00:00');
        $modelDao = UserRoomOnlineModelDao::getInstance();
        $model = $modelDao->loadRoomOnline($userId, $roomId, $date, 2);
        if ($model == null) {
            $model = new UserRoomOnlineModel(0, $userId, $roomId, $date, $onlineSecond);
            $modelDao->addData($model);
        } else {
            $updateOnlineSecond = $onlineSecond + $model->onlineSecond;
            $modelDao->updateOnlineSecond($model->userId, $model->id, $updateOnlineSecond);
        }
        event(new RoomStaySecondEvent($userId, $roomId, $onlineSecond, time()));
    }

    /**
     * @param $userId
     * @param $avatar
     * @throws FQException
     */
    public function setUserAvatar($userId, $avatar)
    {
        try {
            //根据用户会员类型判断用户上传头像是否符合限制
            $userModel = UserModelDao::getInstance()->loadUserModel($userId);
            if ($userModel == null) {
                throw new FQException('用户不存在', 500);
            }

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userModel, $avatar) {
                // 未审核头像不能修改
                $isMemberDetailNotAudit = MemberDetailAuditDao::getInstance()->isMemberDetailNotAudit($userModel->userId, MemberDetailAuditActionModel::$avatar);
                if ($isMemberDetailNotAudit) {
                    throw new FQException('审核通过后才能进行修改', 500);
                }

                $avatarUrl = CommonUtil::buildImageUrl($avatar);
                if ($userModel->vipLevel == 0 && CommonUtil::checkImgIsGif($avatarUrl) == 1) {
                    throw new FQException('开通会员可享上传动图权益', 500);
                }

                // 新增用户的更改信息审核记录，发送小秘书消息通知
                if ($avatar != "") {
                    $unixTime = time();
                    $model = new MemberDetailAuditModel();
                    $model->userId = $userModel->userId;
                    $model->content = $avatar;
                    $model->status = 0;
                    $model->action = MemberDetailAuditActionModel::$avatar;
                    $model->updateTime = $unixTime;
                    $model->createTime = $unixTime;
                    MemberDetailAuditDao::getInstance()->store($model);
                }
            });
        } catch (Exception $e) {
            Log::error(sprintf('setUserAvatarException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

//        新增用户的更改信息审核记录，发送小秘书消息通知
        $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$avatar);
        $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
        YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);

        Log::info(sprintf('UserService::setUserAvatar ok userId=%d avatar=%s',
            $userId, $avatar));

        event(new UserUpdateProfileEvent($userId, ['avatar' => $userModel->avatar], time()));
    }


    /**
     * 设置用户相册
     * @info 11月18日 优化相册逻辑为：如果用户提交的图片有问题直接删除，通过小秘书通知用户，返回剩余正确的图片
     * @param $userId
     * @param $album
     * @return array
     * @throws Exception
     */
    public function setAlbum($userId, $album)
    {
        try {
            $memberAvatar = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $album) {
                //判断手机号
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user === null) {
                    throw new FQException('用户不存在', 500);
                }
                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }
                //处理相册数据(以逗号分割)
                $isWfAlbum = 0;
                if (!empty($album)) {
                    $url = config('config.APP_URL_image');
                    $album = str_replace($url, '', $album);
                    $album = str_replace('https://image.fqparty.com/', '', $album);
                    $albumArray = explode(',', $album);
                    if (count($albumArray) > 10) {
                        throw new FQException('会员相册不能多于10张', 500);
                    }
                    $newAlbumArray = [];
                    foreach ($albumArray as $key => $val) {
                        CommonUtil::checkImgIsGifLite($val);
                        //todo 图片检测
                        $checkStatus = ShuMeiCheck::getInstance()->imageCheck($val, ShuMeiCheckType::$IMAGE_ALBUM_EVENT, $userId);
                        if (!$checkStatus) {
                            $isWfAlbum = 1;
                            $wfAlbumKey = $key;
                        } else {
                            $newAlbumArray[] = $val;
                        }
                    }
//                list($is_safes, $albumArray) = TextcanimgCommon::getInstance()->checkImgReset($albumArray);
//                if (!empty($albumArray)) {
                    $album = implode(",", $newAlbumArray);
//                } else {
//                    $album = "";
//                }
                    if ($isWfAlbum) {
//                发错误消息小秘书通知
                        $msg = '您的背景墙中有违规图片无法展示';
                        YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
                    }
                } else {
                    $album = '';
                }

                if ($isWfAlbum) {
                    throw new FQException(sprintf('第%d张背景墙违反平台规定', $wfAlbumKey + 1), 500);
                }

                $memberAvatar = [];
                $member_album = $album;
                if ($member_album) {
                    $albumData = explode(",", $member_album);
                    foreach ($albumData as $key => $value) {
                        $memberAvatar[$key] = CommonUtil::buildImageUrl(ltrim($value, '/'));
                    }
                } else {
                    $memberAvatar = [];
                }
                return $memberAvatar;
            });

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $album) {
                $userKey = $this->user_info_key . $userId;
                $redis = RedisCommon::getInstance()->getRedis();
//            提交的背景墙数据不为空并且不是删除图片，需要审核
                if ($album !== '' && $this->isDeletePhoto($album, $userId) === false) {
                    $unixTime = time();
                    $model = new MemberDetailAuditModel();
                    $model->userId = $userId;
                    $model->content = $album;
                    $model->status = 0;
                    $model->action = MemberDetailAuditActionModel::$wall;
                    $model->updateTime = $unixTime;
                    $model->createTime = $unixTime;
                    MemberDetailAuditDao::getInstance()->store($model);
                } else {
                    $unixTime = time();
                    $model = new MemberDetailAuditModel();
                    $model->userId = $userId;
                    $model->content = $album;
                    $model->status = 1;
                    $model->action = MemberDetailAuditActionModel::$wall;
                    $model->updateTime = $unixTime;
                    $model->createTime = $unixTime;
                    MemberDetailAuditDao::getInstance()->store($model);
                    $redis->hset($userKey, 'album', $album);
                }
            });

        } catch (Exception $e) {
            Log::error(sprintf('UserService::setAlbum userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        if ($album !== '') {
            $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$wall);
            $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
            YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
        }

        Log::info(sprintf('UserService::setAlbum ok userId=%d album=%s',
            $userId, $album));
        return $memberAvatar;
    }

    private function perfectProfile($userId, $profile)
    {
        $profile = Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $profile) {
            $unixTime = time();
            if (array_key_exists('nickname', $profile)) {
                if (!UserService::getInstance()->isDefaultNickname($profile['nickname'])) {
                    $model = new MemberDetailAuditModel();
                    $model->userId = $userId;
                    $model->content = $profile['nickname'];
                    $model->status = 0;
                    $model->action = MemberDetailAuditActionModel::$nickname;
                    $model->updateTime = $unixTime;
                    $model->createTime = $unixTime;
                    MemberDetailAuditDao::getInstance()->store($model);
                    $profile['nickname'] = '用户_' . $userId;
                }
                // 清理昵称池相关
                NicknameLibraryDao::getInstance()->updateUseNickName($profile['nickname']);
                if (ArrayUtil::safeGet($profile, 'nickname') != null) {
                    UserInfoMapDao::getInstance()->updateNickname($profile['nickname'], $userId);
                }
            }

            if (array_key_exists('avatar', $profile)) {
                if (!UserService::getInstance()->isDefaultAvatar($profile['avatar'])) {
                    $model = new MemberDetailAuditModel();
                    $model->userId = $userId;
                    $model->content = $profile['avatar'];
                    $model->status = 0;
                    $model->action = MemberDetailAuditActionModel::$avatar;
                    $model->updateTime = $unixTime;
                    $model->createTime = $unixTime;
                    MemberDetailAuditDao::getInstance()->store($model);
                }
            }
            return $profile;
        });

        return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $profile) {
            $user = UserRepository::getInstance()->loadUser($userId);
            if ($user == null) {
                throw new FQException('参数错误', 5000);
            }

            $updateDatas = [];
            if (array_key_exists('sex', $profile)) {
                $user->getUserModel()->sex = $profile['sex'];
                $updateDatas['sex'] = $profile['sex'];
            }

            if (array_key_exists('birthday', $profile)) {
                $user->getUserModel()->birthday = $profile['birthday'];
                $updateDatas['birthday'] = $profile['birthday'];
            }

            if (empty($user->getInviteCode()) && array_key_exists('invitecode', $profile)) {
                $user->getUserModel()->inviteCode = $profile['invitecode'];
                $updateDatas['invitcode'] = $profile['invitecode'];
            }

            if (array_key_exists('nickname', $profile)) {
                $user->getUserModel()->nickname = $profile['nickname'];
                $updateDatas['nickname'] = $profile['nickname'];
                $updateDatas['nickname_hash'] = md5($profile['nickname']);
            }

            if (array_key_exists('avatar', $profile)) {
                if (!UserService::getInstance()->isDefaultAvatar($profile['avatar'])) {
                    $profile['avatar'] = $user->getUserModel()->sex == 1 ? 'Public/Uploads/image/male.png' : 'Public/Uploads/image/female.png';
                }
                $user->getUserModel()->avatar = $profile['avatar'];
                $updateDatas['avatar'] = $profile['avatar'];
            }

            if (!empty($updateDatas)) {
                UserModelDao::getInstance()->updateDatas($user->getUserId(), $updateDatas);
                Log::info(sprintf('PerfectProfileOk: userId=%d profile=%s', $user->getUserId(), json_encode($updateDatas)));
            }

            event(new UserUpdateProfileDomainEvent($user, time()));
            return $user;
        });
    }

    private function updateProfile($userId, $profile)
    {
        Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $profile) {
            $unixTime = time();
            if (array_key_exists('nickname', $profile)) {
                // 新增用户的更改信息审核记录，发送小秘书消息通知
                $model = new MemberDetailAuditModel();
                $model->userId = $userId;
                $model->content = $profile['nickname'];
                $model->status = 0;
                $model->action = MemberDetailAuditActionModel::$nickname;
                $model->updateTime = $unixTime;
                $model->createTime = $unixTime;
                MemberDetailAuditDao::getInstance()->store($model);
            }

            if (array_key_exists('intro', $profile)) {
                // 新增用户的更改信息审核记录，发送小秘书消息通知
                $model = new MemberDetailAuditModel();
                $model->userId = $userId;
                $model->content = $profile['intro'];
                $model->status = 0;
                $model->action = MemberDetailAuditActionModel::$intro;
                $model->updateTime = $unixTime;
                $model->createTime = $unixTime;
                // 个性签名为空 审核直接通过
                if (!$profile['intro']) {
                    $model->status = 1;
                }
                MemberDetailAuditDao::getInstance()->store($model);
            }

            if (array_key_exists('voiceIntro', $profile) && array_key_exists('voiceTime', $profile)) {
                // 新增用户的更改信息审核记录，发送小秘书消息通知
                $model = new MemberDetailAuditModel();
                $model->userId = $userId;
                $voiceContent = UserService::getInstance()->formatVoiceAudit($profile['voiceIntro'], $profile['voiceTime']);
                $model->content = json_encode($voiceContent);
                $model->status = 0;
                $model->action = MemberDetailAuditActionModel::$voice;
                $model->updateTime = $unixTime;
                $model->createTime = $unixTime;
                MemberDetailAuditDao::getInstance()->store($model);
            }
        });

        return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $profile) {
            $user = UserRepository::getInstance()->loadUser($userId);
            if ($user == null) {
                throw new FQException('参数错误', 5000);
            }

            $updateDatas = [];
            if (array_key_exists('intro', $profile)) {
                // 个性签名为空 审核直接通过
                if (!$profile['intro']) {
                    $user->getUserModel()->intro = $profile['intro'];
                    $updateDatas['intro'] = $profile['intro'];
                }
            }

            if (array_key_exists('sex', $profile)) {
                $user->getUserModel()->sex = $profile['sex'];
                $updateDatas['sex'] = $profile['sex'];
            }

            if (array_key_exists('birthday', $profile)) {
                $user->getUserModel()->birthday = $profile['birthday'];
                $updateDatas['birthday'] = $profile['birthday'];
            }

            if (array_key_exists('city', $profile)) {
                $user->getUserModel()->city = $profile['city'];
                $updateDatas['city'] = $profile['city'];
            }

            if (count($updateDatas) > 0) {
                UserModelDao::getInstance()->updateDatas($userId, $updateDatas);
                Log::info(sprintf('UpdateProfileOk: userId=%d profile=%s', $userId, json_encode($updateDatas)));
            }

            event(new UserUpdateProfileDomainEvent($user, time()));
            return $user;
        });
    }


    /**
     * @info 是否是删除图片,新上传的背景墙是否包含在旧图中
     * @param $album string  背景墙数据
     * @param $userId int 用户id
     * @return bool
     */
    private function isDeletePhoto($album, $userId)
    {
        if (empty($album)) {
            return true;
        }
        if (empty($userId)) {
            return false;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userKey = $this->user_info_key . $userId;
        $member_album = $redis->hget($userKey, 'album');
        if (empty($member_album)) {
            return false;
        }
        $oldAlbum = explode(",", $member_album);
        $newAlbum = explode(",", $album);
        $oldCount = count($oldAlbum);
        $newCount = count($newAlbum);
        if ($oldCount === 0 || $newCount === 0) {
            return false;
        }
        if ($newCount >= $oldCount) {
            return false;
        }
//        如果旧图和新图有差集，新图不是包含在旧图中则不是删除行为
        $difference = array_diff($newAlbum, $oldAlbum);
        if (!empty($difference)) {
            return false;
        }
        return true;
    }

    private function cancelUserImpl($userId)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $user->cancelUser();
                return $user;
            });
        } catch (Exception $e) {
            Log::error(sprintf('LoginByTokenException userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    private function bindMobileImpl($userId, $mobile)
    {
        try {
            if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
                throw new FQException('此用户不存在', 500);
            }

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $mobile) {
                $bindUserId = AccountMapDao::getInstance()->getUserIdByMobile($mobile);
                if (!empty($bindUserId) && $bindUserId != $userId) {
                    throw new FQException('手机号已经存在', 500);
                }

                AccountMapDao::getInstance()->updateMobile($mobile, $userId);
            });

            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $mobile) {
                $user = UserRepository::getInstance()->loadUser($userId);
                $user->updateMobile($mobile);
                return $user;
            });

        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * 创建云信用户
     *
     * @param $user
     * @return mixed
     */
    private function createYunxinUser($user)
    {
        $acc = YunxinCommon::getInstance()->createUserId($user->getUserId(),
            $user->getUserModel()->nickname,
            '{}',
            $user->getUserModel()->avatar);

        Log::debug(sprintf('UserService::createYunxinUser userId=%d resp=%s',
            $user->getUserId(), json_encode($acc)));

        if (!empty($acc)) {
            $info = ArrayUtil::safeGet($acc, 'info');
            if ($info != null) {
                $accId = ArrayUtil::safeGet($info, 'accid');
                if ($accId != null) {
                    try {
                        return intval($accId);
                    } catch (Exception $e) {
                        Log::warning(sprintf('UserService::createYunxinUser BadAccId userId=%d resp=%s',
                            $user->getUserId(), json_encode($acc)));
                        return 0;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * 检查是否有云信账号
     *
     * @param $user
     */
    private function checkAccId($user)
    {
        if (empty($user->getUserModel()->accId)) {
            // 注册网易云
            $accId = $this->createYunxinUser($user);
            if ($accId != 0) {
                $user->updateAccId(intval($accId));
            }
        }
    }

    /**
     * regist时检查用户注销 如果用户注销了重新注册，要清除用户相应的信息
     *
     * @param $userId
     */
    private function checkUserCancelByRegister($userId)
    {
        if ($userId > 0) {
            if (!UserModelDao::getInstance()->isUserIdNotCancel($userId)) {
                # 该用户已注销
                AccountMapDao::getInstance()->delAccountMap($userId);
                UserInfoMapDao::getInstance()->delUserInfoMap($userId);
                return 0;
            }
        }

        return $userId;
    }

    /**
     * 记录用户登录
     *
     * @param $user
     */
    private function recordLoginDetail($user, $isRegister)
    {
        $clientInfo = $user->getClientInfo();

        $loginDetailNewModel = new LoginDetailNewModel();
        $loginDetailNewModel->userId = $user->getUserId();
        $loginDetailNewModel->channel = $clientInfo->channel;
        $loginDetailNewModel->deviceId = $clientInfo->deviceId;
        $loginDetailNewModel->loginIp = $clientInfo->clientIp;
        $loginDetailNewModel->loginTime = time();
        $loginDetailNewModel->device = $clientInfo->device;
        $loginDetailNewModel->idfa = $clientInfo->idfa;
        $loginDetailNewModel->version = $clientInfo->version;
        $loginDetailNewModel->simulator = $clientInfo->simulator;
        $loginDetailNewModel->imei = $clientInfo->imei;
        $loginDetailNewModel->appId = $clientInfo->appId;
        $loginDetailNewModel->source = $clientInfo->source;
        $loginDetailNewModel->ext_param_1 = $isRegister ? "1" : "0";
        $id = LoginDetailNewModelDao::getInstance()->add($loginDetailNewModel);
        try {
            // 写入队列
            $loginData = LoginDetailNewModelDao::getInstance()->modelToData($loginDetailNewModel);
            $loginData['id'] = (int)$id;
            $strData = json_encode($loginData);
            Log::info(sprintf("recordLoginDetail entry createQueue:%s", $strData));
            AmpQueue::getInstance()->publisher($strData);
        } catch (\Exception $e) {
            Log::error(sprintf("recordLoginDetail ampQueue publisher error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
        }

    }

    /**
     * 登录
     *
     * @param $user
     * @param $clientInfo
     * @param $isRegister
     * @throws Exception
     */
    private function loginImpl($user, $clientInfo, $isRegister, $resetToken = true)
    {
        //注销检测
        if ($user->getUserModel()->isCancel) {
            throw new FQException('用户已注销', 500);
        }
        // 设置clientInfo
        $user->setClientInfo($clientInfo);

        if ($resetToken) {
            $token = TokenService::getInstance()->resetToken($user->getUserId());
            $user->setToken($token);
        } else {
            $token = TokenService::getInstance()->refreshToken($user->getUserId());
            $user->setToken($token);
        }

        // 设置登录时间，登录ip,imei
        $user->updateLoginInfo(time(), $clientInfo->clientIp, $clientInfo->imei);

        // 检查云信是否注册了
        $this->checkAccId($user);

        // 登录记录
        $this->recordLoginDetail($user, $isRegister);

        if (!$isRegister) {
            // 设置密码弹框
            if (CommonCacheService::getInstance()->setUserPwdLayer($user->getUserId())) {
                if (empty($user->getUserModel()->password)) {
                    $user->pwdLayer = 1;
                }
            }
        }
        $user->updateUserLastInfo($clientInfo, $user->getUserId());
        Log::info(sprintf('UserLoginOk userId=%d token=%s',
            $user->getUserId(), $user->getToken()));
        $user->resetCancellation($user->getUserModel());
        $user->checkCancel($user->getUserModel());
        event(new UserLoginDomainEvent($user, time()));
    }

    /**
     * 生成userId
     */
    private function getNextUserId()
    {
        // 最多循环20次，防止进入死循环
        for ($i = 0; $i < 20; $i++) {
            $userId = IdService::getInstance()->getNextUserId();
//            $userId = UserIdCache::getInstance()->getUserId();
            if (!CommonUtil::isPrettyNumber($userId)) {
                return $userId;
            }
        }
        throw new FQException('用户ID生成错误', 500);
    }

    /**
     * 生成用户昵称
     *
     * @param $userId
     * @return string
     */
    private function genNickname($userId)
    {
        return '用户_' . $userId;
    }

    /**
     * 用户注册
     *
     * @param $userModel
     * @param $clientInfo
     * @return int
     */
    private function registerImpl($userModel, ClientInfo $clientInfo)
    {
        $timestamp = time();
        $userModel->userId = $this->getNextUserId();
        $userModel->registerTime = $timestamp;
        $userModel->registerIp = $clientInfo->clientIp;
        $userModel->loginTime = $timestamp;
        $userModel->loginIp = $clientInfo->clientIp;
//        if (empty($userModel->nickname)) {
        $userModel->nickname = $this->genNickname($userModel->userId);
        $userModel->nicknameHash = md5($userModel->nickname);
//        }
        $userModel->registerChannel = $clientInfo->channel;
        $userModel->imei = $clientInfo->imei;
        $userModel->idfa = $clientInfo->idfa;
        $userModel->deviceId = $clientInfo->deviceId;

//        if (empty($userModel->avatar)) {
        // TODO config
        $userModel->avatar = 'Public/Uploads/image/male.png';
//        }
        $userModel->birthday = TimeUtil::timeToStr($timestamp, '%Y-%m-%d');
        $userModel->prettyId = $userModel->userId;
        $userModel->source = $clientInfo->source;
//        $userModel->inviteCode = UserRegisterService::getInstance()->asoTmpl($clientInfo);
        $userModel->inviteCode = UserRegisterService::getInstance()->extend($clientInfo);
        $userModel->registerVersion = $clientInfo->version;
        UserModelDao::getInstance()->saveUserModel($userModel);

        $model = new MemberDetailModel();
        $model->userId = $userModel->userId;
        $model->amount = 0;
        $model->oaid = $clientInfo->oaid;
        $model->createTime = $timestamp;
        $model->updateTime = 0;
        MemberDetailModelDao::getInstance()->storeModel($model);
        return $userModel->userId;
    }

    /**
     * 手机号注册
     */
    private function registerByMobile($mobile, $clientInfo)
    {
        Log::record('clientInfo----' . json_encode($clientInfo));
        Log::record('simulator---' . $clientInfo->simulator);
        if ($clientInfo->simulator == 'true') {
            throw new FQException('模拟器禁止注册，请使用真机注册！', 500);
        }
        if (!empty(AccountMapDao::getInstance()->getUserIdByMobile($mobile))) {
            throw new FQException('此手机号已绑定其他账号', 2000);
        }
        LockService::getInstance()->lock(LockKeys::usernameKey($mobile));
        try {
            $userModel = new UserModel();
            $userModel->username = $mobile;
            $userModel->mobile = $mobile;
            $userId = $this->registerImpl($userModel, $clientInfo);
            AccountMapDao::getInstance()->addByMobile($mobile, $userId);
        } finally {
            LockService::getInstance()->unlock(LockKeys::usernameKey($mobile));
        }
        event(new UserRegisterEvent($userId, time(), $clientInfo));
        return $userId;
    }

    /**
     * 虚拟手机号注册
     * @param $mobile
     * @param $clientInfo
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function registerByVirtualPhone($mobile, $clientInfo)
    {
        if (!empty(AccountMapDao::getInstance()->getUserIdByMobile($mobile))) {
            throw new FQException('此手机号已绑定其他账号', 2000);
        }

        LockService::getInstance()->lock(LockKeys::usernameKey($mobile));
        try {
            $userModel = new UserModel();
            $userModel->username = $mobile;
            $userModel->mobile = $mobile;
            $userId = $this->registerImpl($userModel, $clientInfo);
            AccountMapDao::getInstance()->addByMobile($mobile, $userId);
            return $userId;
        } finally {
            LockService::getInstance()->unlock(LockKeys::usernameKey($mobile));
        }
    }

    /**
     * snsId注册
     *
     * @param $snsId
     * @param $snsType
     * @param $snsInfo
     * @param $clientInfo
     */
    private function registerBySnsId($snsId, $snsType, $snsInfo, $clientInfo)
    {
        if ($clientInfo->simulator == 'true') {
            throw new FQException('模拟器禁止注册，请使用真机注册！', 500);
        }
        LockService::getInstance()->lock(LockKeys::snsKey($snsType, $snsId));
        try {
            $userId = AccountMapDao::getInstance()->getUserIdBySnsType($snsType, $snsId);
            if ($userId > 0) {
                throw new FQException('用户已经注册', 500);
            }
            $userModel = new UserModel();
            if ($snsType == SnsTypes::$QOPENID) {
                $userModel->qopenid = $snsId;
            } elseif ($snsType == SnsTypes::$WXOPENID) {
                $userModel->wxopenid = $snsId;
            } elseif ($snsType == SnsTypes::$APPLEID) {
                $userModel->appleid = $snsId;
            }
            $userId = $this->registerImpl($userModel, $clientInfo);
            AccountMapDao::getInstance()->addBySnsType($snsType, $snsId, $userId);
        } finally {
            LockService::getInstance()->unlock(LockKeys::snsKey($snsType, $snsId));
        }

        event(new UserRegisterEvent($userId, time(), $clientInfo));
        return $userId;
    }

    /**
     * 检查昵称格式以及是否存在
     *
     * @param $userId
     * @param $nickname
     * @throws Exception
     */
    private function checkNickname($userId, $nickname)
    {
        $len = mb_strlen($nickname, 'gb2312');
        if ($len > 28) {
            throw new FQException('昵称不超过14个字', 500);
        }

        $existsUserId = UserInfoMapDao::getInstance()->getUserIdByNickname($nickname);
        if ($existsUserId != null && $existsUserId != $userId) {
            throw new FQException('该用户名昵称已存在', 500);
        }

//        如果不是昵称库
        if (!UserService::getInstance()->isDefaultNickname($nickname)) {
            /* 文本检测 */
            $checkStatus = ShuMeiCheck::getInstance()->textCheck($nickname, ShuMeiCheckType::$TEXT_NICKNAME_EVENT, $userId);
            if (!$checkStatus) {
                throw new FQException('昵称包含敏感字符', 500);
            }
        }

    }

    /**
     * 检查简介
     *
     * @param $userId
     * @param $intro
     * @throws Exception
     */
    private function checkIntro($userId, $intro)
    {
        $len = mb_strlen($intro, 'gb2312');
        if ($len > 60) {
            throw new FQException('个性签名不超过60个字符', 500);
        }
        //内容检测
//        if (!GreenCommon::getInstance()->checkText($intro)) {
//            throw new FQException('简介包含色情或敏感字字符', 2008);
//        }
        /* 文本检测 */
        $checkStatus = ShuMeiCheck::getInstance()->textCheck($intro, ShuMeiCheckType::$TEXT_INTRO_EVENT, $userId);
        if (!$checkStatus) {
            throw new FQException('个性签名包含敏感字符', 500);
        }
    }

    /**
     * 检查简介
     *
     * @param $userId
     * @param $intro
     * @throws Exception
     */
    private function checkCity($userId, $cityStr)
    {
        $len = mb_strlen($cityStr, 'gb2312');
        if ($len > 50) {
            throw new FQException('城市不超过50个字符', 500);
        }

        $originList = explode("-", $cityStr);
        if (count($originList) === 1) {
            $originList = [];
            list($op, $oc) = $this->optimizeCityStr($cityStr);
            $originList[] = $op;
            $originList[] = $oc;
        }
        $origin_province = $originList[0] ?? "";
        $origin_city = $originList[1] ?? "";
        /* 文本检测 */
        return $this->authCityData($origin_province, $origin_city);
    }

    public function checkVoiceTro($userId, $voiceTro)
    {
        if (!ShuMeiCheck::getInstance()->aliAudioCheck($voiceTro)) {
            throw new FQException('语音介绍违反平台规定');
        }
    }

    private function optimizeCityStr($cityStr)
    {
        $confData = $this->loadCityData();
        $provinceData = $this->coveProvinceConf($confData);
        $op = mb_substr($cityStr, 0, 2);
        if (in_array($op, $provinceData)) {
            $oc = mb_substr($cityStr, 2);
            return [$op, $oc];
        }

        $op = mb_substr($cityStr, 0, 3);
        if (in_array($op, $provinceData)) {
            $oc = mb_substr($cityStr, 3);
            return [$op, $oc];
        }
        return ["", ""];
    }


    /**
     * @info 验证省，市数据
     * @param $province
     * @param $city
     * @return bool
     * @throws FQException
     */
    private function authCityData($province, $city)
    {
        $confData = $this->loadCityData();
        $nameDatas = $this->coveProvinceConf($confData);
        if (!in_array($province, $nameDatas)) {
            throw new FQException("province data error", 500);
        }
        $childDatas = $this->coveCityConf($confData);
        if (!in_array($city, $childDatas)) {
            throw new FQException("authCityData city data error", 500);
        }
        return true;
    }

    private function coveCityConf($confData)
    {
        $result = [];
        foreach ($confData as $itemData) {
            $tempData = ArrayUtil::safeGet($itemData, "child");
            foreach ($tempData as $chiildData) {
                $result[] = $chiildData['name'] ?? "";
            }
        }
        return $result;
    }

    private function coveProvinceConf($confData)
    {
        return array_column($confData, "name");
    }


    private function loadCityData()
    {
        return Config::getInstance()->getCityConf();
    }


    /**
     * 检查生日
     *
     * @param $userId
     * @param $birthday
     * @throws Exception
     */
    private function checkBirthday($userId, $birthday)
    {
        $ts = TimeUtil::strToTime($birthday);
        if ($ts == -1 || $ts > time()) {
            throw new FQException('用户生日数据错误', 500);
        }
        $birthdayUnix = strtotime($birthday);
        $teenagerUnix = mktime(10, 0, 0, date('m'), date('d'), date('Y') - 18);
        if ($birthdayUnix > $teenagerUnix) {
            throw new FQException("年龄最小设置为18岁", 500);
        }
    }

    /**
     * 检查头像
     * @param $userId
     * @param $avatar
     * @throws Exception
     */
    public function checkAvatar($userId, $avatar)
    {
        //不是默认头像需要检测数美
        if (!UserService::getInstance()->isDefaultAvatar($avatar)) {
            $checkStatus = ShuMeiCheck::getInstance()->imageCheck($avatar, ShuMeiCheckType::$IMAGE_HEAD_EVENT, $userId);
            if (!$checkStatus) {
                throw new FQException("头像图片违反平台规定", 500);
            }
        }
    }

    /**
     * 实名认证
     */
    public function memberIdentity($userId, $certName, $certNo, $channel, $bizCode, $config)
    {
        try {
            if (!UserModelDao::getInstance()->isUserIdExists($userId)) {
                throw new FQException('用户不存在', 500);
            }

            return Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $certName, $certNo, $channel, $bizCode, $config) {
                $redis = RedisCommon::getInstance()->getRedis();
                $redisKey = 'user_idcard_' . $userId;
                $rdsData = $redis->get($redisKey);
                $t = 24 * 3600;
                if (!empty($rdsData)) {
                    if ($rdsData > 500) {
                        throw new FQException('24小时认证次数超限', 500);
                    }
                    $v = $rdsData + 1;
                    $redis->setex($redisKey, $t, $v);
                } else {
                    $redis->setex($redisKey, $t, 1);
                }

                $outerOrderNo = CommonUtil::createOrderNo($userId);
                $certifyId = AliPayEasyCommon::getInstance()->init($outerOrderNo, $certName, $certNo, $channel, $bizCode, $config);
                if (empty($certifyId)) {
                    throw new FQException("用户信息错误请检查重试", Error::INVALID_PARAMS);
                }
                $model = new UserIdentityModel($userId, $certName, $certNo, $outerOrderNo, $certifyId, 2, time());
                UserIdentityModelDao::getInstance()->addData($model);

                return $certifyId;
            });
        } catch (Exception $e) {
            Log::error(sprintf('memberIdentity userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 查询实名认证
     */
    public function queryIdentity($userId, $certifyId, $deviceId)
    {
        $isTeen = 0;
        $result = AliPayEasyCommon::getInstance()->query($certifyId);
        Log::record(sprintf('identityResponse userId=%d res=%d', $userId, $result->passed));
        if ($result->passed != 'T') { //认证通过 T
            return [false, $isTeen];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                UserModelDao::getInstance()->updateDatas($userId, ['attestation' => 1]);
                event(new CompleteRealUserDomainEvent($user, time()));
            });

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($certifyId, &$isTeen) {
                //业务逻辑
                $model = UserIdentityModelDao::getInstance()->loadByCertifyId($certifyId);
                $model->status = 1;
                UserIdentityModelDao::getInstance()->addData($model);

                // 是否满足16周岁，0:满足16周岁，1: 不满足16周岁
                if (CommonUtil::getAge($model->certno) < 16) {
                    $isTeen = 1;
                }
            });

            $redis->hset('userinfo_' . $userId, 'attestation', 1); //更新缓存中的认证状态
            $redis->hSet('userinfo_' . $userId, 'auth_deviceId', $deviceId);

            return [true, $isTeen];
        } catch (Exception $e) {
            $redis->hDel('userinfo_' . $userId, 'auth_deviceId');
            Log::error(sprintf('memberIdentity userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }


    /**
     * @info 获取在线用户最大数200
     * @param $sex
     * @param int $count
     * @return array|false
     */
    public function getOnlineCacheSex($sex, $count = 200)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf('user_online_%s_list', $sex);
        $re = [];
        $iterator = null;
        while ($members = $redis->zscan($cacheKey, $iterator, null, 50)) {
            foreach ($members as $member => $score) {
                // 过滤隐身在线的用户
                if (!$redis->sIsMember('user_hidden_online', $member)) {
                    $re[] = $member;
                }
            }
            if (count($re) >= $count) {
                break;
            }
        }
        $re = array_slice($re, 0, $count);
        if (config('config.appDev') != "dev") {
            return $re;
        }
        if ($sex == 'all') {
            $this->storeFeature($re);
        }
        if ($sex == '1') {
            $this->storeFeatureMan($re);
        }
        if ($sex == '2') {
            $this->storeFeatureWeMan($re);
        }
        return $re;
    }


    private function storeFeatureWeMan(&$re)
    {
        $result = array(
            0 => 103,
            1 => 1037326,
            2 => 1039972,
            3 => 1040294,
            4 => 1040922,
            5 => 1049323,
            6 => 1049497,
            7 => 1061754,
            8 => 1061778,
            9 => 1061786,
            10 => 1075767,
            11 => 1088508,
            12 => 1094795,
            13 => 1099510,
            14 => 1099995,
            15 => 1110473,
            16 => 1116816,
            17 => 1121548,
            18 => 1152166,
            19 => 1180641,
            20 => 1181728,
            21 => 1191705,
            22 => 1204890,
            23 => 1219936,
            24 => 1235078,
            25 => 1241623,
            26 => 1249826,
            27 => 1249860,
            28 => 1252597,
            29 => 1299430,
            30 => 1300030,
            31 => 1304216,
            32 => 1326948,
            33 => 1335540,
            34 => 1335940,
            35 => 1337426,
            36 => 1337532,
            37 => 1346011,
            38 => 1352308,
            39 => 1359062,
            40 => 1367793,
            41 => 1372754,
            42 => 1374358,
            43 => 1376227,
            44 => 1379371,
            45 => 1379604,
            46 => 1379654,
            47 => 1380343,
            48 => 1381855,
            49 => 1398504,
            50 => 1398569,
            51 => 1399073,
            52 => 1399423,
            53 => 1400408,
            54 => 1401397,
            55 => 1405435,
            56 => 1405720,
            57 => 1405829,
            58 => 1407249,
            59 => 1407664,
            60 => 1407920,
            61 => 1408028,
            62 => 1408828,
            63 => 1410169,
            64 => 1410535,
            65 => 1412707,
            66 => 1414635,
            67 => 1414969,
            68 => 1415136,
            69 => 1415203,
            70 => 1415238,
            71 => 1416140,
            72 => 1416854,
            73 => 1418222,
            74 => 1420288,
            75 => 1420928,
            76 => 1428453,
            77 => 1432468,
            78 => 1432481,
            79 => 1432482,
            80 => 1432504,
            81 => 1433470,
            82 => 1433829,
            83 => 1434158,
            84 => 1434424,
            85 => 1434426,
            86 => 1434537,
            87 => 1434634,
            88 => 1434723,
            89 => 1434754,
            90 => 1435064,
            91 => 1435284,
            92 => 1435286,
            93 => 1435470,
            94 => 1435502,
            95 => 1435564,
            96 => 1435671,
        );
        $re = array_merge($re, $result);
    }

    private function storeFeatureMan(&$re)
    {
        $result = array(
            0 => 1027193,
            1 => 1030120,
            2 => 1040800,
            3 => 1067334,
            4 => 1068103,
            5 => 1076710,
            6 => 1099144,
            7 => 1099615,
            8 => 1113951,
            9 => 1134112,
            10 => 1140001,
            11 => 1147127,
            12 => 1170634,
            13 => 1172610,
            14 => 1181895,
            15 => 1187180,
            16 => 1191465,
            17 => 1202615,
            18 => 1262780,
            19 => 1273991,
            20 => 1300558,
            21 => 1304863,
            22 => 1307768,
            23 => 1312670,
            24 => 1323626,
            25 => 1328869,
            26 => 1335699,
            27 => 1337633,
            28 => 1353329,
            29 => 1358644,
            30 => 1360282,
            31 => 1360939,
            32 => 1362141,
            33 => 1365797,
            34 => 1370620,
            35 => 1371210,
            36 => 1374361,
            37 => 1374655,
            38 => 1383249,
            39 => 1386714,
            40 => 1388199,
            41 => 1392309,
            42 => 1392487,
            43 => 1398551,
            44 => 1403324,
            45 => 1406874,
            46 => 1408941,
            47 => 1412764,
            48 => 1413571,
            49 => 1413899,
            50 => 1415592,
            51 => 1415684,
            52 => 1418162,
            53 => 1424011,
            54 => 1425211,
            55 => 1426529,
            56 => 1429170,
            57 => 1430811,
            58 => 1431354,
            59 => 1431752,
            60 => 1432765,
            61 => 1433508,
            62 => 1433519,
            63 => 1433839,
            64 => 1434560,
            65 => 1434583,
            66 => 1434842,
            67 => 1435072,
            68 => 1435074,
            69 => 1435139,
            70 => 1435198,
            71 => 1435285,
            72 => 1435295,
            73 => 1435576,
            74 => 1435598,
            75 => 1435630,
            76 => 1435632,
            77 => 1435639,
            78 => 1435654,
            79 => 1435662,
            80 => 1435664,
            81 => 1435665,
            82 => 1435670,
            83 => 1435672,
            84 => 1435673,
            85 => 1439778,
        );
        $re = array_merge($re, $result);
    }

    private function storeFeature(&$re)
    {
        $result = array(
            0 => 1435284,
            1 => 1435630,
            2 => 1435139,
            3 => 1359062,
            4 => 1386714,
            5 => 1435639,
            6 => 1435598,
            7 => 1181895,
            8 => 1435285,
            9 => 1328869,
            10 => 1414969,
            11 => 1412764,
            12 => 1415592,
            13 => 1061754,
            14 => 1434754,
            15 => 1304216,
            16 => 1415684,
            17 => 1431752,
            18 => 1335540,
            19 => 1134112,
            20 => 1431354,
            21 => 1403324,
            22 => 1434583,
            23 => 1418162,
            24 => 1428453,
            25 => 1027193,
            26 => 1420288,
            27 => 1435286,
            28 => 1388199,
            29 => 1412707,
            30 => 1434723,
            31 => 1435665,
            32 => 1434537,
            33 => 1435654,
            34 => 1360939,
            35 => 1415238,
            36 => 1392487,
            37 => 1435502,
            38 => 1335940,
            39 => 1337633,
            40 => 1434158,
            41 => 1406874,
            42 => 1219936,
            43 => 1353329,
            44 => 1337532,
            45 => 1181728,
            46 => 1116816,
            47 => 1362141,
            48 => 1312670,
            49 => 1094795,
            50 => 1040800,
            51 => 1399073,
            52 => 1435664,
            53 => 1147127,
            54 => 1358644,
            55 => 1374361,
            56 => 1380343,
            57 => 1299430,
            58 => 1379371,
            59 => 1435295,
            60 => 1407664,
            61 => 1323626,
            62 => 1191465,
            63 => 1410535,
            64 => 1068103,
            65 => 1110473,
            66 => 1088508,
            67 => 1432765,
            68 => 1367793,
            69 => 1170634,
            70 => 1435072,
            71 => 1435470,
            72 => 1434560,
            73 => 1061778,
            74 => 1365797,
            75 => 1304863,
            76 => 1099995,
            77 => 1030120,
            78 => 1415136,
            79 => 1249860,
            80 => 1049497,
            81 => 1346011,
            82 => 1433470,
            83 => 1408828,
            84 => 1252597,
            85 => 1392309,
            86 => 1039972,
            87 => 1381855,
            88 => 1424011,
            89 => 1140001,
            90 => 1040922,
            91 => 1049323,
            92 => 1300558,
            93 => 1410169,
            94 => 1434424,
            95 => 1372754,
            96 => 1202615,
            97 => 1420928,
            98 => 1432482,
            99 => 1400408,
            100 => 1415203,
            101 => 1172610,
            102 => 1204890,
            103 => 1426529,
            104 => 1430811,
            105 => 1335699,
            106 => 1435064,
            107 => 1113951,
            108 => 1191705,
            109 => 1439778,
            110 => 1434426,
            111 => 1432481,
            112 => 1398551,
            113 => 1180641,
            114 => 1407920,
            115 => 1433839,
            116 => 1099510,
            117 => 1418222,
            118 => 1187180,
            119 => 1352308,
            120 => 1371210,
            121 => 1432468,
            122 => 1434842,
            123 => 1241623,
            124 => 1413899,
            125 => 1408028,
            126 => 1405720,
            127 => 1076710,
            128 => 1061786,
            129 => 1434634,
            130 => 1370620,
            131 => 1099615,
            132 => 1300030,
            133 => 1433519,
            134 => 1432504,
            135 => 1121548,
            136 => 1435670,
            137 => 1433508,
            138 => 1408941,
            139 => 1414635,
            140 => 1037326,
            141 => 1433829,
            142 => 1067334,
            143 => 1435673,
            144 => 1413571,
            145 => 1399423,
            146 => 1374358,
            147 => 1407249,
            148 => 1398504,
            149 => 1307768,
            150 => 1379604,
            151 => 1326948,
            152 => 1075767,
            153 => 1435672,
            154 => 103,
            155 => 1379654,
            156 => 1040294,
            157 => 1398569,
            158 => 1360282,
            159 => 1425211,
            160 => 1376227,
            161 => 1262780,
            162 => 1435632,
            163 => 1416854,
            164 => 1435074,
            165 => 1337426,
            166 => 1416140,
            167 => 1383249,
            168 => 1401397,
            169 => 1099144,
            170 => 1249826,
            171 => 1273991,
            172 => 1152166,
            173 => 1435564,
            174 => 1435576,
            175 => 1235078,
            176 => 1405829,
            177 => 1405435,
            178 => 1435671,
            179 => 1374655,
            180 => 1435198,
            181 => 1435662,
            182 => 1429170,
        );
        $re = array_merge($re, $result);
    }

    /**
     * 获取用户状态
     * 0 注销 1正常 2 封禁
     */
    public function getUserStatus($userModel)
    {
        $isBan = $userModel->isCancel;   //0: 未注销 1: 注销
        //获取用户是否被封禁
        $userBlackModel = UserBlackModelDao::getInstance()->loadData($userModel->userId);
        if (($userBlackModel && $userBlackModel->status == 1) && ($userBlackModel->endTime >= time() || $userBlackModel->endTime == -1)) {
            $isBan = 2;
        }

        if ($isBan == 0) {
            $isBan = 1;
        }
        return $isBan;
    }


    /**
     * @param $userId
     * @return array|false[]
     * @throws Exception
     */
    public function updateUserQuitGuild($userId)
    {
        if (empty($userId)) {
            return [false, false];
        }
        try {
            $socityRe = Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId) {
                //        - 清理用户的加入工会申请记录
                return MemberSocityModelDao::getInstance()->removeForId($userId);
            });

            $memberRe = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId) {
                // 操作用户退出 工会
                return UserModelDao::getInstance()->quitGuildForId($userId);
            });

            return [$socityRe, $memberRe];
        } catch (Exception $e) {
            Log::error(sprintf('updateUserQuitGuild userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }

    }

    /**
     * @desc 组装个人资料中语音信息
     * @param $voiceIntro
     * @param $voiceTime
     * @return array
     */
    public function formatVoiceAudit($voiceIntro, $voiceTime)
    {
        return [
            'voiceIntro' => $voiceIntro,
            'voiceTime' => $voiceTime,
        ];
    }

    /**
     * @desc 获取个人资料中语音数据
     * @param $content
     * @return array
     */
    public function formatContentToVoice($content)
    {
        if (!$content) {
            return [];
        }
        $voice = json_decode($content, true);
        $voiceIntro = ArrayUtil::safeGet($voice, 'voiceIntro');
        $voiceTime = ArrayUtil::safeGet($voice, 'voiceTime');
        if ($voiceIntro && $voiceTime) {
            return $this->formatVoiceAudit($voiceIntro, $voiceTime);
        }
        return [];
    }

    /**
     * @desc 个人资料需要审核的状态信息
     * @param $userId
     * @return array
     */
    public function getAuditActions($userId)
    {
        $auditActions = MemberDetailAuditActionModel::getShowAuditActions();
        $auditDetailActions = [];
        foreach ($auditActions as $action) {
            $auditDetailModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userId, $action);
            $auditDetailActions[$action]['status'] = (int)$auditDetailModel->status;
            // 如果审核表中不存在，默认为审核成功
            if (!$auditDetailModel->userId) {
                $auditDetailActions[$action]['status'] = 1;
            }
        }
        return $auditDetailActions;
    }

    /**
     * @desc 修改的数据- 是否存在需要审核的字段，未审核的字段不能修改
     * @param $userId
     * @param $profile
     * @throws FQException
     */
    public function isAuditEditProfile($userId, $profile)
    {
        // 未审核状态不能修改
        $auditActions = MemberDetailAuditActionModel::getShowAuditActions();
        foreach ($profile as $key => $value) {
            if (array_key_exists('voiceIntro', $profile)) {
                $key = 'voice';
            }
            if (in_array($key, $auditActions)) {
                // 未审核不能修改
                $isMemberDetailNotAudit = MemberDetailAuditDao::getInstance()->isMemberDetailNotAudit($userId, $key);
                if ($isMemberDetailNotAudit) {
                    throw new FQException('审核通过后才能进行修改', 500);
                }
            }
        }
    }

    /**
     * @param $userId
     * @return bool|int
     * @throws FQException
     */
    public function innerAddYsUser($userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $invisUserKey = "invis_user";
        $issetUser = $redis->sIsMember($invisUserKey, $userId);
        if ($issetUser) {
            throw new FQException("用户已经存在", 500);
        }
        $data = [];
        $data['role'] = 3;
        $updataRe = UserModelDao::getInstance()->updateDatas($userId, $data);
        $result = false;
        if ($updataRe) {
            $result = $redis->sAdd($invisUserKey, $userId);
        }
        return $result;
    }


    /**
     * @param $userId
     * @return false|int
     * @throws FQException
     */
    public function innerDelYsUser($userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常", 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $invisUserKey = "invis_user";
        $issetUser = $redis->sIsMember($invisUserKey, $userId);
        if (!$issetUser) {
            throw new FQException("不是隐身用户无法删除", 500);
        }
        $data = [];
        $data['role'] = 1;
        $updataRe = UserModelDao::getInstance()->updateDatas($userId, $data);
        $result = false;
        if ($updataRe) {
            $result = $redis->sRem($invisUserKey, $userId);
        }
        return $result;
    }

    /**
     * @param $userId
     * @param $invitcode
     * @return \app\core\model\BaseModel
     * @throws FQException
     */
    public function innerUpdateUserInvitcode($userId, $invitcode)
    {
        if (empty($userId) || empty($invitcode)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $data['invitcode'] = $invitcode;
        $result = UserModelDao::getInstance()->updateDatas($userId, $data);

        event(new UserUpdateProfileEvent($userId, $data, time()));
        return $result;
    }


    /**
     * @param $username
     * @param $password
     * @param $sex
     * @return int
     * @throws FQException
     */
    public function innerAddUser($username, $password, $sex)
    {
        if (empty($username) || empty($password)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        if (!empty(AccountMapDao::getInstance()->getUserIdByMobile($username))) {
            throw new FQException('此手机号已绑定其他账号', 2000);
        }
        $clientInfo = new ClientInfo();
        LockService::getInstance()->lock(LockKeys::usernameKey($username));

        try {
            $userModel = new UserModel();
            $userModel->username = $username;
            $userModel->mobile = $username;
            $userId = $this->registerImpl($userModel, $clientInfo);
            AccountMapDao::getInstance()->addByMobile($username, $userId);
        } finally {
            LockService::getInstance()->unlock(LockKeys::usernameKey($username));
        }
        if (empty($userId)) {
            throw new FQException("innerAddUser register error", 500);
        }

        $data = [];
        $data['password'] = $password;
        $data['sex'] = $sex;
        $data['pretty_id'] = $userId;
        $data['nickname'] = sprintf("用户_%d", $userId);
        $data['register_time'] = date('Y-m-d H:i:s');
        UserModelDao::getInstance()->updateDatas((int)$userId, $data);
        UserInfoMapDao::getInstance()->updatePretty($userId, $userId);
        UserInfoMapDao::getInstance()->updateNickname($data['nickname'], $userId);

        event(new UserRegisterEvent($userId, time(), $clientInfo));
        return $userId;
    }

    /**
     * @Info 修改网易云数据
     * @param UserModel $userModel
     * @return array|string
     */
    public function upYunxinUserInfo(UserModel $userModel)
    {
        $yunResult = YunxinCommon::getInstance()->getUinfos([$userModel->userId]);
        if ($yunResult) {
            $result = YunxinCommon::getInstance()->updateUinfo($userModel->accId, $userModel->nickname, CommonUtil::buildImageUrl($userModel->avatar), $userModel->intro, "", $userModel->birthday, $userModel->mobile, $userModel->sex);
        } else {
            $result = "getUinfos error";
        }
        Log::info(sprintf('UserService::upYunxinUserInfo userId=%d responseRe:%s',
            $userModel->userId, json_encode($result)));
        return $result;
    }


    /**
     * @param $userId
     * @return \app\core\model\BaseModel|int
     * @throws FQException
     */
    public function resetAttention($userId)
    {
        if (empty($userId)) {
            throw new FQException("param error", 500);
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常");
        }
        $lockKey = LockKeys::resetAttentionKey($userId);
        LockService::getInstance()->lock($lockKey);
        try {
//        load 提现记录表 看用户是否存在待提现提现记录， 返回id主键 find one
            $issetId = UserWithdrawDetailModelDao::getInstance()->existsAuditOrderForUserId($userId);
            if (!empty($issetId)) {
                throw new FQException('当前用户存在未处理的提现订单，不支持清空实名', 500);
            }
//            用户状态认证成功修改为认证失败
            $upRe = UserModelDao::getInstance()->resetUserAttention($userId);
            if ($upRe === 0) {
                throw new FQException("update user status error", 500);
            }
//            修改用户认证信息记录，从1成功改为失败0
            $identityRe = UserIdentityModelDao::getInstance()->resetIdentityStatus($userId, UserIdentityStatusModel::$ERROR);
//            如果没有修改成功，并且是老用户，从1成功修改为失败0
            if ($identityRe === 0) {
                $identityRe = UserCardModelDao::getInstance()->resetIdentityStatus($userId, UserIdentityStatusModel::$ERROR);
            }
//            清理公众号用户提现 zb_user_withdraw_info 表，将用户数据存入zb_user_withdraw_info_log 清理原表中数据
            $cleanRe = AgentPayService::getInstance()->cleanwithdrawStoreLog($userId);
//        清理用户提现的银行信息 zb_user_withdraw_bank_information 表
            $delRe = UserWithdrawBankInformationModelDao::getInstance()->deleteForUserId($userId);
            Log::info(sprintf('UserService::resetAttention userId=%d userUpRe:%d identityRe:%d cleanwithdrawStoreLog:%d deletewithdrawBankInfo%d', $userId, $upRe, $identityRe, $cleanRe, $delRe));
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
        $profile['attestation'] = 0;
        event(new UserUpdateProfileEvent($userId, $profile, time()));
        return $upRe;
    }

}
