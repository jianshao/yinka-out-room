<?php


namespace app\domain\pay\channel;


use app\service\WeChatOpenService;
use think\facade\Log;

class WechatOpenPay extends WechatBase
{
    public function pay($order)
    {
        $payOrder = [
            'out_trade_no' => $order->orderId,
            'total_amount' => $order->rmb * 100,
        ];
        $openid = WeChatOpenService::getInstance()->getOauthUrlForOpenid($this->code);
        $result = $this->wxGzhPay($payOrder, $openid);
        //组装页面中调起支付的参数
        return $this->initPrepayData($result);
    }

    public function wxGzhPay($order, $openId)
    {
        $data = array(
            'appid' => config('config.WECHAT_OPEN.APPID'),
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => config('config.pay_subject'),
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

    public function initPrepayData($prepayData)
    {
        $prepaystr = json_encode($prepayData);
        Log::record('gzhpay---' . $prepaystr);
        $prepayData = json_decode($prepaystr, true);
        $appData = array(
            'appId' => $prepayData['appid'],
            'timeStamp' => time() . "",
            'nonceStr' => $this->createNonceStr(),
            'package' => "prepay_id=" . $prepayData['prepay_id'],
            'signType' => 'MD5',
        );
        $key = config('config.WECHAT_OPEN.APIKEY');
        $appData['paySign'] = $this->getSign($appData, $key);
        return $appData;
    }

    public function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getSign($params, $key)
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

        foreach ($param as $key => $value) {
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

        curl_setopt($ch, CURLOPT_URL, 'https://api.mch.weixin.qq.com/pay/unifiedorder');
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
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return "<xml><return_code>FAIL</return_code><return_msg>" . "系统不支持" . "</return_msg></xml>";
        }
    }

    protected function formatQueryParaMap($paraMap, $urlEncode = false)
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