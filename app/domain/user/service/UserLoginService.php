<?php


namespace app\domain\user\service;

use app\common\model\RegisterDataModel;
use app\common\RedisCommon;
use app\common\SmsMessageCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepositoryWithdraw;
use app\domain\user\UserWithdraw;
use app\form\ClientInfo;
use app\service\BlackService;
use app\service\VerifyCodeService;
use app\service\WithdrawTokenService;
use app\utils\CommonUtil;
use think\Exception;
use think\facade\Log;


/**
 * 用户服务接口
 */
class UserLoginService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserLoginService();
        }
        return self::$instance;
    }

    /**
     * @param ClientInfo $clientInfo
     * @param $headUid
     * @param $phone
     * @param $type
     * @return mixed
     * @throws FQException
     */
    public function serviceSendSms(ClientInfo $clientInfo, $headUid, $phone, $type)
    {
        //        mark deviceid send sms type
        if ($type == 1) {
            RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $clientInfo->deviceId, 'date' => date('Y-m-d')])->update(['send_sms' => 1]);
        }
        $uid = $headUid;
//        resetPhone
        if (substr_count($phone, '*') && $uid) {
            $phone = UserModelDao::getInstance()->getBindMobile($uid);
        }
//        如果是换绑手机号，reset cancel 为可以发送验证码
        if ($type == 2) {
            $phone = str_replace("_cancel", "", $phone);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $filterKey = $this->makeFilterKey($phone);
        try {
            //        过滤不发送的逻辑
            $this->filterSend($phone, $type);
            $code = $this->makeCode($phone);
            $this->handlerSms($clientInfo, $phone, $code, $type);
        } catch (FqException $e) {
            if ($e->getCode() === 403) {
                return $redis->ttl($filterKey);
            }
            throw $e;
        }
        return $redis->ttl($filterKey);
    }


    private function makeFilterKey($phone)
    {
        return sprintf("filter_sendsms:%d", $phone);
    }

    private function makeCode($phone)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        //判断测试手机 生成code
        if (in_array($phone, array('13800000000', '18888888888', '13888888888'))) {
            $code = 888888;
        } else {
            $code = $redis->get('verify_code_' . $phone);
            if (empty($code)) {
                $code = generateRandomcode(6);
            }
        }

        $redis->setex('verify_code_' . $phone, 600, $code);
        return $code;
    }


//    过滤不发送的逻辑
    private function filterSend($phone, $type)
    {
        CommonUtil::validateMobileSecond($phone);

        //虚拟号段禁止注册
        $arr = [1700, 1701, 1702, 162, 1703, 1705, 1706, 165, 1704, 1707, 1708, 1709, 171, 167];
        if ((in_array(substr($phone, 0, 3), $arr) || in_array(substr($phone, 0, 4), $arr)) && $type == 1) {
            throw new FQException('该手机号不支持注册', 500);
        }
//        filter limit
        $redis = RedisCommon::getInstance()->getRedis();
        $filterKey = $this->makeFilterKey($phone);
        $incr = $redis->incr($filterKey);
        if ($incr > 1) {
            throw new FQException('操作频繁，请稍后再试', 403);
        }
        $redis->expire($filterKey, 58);

        return true;
    }


//    handler sms and response
    private function handlerSms(ClientInfo $clientInfo, $phone, $code, $type)
    {
        if (config('config.VERTIFYCODE')) {
            $result = SmsMessageCommon::getInstance()->sendMessage('ali_sms_templateCode', $phone, ['code' => $code]);
        } else {
            $result['Code'] = "OK";
        }
        if (empty($result) || $result['Code'] !== 'OK') {
            if ($type == 1) {
                RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $clientInfo->deviceId, 'date' => date('Y-m-d')])->update(['send_sms_failed' => 1]);
            }
            throw  new FQException('发送验证码超过限制,请稍后重试', 701);
        }
        if ($type == 1) {
            RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $clientInfo->deviceId, 'date' => date('Y-m-d')])->update(['send_sms_ok' => 1]);
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $redis->incr('verify_code_num' . $phone);
        $t = SurplusTime();
        $redis->expire('verify_code_num' . $phone, $t);
        return true;
    }


    /**
     * 手机验证码登录 zb_user_withdraw_detail
     *
     * @param $mobile
     * @param $verifyCode
     * @param $clientInfo
     * @return UserWithdraw|null
     * @throws Exception
     */
    public function loginByMobileForWithdraw($mobile, $verifyCode, $clientInfo)
    {
        $mobile = trim($mobile);
        if (empty($mobile) || empty($verifyCode)) {
            throw new FQException('参数错误', 500);
        }

        CommonUtil::validateMobile($mobile);

        $this->authVerifyCode($mobile, $verifyCode);
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($mobile);
        if ($userId === 0) {
            throw new FQException("用户信息异常");
        }
        try {
            //黑名单检测
            BlackService::getInstance()->checkBlack($clientInfo, $userId);
            $user = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $clientInfo) {
                $user = UserRepositoryWithdraw::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $this->loginImplForWithdraw($user, $clientInfo);
                return $user;
            });
        } catch (Exception $e) {
            Log::error(sprintf('loginByMobileForWithdraw Error userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        Log::info(sprintf('UserService::loginByMobileForWithdraw ok userId=%d mobile=%s',
            $userId, $mobile));
        return $user;
    }


    /**
     * 登录
     * @param $user
     * @param $clientInfo
     * @param $isRegister
     * @throws Exception
     */
    private function loginImplForWithdraw($user, $clientInfo)
    {
        //注销检测
        if ($user->getUserModel()->isCancel) {
            throw new FQException('用户已注销', 500);
        }
        // 设置clientInfo
        $user->setClientInfo($clientInfo);

        $token = WithdrawTokenService::getInstance()->resetToken($user->getUserId());
        $user->setToken($token);

        Log::info(sprintf('loginImplForWithdraw userId=%d token=%s',
            $user->getUserId(), $user->getToken()));
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

}
