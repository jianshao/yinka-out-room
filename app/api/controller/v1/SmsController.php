<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\common\model\RegisterDataModel;
use app\common\RedisCommon;
use app\common\SmsMessageCommon;
use app\domain\exceptions\FQException;
use app\facade\RequestAes as Request;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;


class SmsController extends BaseController
{

    /**
     * @type 1登录注册 2更改手机号，忘记密码，绑定新手机号（登录） 4申请家族 5注销账号 6 other
     * Notes:
     * User: echosendsmsLite
     * Date: 2021/9/22
     * Time: 6:24 下午
     */
    public function sendsmsLite()
    {
        $phone = Request::param('phone');
        $type = Request::param('type');
//        mark deviceid send sms type
        if ($type == 1) {
            RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $this->deviceId, 'date' => date('Y-m-d')])->update(['send_sms' => 1]);
        }
        $uid = $this->headUid;
//        resetPhone
        if (substr_count($phone, '*') && $uid) {
            $userModel = UserModelCache::getInstance()->getUserInfo($uid);
            $phone = $userModel->username;
        }
//        如果是换绑手机号，reset cancel 为可以发送验证码
        if ($type == 2) {
            $phone = str_replace("_cancel", "", $phone);
        }

        $redis = $this->getRedis();
        $filterKey = $this->makeFilterKey($phone);
        try {
            //        过滤不发送的逻辑
            $this->filterSend($phone, $type);

            $code = $this->makeCode($phone);

            $this->handlerSms($phone, $code, $type);
        } catch (FqException $e) {
            if ($e->getCode() === 403) {
                $ttlSecond = $redis->ttl($filterKey);
                return rjson(['ttl' => (int)$ttlSecond], 403, $e->getMessage(),[
                    'function'  => 'sendSms',
                    'extra'     => [
                        'type'      => $type,
                        'userId'    => $uid,
                        'deviceId'  => $this->deviceId
                    ]
                ]);
            }
            throw $e;
        }
        $ttlSecond = $redis->ttl($filterKey);
        return rjson(['ttl' => (int)$ttlSecond], 200, '验证码发送成功',[
            'function'  => 'sendSms',
            'extra'     => [
                'type'      => $type,
                'userId'    => $uid,
                'deviceId'  => $this->deviceId
            ]
        ]);
    }

    public function sendOpenSms()
    {
        $phone = Request::param('phone');
        $code = Request::param('code');

        if (config('config.VERTIFYCODE') && !in_array($phone, array('13800000000', '18888888888', '13888888888'))) {
            $result = SmsMessageCommon::getInstance()->sendMessage('', $phone, ['code' => $code]);
        } else {
            $result['SendStatusSet'][0]['Code'] = "Ok";
        }
        if (empty($result) || $result['SendStatusSet'][0]['Code'] !== 'Ok') {
            throw  new FQException('发送验证码超过限制,请稍后重试', 701);
        }

        return rjson();
    }

    private function makeFilterKey($phone)
    {
        return sprintf("filter_sendsms:%d", $phone);
    }

    private function makeCode($phone)
    {
        $redis = $this->getRedis();
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
        if ($incr > 1  && !in_array($phone, array('13800000000', '18888888888', '13888888888'))) {
            throw new FQException('操作频繁，请稍后再试', 403);
        }
        $redis->expire($filterKey, 58);

        return true;
    }


//    handler sms and response
    private function handlerSms($phone, $code, $type)
    {
        if (config('config.VERTIFYCODE') && !in_array($phone, array('13800000000', '18888888888', '13888888888'))) {
            $result = SmsMessageCommon::getInstance()->sendMessage('', $phone, ['code' => $code]);
        } else {
            $result['SendStatusSet'][0]['Code'] = "Ok";
        }
        if (empty($result) || $result['SendStatusSet'][0]['Code'] !== 'Ok') {
            if ($type == 1) {
                RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $this->deviceId, 'date' => date('Y-m-d')])->update(['send_sms_failed' => 1]);
            }
            throw  new FQException('发送验证码超过限制,请稍后重试', 701);
        }
        if ($type == 1) {
            RegisterDataModel::getInstance()->getModel()->where(['deviceid' => $this->deviceId, 'date' => date('Y-m-d')])->update(['send_sms_ok' => 1]);
        }

        $redis = $this->getRedis();
        $redis->incr('verify_code_num' . $phone);
        $t = SurplusTime();
        $redis->expire('verify_code_num' . $phone, $t);
        return true;
    }
}