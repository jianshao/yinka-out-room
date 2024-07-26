<?php


namespace app\service;


//use app\domain\exceptions\FQException;
//use app\domain\pay\ProductSystem;
//use app\domain\vip\constant\VipConstant;
//use app\domain\vip\service\VipService;
//use app\domain\thirdpay\chinaums\PayConstant;
//use app\domain\thirdpay\service\ChinaumsPayService;
//use app\domain\thirdpay\service\WeChatService;
//use app\utils\ArrayUtil;
//use think\facade\Log;
//use Yansongda\Pay\Pay;
//use think\facade\View;
//require_once "../app/common/phpqrcode.php";

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\pay\channel\PayConstant;
use app\utils\ArrayUtil;
use think\facade\Log;


class PayService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PayService();
        }
        return self::$instance;
    }

    /**
     * 支付渠道
     * @var array[]
     */
    protected $channelMap = [
        \app\domain\pay\channel\PayConstant::APP_ALIPAY_CHANNEL => [  // app内支付宝支付（原生）
            'class' => \app\domain\pay\channel\AppAlipay::class,
            'method' => 'pay'
        ],
        PayConstant::WEB_ALIPAY_CHANNEL => [ // 支付宝网页支付（原生）
            'class' => \app\domain\pay\channel\WebAlipay::class,
            'method' => 'pay'
        ],
        PayConstant::APP_WECHAT_CHANNEL => [ // app内微信支付 （原生）
            'class' => \app\domain\pay\channel\AppWechatPay::class,
            'method' => 'pay'
        ],
        PayConstant::WECHAT_GZH_CHANNEL => [ // 原生公众号
            'class' => \app\domain\pay\channel\WechatOpenPay::class,
            'method' => 'pay'
        ],
        PayConstant::WEB_WECHAT_CHANNEL => [ // 微信web支付
            'class' => \app\domain\pay\channel\WebWechatPay::class,
            'method' => 'pay'
        ],
        PayConstant::WECHAT_CODE_CHANNEL => [  // 微信扫码支付
            'class' => \app\domain\pay\channel\WechatCodePay::class,
            'method' => 'pay'
        ],
        PayConstant::H5_ALIPAY_CHANNEL => [  // 支付宝手机网页支付
            'class' => \app\domain\pay\channel\AlipayH5::class,
            'method' => 'pay'
        ],
        PayConstant::SHENG_WECHAT_APPLET_CHANNEL => [ // 微信小程序-盛付通
            'class' => \app\domain\pay\channel\ShengPay::class,
            'method' => 'wxAppletPay'
        ],
        PayConstant::SHENG_WECHAT_GZH_CHANNEL => [ // 微信公众号-盛付通
            'class' => \app\domain\pay\channel\ShengPay::class,
            'method' => 'wxGzhPay'
        ],
        PayConstant::SHENG_WECHAT_SCAN_CHANNEL => [ // 微信扫码-盛付通
            'class' => \app\domain\pay\channel\ShengPay::class,
            'method' => 'wxScanPay'
        ],
        PayConstant::DIN_WECHAT_H5_CHANNEL => [ // 智付-微信H5
            'class' => \app\domain\pay\channel\DinPay::class,
            'method' => 'wxH5Pay'
        ],
        PayConstant::DIN_ALI_H5_CHANNEL => [ // 智付-支付宝H5
            'class' => \app\domain\pay\channel\DinPay::class,
            'method' => 'aliH5Pay'
        ],
    ];

    /**
     * 支付
     * @param $order
     * @param $config
     * @param false $isRedPackets 是否是红包
     * @param false $code code码
     * @return false|mixed
     * @throws FQException
     */
    public function pay($order, $config, $isRedPackets = false, $code = false)
    {
        Log::channel(['pay', 'file'])->info(sprintf('PayService pay order:%s config:%s isRedPackets:%s code:%s',
            $order->orderId, $config, $isRedPackets, $code));
        $channelInfo = $this->channelMap[$order->payChannel] ?? [];
        if (empty($channelInfo)) {
            throw new FQException('充值渠道错误', 500);
        }
        $class = ArrayUtil::safeGet($channelInfo, 'class');
        $method = ArrayUtil::safeGet($channelInfo, 'method');
        try {
            $app = new $class($config, $code);
            return call_user_func([$app, $method], $order, $isRedPackets);
        } catch (\Exception $e) {
            $errMsg = sprintf("PayService pay order:%s code:%s er_code:%s msg:%s file:%s line:%s trance:%s",
                $order->orderId, $code, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            Log::channel(['pay', 'file'])->error($errMsg);
            throw new FQException('下单失败,请重试', 500);
        }
    }

    public function pay1($order, $config, $isRedPackets=false, $code=false) {
        $time_expire = config('config.orderExpire');
        $paySubject = config('config.pay_subject');
        $time = time();
        $minute = intval($time_expire / 60);
        switch ($order->payChannel) {
            case 1: //app内支付宝支付（原生）
                $payOrder = $this->formatAppAlipayOrder($order, $minute, $config);
                return $this->appAlipay($payOrder, $config, $isRedPackets);
                break;
            case 2: //支付宝网页支付（原生）
                $order = [
                    'out_trade_no' => $order->orderId,
                    'total_amount' => $order->rmb,
                    'subject' => $paySubject,
                    'timeout_express' => $minute.'m'
                ];
                return $this->webAlipay($order);
                break;
            case 3: //app内微信支付 （原生）
                $payOrder = [
                    'out_trade_no' => $order->orderId,
                    'total_fee' => $order->rmb * 100,
                    'body' => $paySubject,
                    'time_expire' => date('YmdHis', $time + $time_expire),
                ];
                $result = $this->appWechatPay($payOrder, $config, $isRedPackets);
                $result = json_decode($result,true);
                return $result;
                break;
            case 13: //微信web支付
                $order = [
                    'out_trade_no' => $order->orderId,
                    'total_fee' => $order->rmb * 100,
                    'body' => $paySubject,
                    'time_expire' => date('YmdHis', $time + $time_expire),
                ];
                return $this->webWechatPay($order);
                break;
            case 15: //微信扫码支付
                $order = [
                    'out_trade_no' => $order->orderId,
                    'total_fee'      => $order->rmb * 100,
                    'body' => $paySubject,
                    'time_expire' => date('YmdHis', $time + $time_expire),
                ];
                $result = $this->WechatCode($order);
                $url = $result->code_url;
                Log::info(sprintf('PayService::15 url=%s',$url));
                return \QRcode::png($url);

                break;
            case 16: //支付宝手机网页支付
                $order = [
                    'out_trade_no' => $order->orderId,
                    'total_amount' => $order->rmb,
                    'subject' => $paySubject,
                    'timeout_express' => $minute.'m'
                ];
                return $this->AlipayH5($order);
                break;
            case PayConstant::CHINAUMS_APP_ALI_CHANNEL: // 支付宝app支付银联商务
                $payOrder = $this->handleChinaumsParams($order, $time, $code);
                return ChinaumsPayService::getInstance()->aliAppPay($payOrder);

            case PayConstant::CHINAUMS_WECHAT_APPLET_CHANNEL: // 微信小程序银联商务
                $payOrder = $this->handleChinaumsParams($order, $time, $code);
                return ChinaumsPayService::getInstance()->weChatAppletPay($payOrder);

            case PayConstant::CHINAUMS_WECHAT_GZH_CHANNEL: // 微信公众号银联商务
                $payOrder = $this->handleChinaumsParams($order, $time, $code);
                return ChinaumsPayService::getInstance()->weChatGzhPay($payOrder);

            case PayConstant::CHINAUMS_H5_ALI_CHANNEL: // ali-h5 支付银联商务
                $payOrder = $this->handleChinaumsParams($order, $time, $code);
                return ChinaumsPayService::getInstance()->aliH5Pay($payOrder);

            case PayConstant::CHINAUMS_CTOB_CHANNEL: // c扫b 聚合银联商务
                $payOrder = $this->handleChinaumsParams($order, $time, $code);
                return ChinaumsPayService::getInstance()->ctoBPay($payOrder);

            default:
                throw new FQException('充值渠道错误', 500);
        }
    }

    /**
     * @desc 获取支付宝支付数据
     * @param $order
     * @param $minute
     * @return array
     */
    public function formatAppAlipayOrder($order, $minute, $config)
    {
        $product = ProductSystem::getInstance()->findProduct($order->productId);
        $payOrder = [
            'out_trade_no' => $order->orderId,
            'total_amount' => $order->rmb,
            'subject' => config('config.pay_subject'),
            'timeout_express' => $minute . 'm'
        ];
        // 自动续费参数
        if ($product->isAutoRenewal) {
            $externalAgreementNo = sprintf('%s-%s', $order->orderId, $config);
            $conf = config("$config.alipay_yuansheng");

            $product = ProductSystem::getInstance()->findProduct($order->productId);
            $time = time();
            $executeTimeStr = VipService::getInstance()->getVipExecuteTime($time);
            $payOrder = [
                'out_trade_no' => $order->orderId,
                'total_amount' => $order->rmb,
                'subject' => config('config.pay_subject'),
                'timeout_express' => $minute . 'm',
                'product_code' => 'CYCLE_PAY_AUTH',
                'agreement_sign_params' => [
                    'personal_product_code' => 'CYCLE_PAY_AUTH_P',
                    'sign_scene' => 'INDUSTRY|SOCIALIZATION',
                    'external_agreement_no' => $externalAgreementNo,
                    'sign_notify_url' => $conf['sign_notify_url'] ?? '',
                    'access_params' => [
                        'channel' => 'ALIPAYAPP'
                    ],
                    'period_rule_params' => [
                        'period_type' => VipConstant::PERIOD_TYPE,
                        'period' => VipConstant::PERIOD,
                        'execute_time' => $executeTimeStr,
                        'single_amount' => $product->autoRenewalPrice,
                    ]
                ],
            ];
        }
        return $payOrder;
    }

    /**
     * @desc 处理银联商务参数
     * @param $order
     * @param $time
     * @param $code
     * @return array
     * @throws FQException
     */
    public function handleChinaumsParams($order, $time, $code)
    {
        $time_expire = config('config.orderExpire');
        $expireTime = date('Y-m-d H:i:s', $time + $time_expire);
        $payOrder = [
            'order_id' => $order->orderId,
            'totalAmount' => $order->rmb * 100,
            'orderDesc' => config('config.pay_subject'),
            'expireTime' => $expireTime,
        ];
        if ($code) {
            $WxOpenidInfo = WeChatService::getInstance()->getWxOpenid($code);
            $subOpenId = ArrayUtil::safeGet($WxOpenidInfo, 'openid');
//            $subOpenId = 'oy5-E5btOxiWK_kaURjPSMF9GBYk';
            if (!$subOpenId) {
                throw new FQException('code 获取openid异常', 500);
            }
            $payOrder['subOpenId'] = $subOpenId;
        }
        return $payOrder;
    }

    //web微信支付（原生）
    public function webWechatPay($order)
    {
        $conf = config('config.wechat_yuansheng');
        $config = [
            'appid' => $conf['appid'],
            'app_id' => $conf['app_id'], // 公众号 APPID
            'miniapp_id' => '', // 小程序 APPID
            'mch_id' => $conf['mch_id'],
            'key' => $conf['key'],
            'notify_url' => $conf['notify_url'],
            'cert_client' => '',
            'cert_key' => '',
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'info', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            // 'mode' => 'dev',
        ];
        $response = Pay::wechat($config)->wap($order);
        return $response->getContent();
//        return Pay::wechat($config)->wap($order);
    }

    /**
     * 微信二维码支付
     * @param $order
     * @return mixed
     */
    public function WechatCode($order) {
        $conf = config('config.wechat_yuansheng');
        $config = [
            'appid' => $conf['appid'],
            'app_id' => $conf['app_id'], // 公众号 APPID
            'miniapp_id' => '', // 小程序 APPID
            'mch_id' => $conf['mch_id'],
            'key' => $conf['key'],
            'notify_url' => $conf['notify_url'],
            'cert_client' => '',
            'cert_key' => '',
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'info', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            // 'mode' => 'dev',
        ];
        return Pay::wechat($config)->scan($order);
    }

    /**
     * 支付宝H5支付
     * @param $order
     * @return mixed
     */
    public function AlipayH5($order) {
        $conf = config('config.alipay_yuansheng');
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $conf['notify_url'],
            'return_url' => $conf['return_url'],
            'ali_public_key' => $conf['ali_public_key'],
            // 加密方式： **RSA2**
            'private_key' => $conf['private_key'],
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'debug', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev',
        ];
        return Pay::alipay($config)->wap($order)->getContent();
    }

//    public function gzhpay($order, $config, $isRedPackets=false, $code=false) {
//        switch ($order->payChannel) {
//            case 4: //公众号支付 （原生）
//                if($code) {
//                    $openid = curlData('http://newmapi2.fqparty.com/web/gzhindex', ['code' => $code]);
//                }
//                Log::record('openid----'.$openid);
//                $order = [
//                    'out_trade_no' => $order->orderId,
//                    'total_amount' => $order->rmb * 100,
//                    'subject' => $paySubject,
//                ];
//                $result = $this->wxGzhPay($order, $openid);
//                //组装页面中调起支付的参数
//                $prePayData = $this->initPrepayData($result);
//                View::assign('appId', $prePayData['appId']);
//                View::assign('timeStamp', $prePayData['timeStamp']);
//                View::assign('nonceStr', $prePayData['nonceStr']);
//                View::assign('package', $prePayData['package']);
//                View::assign('signType', $prePayData['signType']);
//                View::assign('paySign', $prePayData['paySign']);
//                return View::fetch('../view/web/zhifu/pay.html');
//                break;
//            default:
//                throw new FQException('充值渠道错误', 500);
//        }
//    }

    //app微信支付（原生）
    public function appWechatPay($order, $config, $isRedpacket=false) {
        $conf = config("$config.wechat_yuansheng");
        $config = [
            'appid' => $conf['appid'],
            'app_id' => $conf['app_id'], // 公众号 APPID
            'miniapp_id' => '', // 小程序 APPID
            'mch_id' => $conf['mch_id'],
            'key' => $conf['key'],
            'notify_url' => $isRedpacket ? $conf['redpacket_notify_url'] : $conf['notify_url'],
            'cert_client' => '',
            'cert_key' => '',
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'info', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            // 'mode' => 'dev',
        ];
        Log::debug(sprintf('PayService::appWechatPay payOrder=%s config=%s',
            json_encode($order), json_encode($config)));
        return Pay::wechat($config)->app($order)->getContent();
    }

    //app支付宝支付(原生)
    public function appAlipay($order, $config, $isRedpacket=false) {
        $conf = config("$config.alipay_yuansheng");
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $isRedpacket ? $conf['redpacket_notify_url'] : $conf['notify_url'],
            'return_url' => $conf['return_url'],
            'ali_public_key' => $conf['ali_public_key'],
            // 加密方式： **RSA2**
            'private_key' => $conf['private_key'],
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'debug', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev',
        ];
        Log::debug(sprintf('PayService::appAlipay payOrder=%s config=%s',
            json_encode($order), json_encode($config)));
        return Pay::alipay($config)->app($order)->getContent();
    }


    //支付宝网页支付
    public function webAlipay($order)
    {
        $conf = config('config.alipay_yuansheng');
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $conf['notify_url'],
            'return_url' => $conf['return_url'],
            'ali_public_key' => $conf['ali_public_key'],
            // 加密方式： **RSA2**
            'private_key' => $conf['private_key'],
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'debug', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev',
        ];
        return Pay::alipay($config)->web($order)->getContent();
    }

    public function wxGzhPay($order, $openId) {
        $data = array(
            'appid' => config('config.WECHAT_OPEN.APPID'),
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => 'test',
            'mch_id' => config('config.WECHAT_OPEN.MCHID'),
            'nonce_str' => $this->createNonceStr(),
            'notify_url' => config('config.WECHAT_OPEN.notify_url'),
            'openid' => $openId,            //rade_type=JSAPI，此参数必传
            'out_trade_no' => $order['out_trade_no'],
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'total_fee' => $order['total_amount'],       //单位 转为分
            'trade_type' => 'JSAPI',
        );
        $key = config('config.WECHAT_OPEN.APIKEY');
        $data['sign'] = $this->getSign($data, $key);
        $xml = $this->arrToXML($data);
        $responseXml = $this->postXmlCurl($xml);
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $unifiedOrder;
    }

    public function initPrepayData($prepayData) {
        $prepaystr = json_encode($prepayData);
        Log::record('gzhpay---'.$prepaystr);
        $prepayData = json_decode($prepaystr, true);
        $appData = array(
            'appId'     => $prepayData['appid'],
            'timeStamp' => time()."",
            'nonceStr'  => $this->createNonceStr(),
            'package'  => "prepay_id=" .$prepayData['prepay_id'],
            'signType'   => 'MD5',
        );
        $key = config('config.WECHAT_OPEN.APIKEY');
        $appData['paySign'] = $this->getSign($appData, $key);
        return $appData;
    }

    public  function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public  function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = $this->formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    private function arrToXML($param, $cdata = false)
    {
        $xml = "<xml>";
        $cdataPrefix = $cdataSuffix = '';
        if ($cdata) {
            $cdataPrefix = '<![CDATA[';
            $cdataSuffix = ']]>';
        }

        foreach($param as $key => $value) {
            $xml .= "<{$key}>{$cdataPrefix}{$value}{$cdataSuffix}</$key>";
        }
        $xml .= "</xml>";
        return $xml;
    }

    private static function postXmlCurl($xml, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch,CURLOPT_URL, 'https://api.mch.weixin.qq.com/pay/unifiedorder');
        // curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        // curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return "<xml><return_code>FAIL</return_code><return_msg>"."系统不支持"."</return_msg></xml>";
        }
    }

    protected  function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}