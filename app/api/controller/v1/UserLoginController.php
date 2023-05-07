<?php

namespace app\api\controller\v1;

use app\domain\activity\recall\RecallService;
use app\domain\exceptions\FQException;
use app\domain\feedback\dao\LoginFeedbackModelDao;
use app\domain\feedback\model\LoginFeedbackModel;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserInfoService;
use app\domain\user\service\UserService;
use app\form\ClientInfo;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use app\service\ThirdLoginService;
use app\utils\AdminAuthTrait;
use app\utils\CommonUtil;
use app\utils\Error;
use app\view\UserView;
use \app\facade\RequestAes as Request;
use think\facade\Log;
use app\BaseController;
use app\common\model\RegisterDataModel;
use Exception;

class UserLoginController extends BaseController
{
    use AdminAuthTrait;

    private $loginTypeMap = [2 => 'qopenid', 3 => 'wxopenid', 5 => 'appleid'];

    //判断绑定手机号
    public function checkbindMobile()
    {
        $userId = intval($this->headUid);
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if ($userModel != null) {
            return rjson(['username' => $userModel->username]);
        } else {
            return rjson();
        }
    }

    //新用户绑定手机号
    public function userBindMobile()
    {
        $username = Request::param('username');
        $verifyCode = Request::param('verify');
        $username = trim($username);
        $isgetui = Request::param('isgetui');
        $gytoken = Request::param('gytoken');
        $gyuid = Request::param('gyuid');

        $userId = intval($this->headUid);

        try {
            if ($isgetui == 1) {
                $username = UserService::getInstance()->bindGetui($userId, $gytoken, $gyuid, $this->config);
            } else {
                UserService::getInstance()->bindMobile($userId, $username, $verifyCode);
            }
            return rjson($username,200,'绑定成功');
        } catch (FQException $e) {
            return rjson([], 500, $e->getMessage());
        }
    }

    //注销账户
    public function cancelUser()
    {
        $type = Request::param('type');
        $userId = intval($this->headUid);
        try {
            if ($type == 1) {    //验证是否可以注销账户
                UserService::getInstance()->checkCancelUser($userId);
                return rjson([], 200, '验证通过');
            } else {    // 确认注销账户
                $mobile = UserModelDao::getInstance()->getBindMobile($userId);
                $verify = Request::param('verify');
                UserService::getInstance()->cancelUser($userId, $mobile, $verify, $this->headToken);
                $user = UserModelDao::getInstance()->loadUserModel($userId);
                $data = [
                    'cancelTime' => UserService::getInstance()->getCancelExpiresTime($user),
                ];
                return rjson($data, 200, 'success');
            }
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    //查询三方登录信息
    public function thirdInfo()
    {
        $thirdId = Request::param('third_id');
        $userId = intval($this->headUid);

        $info = CommonCacheService::getInstance()->getUserThirdInfo($userId, $thirdId);

        return rjson($info);
    }

    //完善用户信息
    public function perfectUserInfo()
    {
        $userId = intval($this->headUid);
        $birthday = Request::param('birthday');
        $sex = Request::param('sex', 1);
        $nickname = trim(Request::param('nickname'));
        $nickname = !empty($nickname) ? $nickname : '用户_' . $userId;
        $avatar = Request::param('avatar');
        $inviteCode = Request::param('invitcode');
        $simulator = empty(Request::param('simulator')) ? false : Request::param('simulator');

        if ($simulator == 'true') {
            return rjson([], 500, '模拟器禁止注册，请使用真机注册！');
        }

        if (empty($sex)) {
            return rjson([], 500, '您还未选择性别');
        }

        if ($sex != null) {
            $data['sex'] = intval($sex);
        }
        if ($birthday != null) {
            $data['birthday'] = $birthday;
        }
        if ($avatar != null) {
            $avatar = str_replace(config("config.APP_URL_image"), "", $avatar);
            $avatar = str_replace(config("config.APP_URL_image_two"), "", $avatar);
            $data['avatar'] = $avatar;
        } else {
            $data['avatar'] = $data['sex'] == 1 ? 'Public/Uploads/image/male.png' : 'Public/Uploads/image/female.png';
        }
        if ($nickname != null) {
            $data['nickname'] = $nickname;
        }
        if ($inviteCode != null) {
            $data['invitecode'] = $inviteCode;
        }
        try {
            $user = UserService::getInstance()->perfectUserInfo($userId, $data);
            $user->isRegister = true;
            return rjson(['info' => UserView::viewUser($user, $this->source, $this->version, $this->channel)], 200);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //登录
    public function login()
    {
        $params = Request::param();
        $type = Request::param('type');

        if (!$type) {
            return rjson([], 500, '参数错误');
        }

        $type = intval($type);
        try {
            $clientInfo = new ClientInfo();
            $clientInfo->fromRequest($this->request);
            $clientInfo->simulatorInfo = isset($params['simulator_info']) ? $params['simulator_info'] : '';
            if ($type == 1) {
                // 手机登录
                $user = UserService::getInstance()->loginByMobile($params['username'], $params['vertify'], $clientInfo);
            } elseif ($type == 4) {
                //账号密码登录
                $password = base64_decode(Request::param('pwd'));
                $user = UserService::getInstance()->loginByPassword($params['username'], $password, $clientInfo);
            } elseif ($type == 2 || $type == 3 || $type == 5) {
                // 苹果等第三方登录
                if ($type == 5) {
                    $appleUid = $params['appleuid'];
                    $appleToken = $params['third_id'];
                    $snsInfo = ThirdLoginService::getInstance()->appleLogin($this->appId, $appleUid, $appleToken);
                } else {
                    $snsInfo = ThirdLoginService::getInstance()->thirdLogin($params['third_id'], $type, $this->config);
                }
                if (empty($snsInfo)) {
                    return rjson([], 500, '用户信息错误',[
                        'function'  => 'login',
                        'extra'     => [
                            'type'      => $type,
                            'deviceId'  => $clientInfo->deviceId
                        ]
                    ]);
                }
                $user = UserService::getInstance()->loginBySnsId($snsInfo['snsId'], $type, $snsInfo, $clientInfo);
            } elseif ($type == 6) {
                // 手机号一键登录
                if (empty($params['vertify']) || empty($params['third_id'])) {
                    return rjson([], 500, '一键登录错误请重试',[
                        'function'  => 'login',
                        'extra'     => [
                            'type'      => $type,
                            'deviceId'  => $clientInfo->deviceId
                        ]
                    ]);
                }

                $snsInfo = ThirdLoginService::getInstance()->getuiLogin($params['vertify'], $params['third_id'], $this->config);

                $user = UserService::getInstance()->loginByAutoMobile($snsInfo['snsId'], $clientInfo);
            } else {
                throw new Exception('错误的登录类型', 500);
            }
            return rjson(['info' => UserView::viewUser($user, $this->source, $this->version, $this->channel)], 200,'登录成功',[
                'function'  => 'login',
                'extra'     => [
                    'type'      => $type,
                    'userId'    => $user->getUserId(),
                    'isRegister'=> $user->isRegister,
                    'deviceId'  => $clientInfo->deviceId
                ]
            ]);
        } catch (FQException $e) {
            Log::warning(sprintf('UserLoginController::login type=%d ex=%d:%s',
                $type, $e->getCode(), $e->getMessage()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 自动登录接口
     */
    public function autologin()
    {
        $params = Request::param();
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        $clientInfo->simulatorInfo = isset($params['simulator_info']) ? $params['simulator_info'] : '';
        $token = Request::header('TOKEN');

        Log::info(sprintf('AutoLogin deviceId=%s version=%s channel=%s platform=%s source=%s token=%s',
            $clientInfo->deviceId, $clientInfo->version, $clientInfo->channel, $clientInfo->platform,
            $clientInfo->source, $token));

        if (empty($token)) {
            RegisterDataModel::getInstance()->StoreDeviceData($clientInfo);
            throw new FQException('token不存在',5000);
        }

        try {
            $user = UserService::getInstance()->loginByToken($token, $clientInfo);
            return rjson(['info' => UserView::viewUser($user, $this->source, $this->version, $this->channel)], 200);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 登录反馈
     */
    public function loginFeedBack()
    {
        $phone = Request::param('phone');
        //手机号验证
        CommonUtil::validateMobile($phone);

        $model = new LoginFeedbackModel();
        $model->account = Request::param('account');
        $model->phone = $phone;
        $model->problem = Request::param('problem');
        $model->mode = Request::param('mode');
        $model->createTime = time();
        LoginFeedbackModelDao::getInstance()->addFeedback($model);
        return rjson([], 200, '提交成功');
    }

    public function reCallReWard()
    {
        $userId = intval($this->headUid);
        try {
            $rewards = RecallService::getInstance()->recallReward($userId);
            $ret = [];
            foreach ($rewards as $reward) {
                $ret[] = [
                    'image' => CommonUtil::buildImageUrl($reward->content->img),
                    'title' => $reward->content->name
                ];
            }
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * @info 后台头像/昵称/个性签名/背景墙/语音 审核
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberDetailAudit()
    {
        $operatorId = $this->checkAuthInner();
        $id = Request::param('mdaId', 0, 'intval');
        $status = Request::param('status', 0, 'intval');
        $sign = Request::param('sign', '');
        $time = Request::param('time', 0, 'intval');
        if (empty($sign) || empty($id) || empty($status) || empty($time)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
//        auth sign
        $salt = 'registerfanqie';
        $authSign = md5(sprintf("%s%s", $salt, $time));
        if ($authSign != $sign) {
            throw new FQException("fatal error sign error");
        }
        $result = UserInfoService::getInstance()->memberDetailAuditHandler($id, $status, $operatorId);
        if (empty($result)) {
            return rjson([], 500, '操作失败，请检查并重试');
        }
        return rjson([], 200, 'success');
    }
}


