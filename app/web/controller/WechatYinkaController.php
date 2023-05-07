<?php
namespace app\web\controller;
//define your token
use app\BaseController;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\CommonUtil;
use think\facade\Log;

class WechatYinkaController extends BaseController
{
    private $fromUsername;
    private $toUsername;
    private $times;
    private $keyword;
    private $app_id = 'wxae30b19810e724c5';
    private $app_secret = '56ebbfe59f5fd2bb512469e58486c6a0';
    private $EncodingAESKey = 'tByjObyhPtNneGdNn4MNVLbV2m8kNzsxjPJWQ70OFip';
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
        $result = file_get_contents($url);
        $data = json_decode($result,true);
        Log::info(sprintf('get_access_token result:%s', $result));
        if ($data !== null && $data['access_token'] && $data['expires_in'] == 7200) {
            return $data['access_token'];
        } else{
            throw new FQException('未知错误，请重试',500);
        }
    }

    public function delMenu() {
        $access_token = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=". $access_token;
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
                    'url'=>'http://www.ddyuyin.com/#/download'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'http://www.ddyuyin.com/#/'
                        ),
//                        array(
//                            'type' => 'view',
//                            'name' => urlencode('公会招募'),
//                            'url' => 'https://image2.fqparty.com/resource/html/gonghuizhaomu.html'
//                        ),
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
                            'url' => 'http://www.ddyuyin.com/#/topup'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('提现'),
                            'url' => 'http://www.ddyuyin.com/tixian/#/login'
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
                    'url'=>'http://www.ddyuyin.com/#/download'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'http://www.ddyuyin.com/#/'
                        ),
//                        array(
//                            'type' => 'view',
//                            'name' => urlencode('公会招募'),
//                            'url' => 'https://image2.fqparty.com/resource/html/gonghuizhaomu.html'
//                        ),
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
                    'url' => 'http://www.ddyuyin.com/tixian/#/login'
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
                    'url'=>'http://www.ddyuyin.com/#/download'
                ),
                array(
                    'name' => urlencode("产品相关"),
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => urlencode('公司官网'),
                            'url' => 'http://www.ddyuyin.com/#/'
                        ),
//                        array(
//                            'type' => 'view',
//                            'name' => urlencode('公会招募'),
//                            'url' => 'https://image2.fqparty.com/resource/html/gonghuizhaomu.html'
//                        ),
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
                            'url' => 'http://www.ddyuyin.com/#/topup'
                        ),
                        array(
                            'type' => 'view',
                            'name' => urlencode('提现'),
                            'url' => 'http://www.ddyuyin.com/tixian/#/login'
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
                    $content = '恭喜你！成为音咖一员~
处CP交友通通都可以哦~
各种小游戏，你画我猜 谁是卧底让您体验哦~
欢迎关注音咖哦！
希望小番茄们继续支持我们哦~

注意:苹果手机用户回复"充值"获取充值链接';
                    $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
<Content>hi，小伙伴，欢迎来到音咖语音~
非常感谢您的关注~我们将为您提供专属服务哦！
 
☞回复 【下载】
恋爱交友，心动速配，总有人为你而来。
 游戏开黑，实时语音；K歌房间，想唱就唱；互动社区，美好生活，甜满你整个世界。更多玩法等你体验，赶紧下载吧~
 
☞【&lt;a href=&quot;http://www.ddyuyin.com/#/topup&quot;&gt;官网充值&lt;/a&gt;】
☞提现服务请点击【&lt;a href=&quot;http://www.ddyuyin.com/tixian/#/login&quot;&gt;提现&lt;/a&gt;】
☞如有疑问请咨询音咖语音QQ客服：3425184378，我们的客服将会在第一时间为您处理，感谢您的关注~
</Content>
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
                            $contentStr = "亲爱的小伙伴，应苹果政策要求，公众号菜单栏的iOS充值功能将暂时关闭（安卓用户不受影响），如需充值，请点击".
                                "【<a href='http://www.ddyuyin.com/#/topup'>充值链接</a>】"."进入官网，在官网中进行充值哦~
如有其它充值问题，请在APP内咨询在线客服，我们将在24小时内为您解答哦~";
                            break;
                        default;
                            $contentStr = "亲爱的小伙伴，应苹果政策要求，公众号菜单栏的iOS充值功能将暂时关闭（安卓用户不受影响），如需充值，请点击".
                                "【<a href='http://www.ddyuyin.com/#/topup'>充值链接</a>】"."进入官网，在官网中进行充值哦~
如有其它充值问题，请在APP内咨询在线客服，我们将在24小时内为您解答哦~";
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

        $token = 'yinka';
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

    public function getJsSdkParams() {
        $url = $this->request->param('url');
        $redis = RedisCommon::getInstance()->getRedis();
        $ticket = $redis->get('wechatTicket');
        if (empty($ticket)) {
            $ticket = $this->getticket();
        };
        // 时间戳
        $timestamp = time();
        // 随机字符串
        $nonceStr = $this->createNoncestr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序 j -> n -> t -> u
        $string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array (
            "appId" => $this->app_id,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
        );
        return rjson($signPackage);

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





    public function getticket() {
        $redis = RedisCommon::getInstance()->getRedis();
        $token = $redis->get('wechatToken');
        if (empty($token)) {
            $token = $this->get_access_token();
            $redis->set('wechatToken', $token);
            $redis->expireAt('wechatToken', time() + 7000);
        }
        if ($token) {
            $ticket = $this->getTicketTmpl($token);
            if ($ticket) {
                $redis->set('wechatTicket', $ticket);
                $redis->expireAt('wechatTicket', time() + 7000);
            }
            return $ticket;
        } else {
            throw new FQException('未知错误,请重试',500);
        }
    }

    public function getTicketTmpl($accessToken) {
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=". $accessToken ."&type=jsapi";
        $result = '';
        try {
            $result = file_get_contents($url);
            Log::info(sprintf('getTicketTmpl result:%s', $result));
            $res = json_decode($result, true);
            if ($res !== null && $res['ticket'] && $res['expires_in'] == 7200) {
                return $res['ticket'];
            }
            throw new FQException('未知错误，请重试',500);
        } catch (\Exception $e) {
            Log::error(sprintf('getTicketTmpl: res:%s error:%s:%s', $result, $e->getCode(), $e->getMessage()) );
            throw new FQException('未知错误，请重试',500);
        }


    }


}
