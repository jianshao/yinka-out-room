<?php


namespace app\service;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use think\facade\Log;


class ApplePayService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ApplePayService();
        }
        return self::$instance;
    }

    public function makeDealId($transactionId)
    {
        return 'APPLEPAY' . $transactionId;
    }


    /**
     * 验证AppStore内付
     * @param  string $receiptData 付款后凭证
     * @return array                验证是否成功
     */
    public function checkApplePay($receiptData, $userId, $config = '')
    {
        if (empty($receiptData)) {
            return false;
        }
        $receiptData = str_replace(' ', '+', $receiptData);
        for ($i = 0; $i < 3; $i ++) {
            list($error, $html) = $this->acurl($receiptData, 0, $config);// 请求验证
            if ($error == 0) {
                break;
            } else {
                usleep(200000);
            }
        }
        if (!empty($html)) {
            $data = json_decode($html,true);

            Log::info(sprintf('ApplePayService::checkApplePay data=%s', $html));
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            // 如果是沙盒数据 则验证沙盒模式
            if ($data['status'] == '21007') {
                list($error, $html) = $this->acurl($receiptData, 1, $config);// 请求验证
                $data = json_decode($html,true);
                $data['sandbox'] = '1';
                Log::info(sprintf('ApplePayService::checkApplePay sandbox userId=%s data=%s', $userId, $html));
//                if (!$redis->sIsMember('apple_pay_white_conf', $userId)) {
//                    throw new FQException('Order cannot be completed in sandbox or test environment',4003);
//                }
            }
            // 判断是否购买成功
            if (isset($data['status'])) {
                if ($data['status'] == 0) {
                    if (!empty($data['receipt']['in_app'])) {
                        return $data['receipt']['in_app'];
                    }
                } else {
                    Log::info(sprintf('ApplePayService::checkApplePay sandbox  status=%s data=%s', $data['status'], $html));
                }
            }
        }
        return false;
    }

    /**
     * 21000 App Store不能读取你提供的JSON对象
     * 21002 receipt-data域的数据有问题
     * 21003 receipt无法通过验证
     * 21004 提供的shared secret不匹配你账号中的shared secret
     * 21005 receipt服务器当前不可用
     * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
     * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
     * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
     */
    private function acurl($receiptData, $sandbox=0, $config)
    {
//        $POSTFIELDS = array('receipt-data' => $receiptData, 'password' => config('config.apple_subscription_password'));
        $POSTFIELDS = array('receipt-data' => $receiptData);
        $POSTFIELDS = json_encode($POSTFIELDS);

        //正式购买地址 沙盒购买地址
        $url_buy     = 'https://buy.itunes.apple.com/verifyReceipt';
        $url_sandbox = 'https://sandbox.itunes.apple.com/verifyReceipt';
        $url = $sandbox ? $url_sandbox : $url_buy;

        Log::debug(sprintf('ApplePayServivce::acurl url=%s', $url));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        Log::info(sprintf('ApplePayService::checkApplePay acurl errorno=%s errmsg=%s', $errno, $errmsg));
        curl_close($ch);

        //判断时候出错，抛出异常
        if ($errno != 0) {
            Log::info(sprintf('ApplePayService::checkApplePay acurl errorno=%s errmsg=%s', $errno, $errmsg));
        }
        return [$errno, $result];
    }
}