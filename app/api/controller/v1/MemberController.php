<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\common\RedisCommon;
use app\query\backsystem\dao\MarketChannelModelDao;
use app\domain\exceptions\FQException;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserReportService;
use app\domain\user\service\UserService;
use app\form\ClientInfo;
use app\service\ThirdLoginService;
use app\service\TokenService;
use app\service\VerifyCodeService;
use app\utils\CommonUtil;
use app\view\UserView;
use Exception;
use \app\facade\RequestAes as Request;

class MemberController extends BaseController
{
//    protected $supported_type = array('qq', 'wechat');      //QQ与微信登录
//    private $blackIp = ['183.92.251.227'];
//    private $check_username = "/^1\d{10}$/";                //手机号检测
//    private $user_code_key = 'verify_code_';            //验证码
//    private $token_expires_time = "864000";             //过期时间
//    private $userKey = "userinfo_";                     //存储用户数据表
//    private $loginKey = "login_time_ip";                //登录时间
//    private $current_room_key = "user_current_room";                      //房间昵称
//    private $RegistUidPwd = "RegistUidPwd";                      //密码uid缓存

    //验证换绑验证码
    public function verifyMobile()
    {
        $verifyCode = Request::param('verify');
        $userId = $this->headUid;
        $mobile = UserModelDao::getInstance()->getBindMobile($userId);
        if (config('config.VERTIFYCODE')
            && !VerifyCodeService::getInstance()->checkVerifyCode($mobile, $verifyCode)) {
            return rjson([], 500, '验证码不正确');
        }
        return rjson();
    }

    /**
     * 设置手机号
     */
    public function setMobile()
    {
        $mobile = Request::param('mobile');
        $verifyCode = Request::param('verify');
        $userId = $this->headUid;
        if (substr_count($mobile, '*')) {
            $mobile = UserModelDao::getInstance()->getBindMobile($userId);
        }
        try {
            UserService::getInstance()->setMobile($this->headUid, $mobile, $verifyCode);
            return rjson([], 200, '绑定成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 忘记密码
     */
    public function forgetPassword()
    {
        $verifyCode = Request::param('vertify');
        $password = Request::param('pwd');
        $password = base64_decode($password);
        $userId = $this->headUid;
        $mobile = null;
        if (!empty($userId)) {
            $mobile = UserModelDao::getInstance()->getBindMobile($userId);
        }
        if (is_null($mobile)) {
            $mobile = Request::param('mobile');
        }
        try {
            UserService::getInstance()->forgetPassword($mobile, $verifyCode, $password);
            return rjson();
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 设置密码
     */
    public function setPassword()
    {
        $newPassword = Request::param('pwd');
        $oldPassword = Request::param('oldpwd');
        $token = Request::param('token');
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $userId = TokenService::getInstance()->getUserIdByToken($token);
        if ($userId <= 0) {
            return rjson([], 500, '用户信息错误');
        }
        $newPassword = base64_decode($newPassword);

        try {
            if (UserService::getInstance()->setPassword($userId, $oldPassword, $newPassword)) {
                return rjson([], 200, '密码设置成功');
            }
            return rjson([], 200, '密码修改成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //登录
    public function login()
    {
        return rjson([], 500, '请更新新版本');

        $params = Request::param();
        $type = Request::param('type');

        if (!$type) {
            return rjson([], 500, '参数错误');
        }

        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        try {
            if ($type == 1) {
                $user = UserService::getInstance()->loginByMobile($params['username'], $params['vertify'], $clientInfo);
            } elseif ($type == 4) {
                $password = base64_decode(Request::param('pwd'));
                $user = UserService::getInstance()->loginByPassword($params['username'], $password, $clientInfo);
            } else if ($type == 2 || $type == 3 || $type == 5) {
                if ($type == 5) {
                    $appleUid = $params['appleuid'];
                    $appleToken = $params['third_id'];
                    $snsInfo = ThirdLoginService::getInstance()->appleLogin($this->appId, $appleUid, $appleToken);
                } else {
                    $snsInfo = ThirdLoginService::getInstance()->thirdLogin($params['third_id'], $type, $this->config);
                }
                if (empty($snsInfo)) {
                    return rjson([], 500, '用户信息错误');
                }
                $user = UserService::getInstance()->loginBySnsId($snsInfo['snsId'], $type, $snsInfo, $clientInfo);
            } else {
                throw new Exception('错误的登录类型', 500);
            }
            return rjson(['info' => UserView::viewUser($user, $this->source, $this->version, $this->channel)], 200);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getRegisterRoomId($user)
    {
        if (!empty($user->getUserModel()->inviteCode)) {
            $inviteRoomId = MarketChannelModelDao::getInstance()->getRoomIdByInviteCode($user->getUserModel()->inviteCode);
            if ($inviteRoomId != 0) {
                return [$inviteRoomId];
            }
        }
        // 设置注册进房间
        return RedisCommon::getInstance()->SMEMBERS('regist_roomid');
    }

    /**
     * @param $token    token值
     * @param $profile     修改用户信息
     */
    public function edit()
    {
        //获取数据
        $token = Request::param('token');
        $profile = Request::param('profile');

        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 5000, '用户不存在');
        }

        $userId = intval($userId);

        //根据用户id修改用户属性
        $profile = json_decode($profile, true);
        if (empty($profile)) {
            return rjson([], 5000, '数据错误');
        }

        try {
            $user = UserService::getInstance()->editProfile($userId, $profile, $this->channel, $this->version);
            $userModel = $user->getUserModel();
            $result = [
                'sex' => $userModel->sex,
                'userid' => $user->getUserId(),
                'username' => $userModel->username,
                'nickname' => $userModel->nickname,
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                'attestation' => $userModel->attestation,
                'city' => $userModel->city,
                'twelve_animals' => birthext($userModel->birthday)
            ];
            return rjson($result, 200, '修改成功',[
                'function'  => 'editUser',
                'extra'     => [
                    'userId' => $user->getUserId(),
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage(),[
                'function'  => 'editUser',
                'extra'     => [
                    'userId' => $userId,
                ]
            ]);
        }
    }

    public function reportUser()
    {
        $toUserId = intval(Request::param('to_uid'));
        $contents = Request::param('contents');
        $userId = intval($this->headUid);

        try {
            UserReportService::getInstance()->reportUser($userId, $toUserId, $contents);
            return rjson([], 200, '举报成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


}