<?php
namespace app\web\controller;
//define your token
use app\BaseController;

class WechatMuaController extends BaseController
{
    private $fromUsername;
    private $toUsername;
    private $times;
    private $keyword;
    private $app_id = 'wx2d18a0fa953d0c0a';
    private $app_secret = 'b2dd1b3362e064cd575d3e52e378065c';
    private $aes_key = 'twb1b1FzyPbubSdtzHtLytaVWDR2iLYce8wliKVvAIt';
    public function getAccessToken() {
        $access_token = $this->get_access_token();
        echo $access_token;die;
    }
    /**
     * 获取access_token
     */
    private function get_access_token()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->app_id."&secret=".$this->app_secret;
        $data = json_decode(file_get_contents($url),true);
        if($data['access_token']){
            return $data['access_token'];
        }else{
            return "获取access_token错误";
        }
    }

    public function delMenu() {
        $access_token = $this->get_access_token();
        $url = " https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=". $access_token;
        $data = json_decode(file_get_contents($url), true);
        if($data['errcode'] == 0) {
            return "ok";
        } else {
            return $data;
        }
    }

    public function createMenu() {
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $arr = array(
            'button' =>array(
                array(
                    'name'=>urlencode("下载"),
                    'type'=>'view',
                    'url'=>'https://fqparty.com'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'https://www.fqparty.com'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('工会招募'),
                            'url' => 'https://image.fqparty.com//banner/20200610/4465c83eb7641c5330f9e30c0fce3448.png'
                        ),
                        array(
                            'type' => 'click',
                            'name' => urlencode('注意事项'),
                            'key' => 'Pay_888'
                        )
                    )
                ),
                array(
                    'name'=>urlencode("充值相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('充值'),
                            'url' => 'https://www.fqparty.com/web/gzhpay'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('提现'),
                            'url' => 'http://gl.muayuyin.com/web/webUserWithdrawal/withdrawalLogin'
                        )
                    )
                )
            )
        );
        $jsondata = urldecode(json_encode($arr));
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$jsondata);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    /**
     * 创建ios个性化菜单
     */
    public function createIosMenu()
    {
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=" . $access_token;

        $arr = array(
            'button' =>array(
                array(
                    'name'=>urlencode("下载"),
                    'type'=>'view',
                    'url'=>'https://fqparty.com'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'https://www.fqparty.com'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('工会招募'),
                            'url' => 'https://image.fqparty.com//banner/20200610/4465c83eb7641c5330f9e30c0fce3448.png'
                        ),
                        array(
                            'type' => 'click',
                            'name' => urlencode('注意事项'),
                            'key' => 'Pay_888'
                        )
                    )
                ),
                array(
                    'name' => urlencode("提现"),
                    'type' => 'view',
                    'url' => 'http://gl.muayuyin.com/web/webUserWithdrawal/withdrawalLogin'
                ),
            ),
            'matchrule' => array(
                'client_platform_type' => 1
            ),
        );
        $jsondata = urldecode(json_encode($arr));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * 创建安卓个性化菜单
     * @return bool|string
     */
    public function createAndroidMenu()
    {
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=" . $access_token;
        $arr = array(
            'button' =>array(
                array(
                    'name'=>urlencode("下载"),
                    'type'=>'view',
                    'url'=>'https://fqparty.com'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'https://www.fqparty.com'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('工会招募'),
                            'url' => 'https://image.fqparty.com//banner/20200610/4465c83eb7641c5330f9e30c0fce3448.png'
                        ),
                        array(
                            'type' => 'click',
                            'name' => urlencode('注意事项'),
                            'key' => 'Pay_888'
                        )
                    )
                ),
                array(
                    'name'=>urlencode("充值相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('充值'),
                            'url' => 'https://www.fqparty.com/web/gzhpay'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('提现'),
                            'url' => 'http://gl.muayuyin.com/web/webUserWithdrawal/withdrawalLogin'
                        )
                    )
                )
            ),
            'matchrule' => array(
                'client_platform_type' => 2
            ),
        );
        $jsondata = urldecode(json_encode($arr));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    public function wxIndex() {
        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            if(strtolower($postObj->MsgType) == 'event') {
                if (strtolower($postObj->Event) == 'subscribe') {
                    //欢迎语
                    $toUser = $postObj->FromUserName;
                    $fromUser = $postObj->ToUserName;
                    $time = time();
                    $msgType = 'text';
                    $content = '恭喜你！成为音恋一员~
处CP交友通通都可以哦~
各种小游戏，你画我猜 谁是卧底让您体验哦~
欢迎关注音恋哦！
希望小番茄们继续支持我们哦~

注意:苹果手机用户回复"充值"获取充值链接';
                    $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
<Content>hi，小伙伴们，欢迎来到音恋声音王国~
非常感谢您的关注~音恋将为您提供专属服务哦！
 
☞回复 【下载】
恋爱交友，心动速配，总有人为你而来。
 游戏开黑，实时语音；K歌房间，想唱就唱；互动社区，美好生活，甜满你整个世界。更多玩法等你体验，赶紧下载吧~
 
☞苹果充值请点击【&lt;a href=&quot;https://www.fqparty.com/web/gzhpay&quot;&gt;充值&lt;/a&gt;】
☞提现服务请点击【&lt;a href=&quot;http://gl.muayuyin.com/web/webUserWithdrawal/withdrawalLogin&quot;&gt;提现&lt;/a&gt;】
☞如有疑问请咨询音恋语音QQ客服：3425184378
☞支付宝充值链接请点击http://newmapi2.fqparty.com/web/wxpay</Content>
                            </xml>";
                    $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                    echo $info;
                }
                if (strtolower($postObj->Event) == 'click') {
                    if(strtolower($postObj->EventKey) == 'pay_888') {
                        $toUser = $postObj->FromUserName;
                        $fromUser = $postObj->ToUserName;
                        $time = time();
                        $msgType = 'text';
                        $content = '苹果手机用户回复"充值"获取充值链接';
                        $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            </xml>";
                        $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                        echo $info;
                    }
                }
            } elseif(strtolower($postObj->MsgType) == 'text') {
                if(!empty( $keyword ))
                {
                    $msgType = "text";
                    switch ($keyword)
                    {
                        case "充值";
                            $contentStr = "公众号充值：
https://www.fqparty.com/web/gzhpay
网页充值：
http://newmapi2.fqparty.com/web/wxpay";
                            break;
                        case "chongzhi";
                            $contentStr = "https://www.fqparty.com/web/gzhpay";
                            break;
                        default;
                            $contentStr = "http://newmapi2.fqparty.com/web/wxpay";

                    }

                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                }
            }
        }else {
            echo "";
            exit;
        }
    }


    public function check() {
        if (isset($_GET['echostr'])) {
            if ($this->checkSignature()) {
                echo $_GET['echostr'];
            }
            exit();
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = 'yinlian';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }


}
