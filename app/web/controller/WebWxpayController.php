<?php
/**
 * 
 */

namespace app\web\controller;

use app\admin\model\MemberModel;
use app\common\model\PayChannelModel;
use think\facade\Request;
use app\BaseController;
use think\facade\Db;
use think\facade\Log;
use app\common\model\ChargeModel;
use think\facade\View;


class WebWxpayController extends BaseController
{

    protected $appid;
    protected $appSecret;
    public $data = null;

    public function __construct()
    {
    	$conf = config('config.WECHAT_OPEN');
        $this->appid = $conf['APPID'];
        $this->appSecret = $conf['APPSECRET'];
    }

    public function WeChatSubscription() {
        $where[] = ['diamond','>',0];
        $res = ChargeModel::getInstance()->where($where)->select()->toArray();
        View::assign('data', $res);
        return View::fetch('../view/web/zhifu/gzh.html');
    }

    //充值列表页
    public function wxpay()
    {

        if (!session_id()) session_start();
        $id = !empty($_SESSION)?$_SESSION['id']:'';
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            View::assign('url', './men.png');
            return View::fetch('../view/web/zhifu/guide.html');//蒙层
        }
    	// $avatar = Request::param('avatar');
        $where[] = ['diamond','>',0];
    	$res = ChargeModel::getInstance()->where($where)->select()->toArray();
        $web_url = config('config.WEB_URL');
    	View::assign('data', $res);
        View::assign('web_url', $web_url);
        View::assign('id', $id);
        if(isMobile()===false){
            return View::fetch('../view/web/zhifu/wxpay1.html');//电脑端
        }else{
            return View::fetch('../view/web/zhifu/wxpay.html');//手机端
        }
    }

    //根据用户账号id获取用户头像和昵称
    public function userData() {
        $userId = Request::param('uid');
        $info = MemberModel::getInstance()->where(['id'=>$userId])->find();
        if (empty($info)) {
            $info = MemberModel::getInstance()->where(['pretty_id'=>$userId])->find();
        }
        if($info) {
            $data['nickname'] = $info['nickname'];
            $data['avatar'] = getavatar($info['avatar']);
            return rjson($data);
        }else{
            $info = [];
        }
        return rjson([],500);
    }

    /**
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     * @return 用户的openid
     */
    public function gzhindex()
    {
        $data = Request::param();
        $payment = config('config.WECHAT_OPEN.payment');  //获取支付地址
//        $payment = 'http://recodetest.fqparty.com/api/v1/payment';
        $uid = isset($data['uid']) ? $data['uid'] : '';
        $rmb = isset($data['rmb']) ? $data['rmb'] : '';
        $channel = 4;
        // $payChannel = PayChannelModel::getInstance()->where(['pid'=>2, 'check'=>1, 'type'=>2])->find();
        // if(!empty($payChannel)) {
        //     $channel = $payChannel['id'];
        // }
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode($payment."?uid=".$uid."&rmb=".$rmb."&channel=".$channel);
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code);
            return $openid;
        }
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        try {
            $url = $this->__CreateOauthUrlForOpenid($code);
            $res = self::curlGet($url);
            Log::info('getOpenId----'. $res);
            //取出openid
            $data = json_decode($res, true);
            $this->data = $data;
            if (session('openid'))
                return;
            $openid = $data['openid'];
            return $openid;
        } catch (\Exception $e) {
            echo $e;
        }
    }

    /**
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["secret"] = $this->appSecret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        Log::record('__CreateOauthUrlForCode-----'.$bizString);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }

   

    public static function curlGet($url = '', $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
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

    public function way(){
        if (!session_id()) session_start();
        $id = !empty($_SESSION)?$_SESSION['id']:'';

        // $avatar = Request::param('avatar');
        $res = ChargeModel::getInstance()->select()->toArray();
        $web_url = config('config.WEB_URL');
        View::assign('data', $res);
        View::assign('web_url', $web_url);
        View::assign('id', $id);
        return view('web/zhifu/way');
    }
}
/**
 * 判断用户请求设备是否是移动设备
 * @return bool
 */
function isMobile() {

    //如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }

    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], 'wap')) {
        return true;
    }

    //野蛮方法,判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {

        $clientKeywords = ['nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'];

        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(".implode('|', $clientKeywords).")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }

    //协议法,因为有可能不准确,放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        //如果只支持wml并且不支持html那一定是移动设备
        //如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }

    return false;
}



