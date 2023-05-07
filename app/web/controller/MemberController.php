<?php

namespace app\web\controller;

use AlibabaCloud\Client\AlibabaCloud;
use app\domain\user\dao\AccountMapDao;
use app\form\ClientInfo;
use app\service\BlackService;
use app\web\common\WebBaseController;
use think\facade\Log;
use Ramsey\Uuid\Uuid;

class MemberController extends WebBaseController
{
    /**
     * @var integer 验证码长度.
     */
    protected $captcha_length = 6;
    /**
     * 阿里短信配置项
     */
    private $ali_sms_product = 'Dysmsapi';
    private $ali_sms_action = 'SendSms';
    private $ali_sms_host = 'dysmsapi.aliyuncs.com';

    /**
     * 获取短信验证码
     */
    public function smsCode()
    {
        $phone = $this->request->param('phone');
        $pattern = " /^1\d{10}$/";
        if( !preg_match($pattern, $phone) ){
            echo $this->return_json(500, null, '手机号不合法');
            die;
        }
        try {
            //查询手机号是否存在用户表中
            $user_id = AccountMapDao::getInstance()->getUserIdByMobile($phone);
            if (empty($user_id)) {
                echo $this->return_json(500, null, '手机号尚未注册');
                die;
            }
            $user_id = $user_id->toArray();
            //查询此用户是否被封禁
            //查询此用户是否被封禁
            $clientInfo = new ClientInfo();
            $clientInfo->fromRequest($this->request);
            $clientInfo->simulatorInfo = $params['simulator_info'] ?? '';
            BlackService::getInstance()->checkBlack($clientInfo, $user_id);
            $code = $this->generateRandomStr($this->captcha_length);
            $expired_time = 5; // 单位分钟.
            $redis = $this->getRedis();
            $redis->setex('music_verify_code_' . $phone, $expired_time * 60, $code);
            Log::record('官网阿里短信发送开始日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code, 'smsCode');
            $result = $this->_aliSmsSend($phone, json_encode(array('code' => $code)));
            Log::record('官网阿里短信发送结束日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code . ':返回数据:' . json_encode($result), 'smsCode');
            if (empty($result) || $result['Code'] != 'OK') {
                echo $this->return_json(500, null, '发送验证码失败');
                die;
            }
            $this->returnCode = 200;
            $this->returnMsg = '发送验证码成功';
        } catch (\Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        echo $this->return_json($this->returnCode, null, $this->returnMsg);
        die;
    }

    /**
     * 用户登录接口
     */
    pubLic function login()
    {
        $phone = $this->request->param('phone');
        $pattern = " /^1\d{10}$/";
        if( !preg_match($pattern, $phone) ){
            echo $this->return_json(500, null, '手机号不合法');
            die;
        }
        $code = $this->request->param('code');
        if (!$code || strlen($code) < 6) {
            echo $this->return_json(500, null, '请正确输入验证码');
            die;
        }
        //校验验证码
        $redis = $this->getRedis();
        $redis_code = $redis->get('music_verify_code_' . $phone);
        if ($redis_code !== $code) {
            echo $this->return_json(500, null, '请正确输入验证码');
            die;
        }
        //校验成功后生成token
        $userinfo = $this->_login($phone);
        if ($userinfo === false) {
            echo $this->return_json(500, null, '用户不存在');
            die;
        }
        Log::record('用户登录账号:' . json_encode($userinfo), 'login');
        $redis->del('music_verify_code_' . $phone);
        echo $this->return_json(200, '', '登录成功');
        die;
    }
    /**
     * Ali短信发送
     */
    private function _aliSmsSend($phone, $data)
    {
        $conf = config('config.ALISMS');
        AlibabaCloud::accessKeyClient($conf['accessKeyId'], $conf['accessSecret'])->regionId('cn-hangzhou')->asDefaultClient();
        $result = AlibabaCloud::rpc()
            ->product($this->ali_sms_product)
            ->version('2017-05-25')
            ->action($this->ali_sms_action)
            ->method('POST')
            ->host($this->ali_sms_host)
            ->options([
                'query' => [
                    'RegionId' => $conf['ali_sms_regionId'],
                    'PhoneNumbers' => $phone,
                    'SignName' => $conf['ali_sms_signName'],
                    'TemplateCode' => $conf['ali_sms_templateCode'],
                    'TemplateParam' => $data,
                ],
            ])
            ->request();
        return $result->toArray();
    }

    /**
     * 用户登录基本信息
     */
    private function _login($phone)
    {
        //更新mysql
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($phone);
        if (empty($userId)) {
            return false;
        }
        //创建用户基本信息
        $info = [
            'username' => $phone,
            'id' => $userId,
            'last_login_time' => time(),
        ];
        if (!session_id()) session_start();
        $_SESSION['id'] = $userId;
        $_SESSION['username'] = $phone;
        /*Session::set('username' , $phone);
        Session::set('id' , $login['id']);
        Session::set('last_login_time' , time());*/

        return array( 'userInfo' => $info);
    }
    /* 生成随机字符串.
    * @param int $length 需要生成的长度.
    * @param string $table 需要生成的字符串集合.
    * @return string
    */
    protected function generateRandomStr($length = 6, $table = '0123456789')
    {
        $code = '';
        if ($length <= 0 || empty($table)) {
            return $code;
        }
        $max_size = strlen($table) - 1;
        while ($length-- > 0) {
            $code .= $table[rand(0, $max_size)];
        }
        return $code;
    }

    /**
     * 退出操作
     */
    public function loginOut()
    {
        if (!session_id()) session_start();
        session_destroy();  //清除服务器的sesion文件
        echo $this->return_json(200, '', '安全退出成功');
        die;
    }

}
