<?php


namespace app\domain\thirdpay\chinaums;

use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc 支付基类
 * 注意:  请求下单用 SHA256 加密请求 ，  回调用md5验签
 * Class BasePay
 * @package app\domain\thirdpay\chinaums
 */
class BasePay
{
    public $config = array();

    protected $notifyUrl = '';  // 回调地址

    protected $url = 'https://qr.chinaums.com/netpay-route-server/api/';    //支付网关

    protected $msgType = 'wx.unifiedOrder';  //消息类型

    protected $jump = false;  // 是否直接跳转

    public function __construct($config)
    {
        if (!ArrayUtil::safeGet($config, 'msgSrc')) {
            throw new ThreePaymentException('缺少配置消息来源');
        }

        if (!ArrayUtil::safeGet($config, 'msgSrcId')) {
            throw new ThreePaymentException('缺少配置来源编号');
        }

        if (!ArrayUtil::safeGet($config, 'mdKey')) {
            throw new ThreePaymentException('缺少配置通讯密钥');
        }

        if (!ArrayUtil::safeGet($config, 'mid')) {
            throw new ThreePaymentException('缺少配置商户编号');
        }

        if (!ArrayUtil::safeGet($config, 'tid')) {
            throw new ThreePaymentException('缺少配置终端号');
        }

        if (!ArrayUtil::safeGet($config, 'notifyUrl')) {
            throw new ThreePaymentException('缺少回调地址');
        }

        $this->config = $config;
        $this->notifyUrl = ArrayUtil::safeGet($config, 'notifyUrl');
    }

    /**
     * @desc 统一下单接口
     * @param $params
     */
    protected function pay($params)
    {
        $this->checkParams($params);

        $config = $this->config;
        $requestTimestamp = date('Y-m-d H:i:s', time());
        $merOrderId = sprintf('%s%s', $config['msgSrcId'], $params['order_id']);
        // 必传字段
        $data = [
            'msgSrc' => $config['msgSrc'],//消息来源
            'msgType' => $this->msgType,//消息类型
            'requestTimestamp' => $requestTimestamp,
            'mid' => $config['mid'],
            'tid' => $config['tid'],
            'merOrderId' => $merOrderId,
//            'instMid' => 'MINIDEFAULT',
            'totalAmount' => $params['totalAmount'],
//            'tradeType' => 'MINI',
            'signType' => 'SHA256',
//            'subOpenId' => $subOpenId,
            'notifyUrl' => $this->notifyUrl,//回调地址
        ];

        // 扫码场景已 billNo 为订单id
        if ($this->msgType == "bills.getQRCode") {
            $data['billNo'] = $merOrderId;
        }

        // 微信openid
        if (isset($params['subOpenId'])) {
            $data['subOpenId'] = $params['subOpenId'];
        }

        if (isset($params['instMid'])) {
            $data['instMid'] = $params['instMid'];
        }

        if (isset($params['tradeType'])) {
            $data['tradeType'] = $params['tradeType'];
        }

        // 订单过期时间
        if (isset($params['expireTime'])) {
            $data['expireTime'] = $params['expireTime'];
        }

        // 订单描述
        if (isset($params['orderDesc'])) {
            $data['orderDesc'] = $params['orderDesc'];
        }

        // 同步回调地址
        if (isset($params['returnUrl'])) {
            $data['returnUrl'] = $params['returnUrl'];
        }

        //获取sign参数
        ksort($data);
        reset($data);
        $options = '';
        foreach ($data as $key => $value) {
            $options .= $key . '=' . $value . '&';
        }
        $options = rtrim($options, '&');
        $sign1 = $options . $config['mdKey'];
        $sign = trim(hash("sha256", $sign1));//sha256计算
        $data['sign'] = $sign;

        Log::channel(['pay', 'file'])->info(sprintf('request chinaums::pay before params order_id=%s data=%s',
            $params['order_id'], json_encode($data)));

        // 两种方式：
        //  一. 返回结果，客户端/前端发起支付， $this->jump = false
        //  二. 直接重定向到支付页面   $this->jump = true
        if ($this->jump) {
            $querystring = http_build_query($data);
            $jumpUrl = $this->url . '?' . $querystring;
            Log::channel(['pay', 'file'])->info(sprintf('request chinaums::pay jump params order_id=%s jumpUrl=%s ',
                $params['order_id'], $jumpUrl));
            header('Location: ' . $jumpUrl);
            exit;
//            $response = ['jump_url' => $jumpUrl];
        } else {
            $data = json_encode($data);
            $response = curlData($this->url, $data, 'POST');
            Log::channel(['pay', 'file'])->info(sprintf('request chinaums::pay after params order_id=%s response=%s',
                $params['order_id'], $response));
            $response = json_decode($response, true);
        }

        return $response;
    }

    /**
     * 检查参数
     * @param $params
     * @throws ThreePaymentException
     */
    public function checkParams($params)
    {
        //检测必填参数
        if (!isset($params['totalAmount'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数totalAmount");
        }

        if (!isset($params['order_id'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数order_id");
        }

        // 银联商务订单号范围在 6 - 32之间
        if (mb_strlen($params['order_id']) > 29) {
            throw new ThreePaymentException("order_id长度不能超过29");
        }

        //关联参数
        if ($this->msgType == "wx.unifiedOrder" && !isset($params['subOpenId'])) {
            throw new ThreePaymentException("统一支付接口中，缺少必填参数subOpenId！msgType为wx.unifiedOrder时，subOpenId为必填参数！");
        }

        return true;
    }

}