<?php
namespace app\domain\sms\api;

/* *
 * 类名：RongtongdaSmsApi
 * 功能：蓉通达接口请求类
 * @info: 【Http接口参数】
            提交地址:122.112.230.64:8001
            用户名:642799
            密码:3mAfhpmj
 * 日期：2022-02-14
 * 说明：自己封装的服务api接口类
 */

use app\utils\CommonUtil;
use app\utils\RequestOrm;

class RongtongdaSmsApi
{
    const API_SEND_SMS_API = 'http://122.112.230.64:8001/mt.ashx';// 发送单人短信
    const API_SENDSMSLIST_API = 'http://122.112.230.64:8001/mts.ashx';// 群推短信 发送给多人
    const API_SENDVARIABLESMS_API = 'http://122.112.230.64:8001/mts_var_json.ashx';// 多变量群发接口 发送给多人
    const API_RPTREPORT_URL = 'http://122.112.230.64:8001/rpt.ashx';// 短信状态报告
    const API_ACCOUNT_API = 'http://122.112.230.64:8001/bi.ashx';// 查询rtd账户信息


    const API_ACCOUNT = '642799'; // 账号
    const API_PASSWORD = '3mAfhpmj';// 密码

    /**
     * 发送单人短信
     * @param string $mobile 手机号码
     * @param string $msg 短信内容
     * @param string $needstatus 是否需要状态报告
     */
    public function sendSMS($mobile, $msg)
    {
        $msg = urlencode($msg);
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $link = sprintf('%s?account=%s&pswd=%s&msg=%s&pn=%d', self::API_SEND_SMS_API, self::API_ACCOUNT, self::API_PASSWORD, $msg, $mobile);
        return $requestObj->get($link);
    }

    /**
     * 群推短信 发送给多人
     * @param string $mobile 手机号码
     * @param string $msg 短信内容
     * @param string $needstatus 是否需要状态报告
     */
    public function sendSMSList($mobiles, $msg)
    {
        $msg = urlencode($msg);
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $link = sprintf('%s?account=%s&pswd=%s&msg=%s&pn=%s', self::API_SENDSMSLIST_API, self::API_ACCOUNT, self::API_PASSWORD, $msg, $mobiles);
        return $requestObj->get($link);
    }

    /**
     * 群推短信 发送给多人
     * @param string $mobile 手机号码
     * @param string $msg 短信内容
     * @param string $needstatus 是否需要状态报告
     */
    public function sendSMSListPost($mobiles, $msg)
    {
        if (config("config.addDev") === "dev") {
            return "20220214190512,0,202202141124479690";
        }
        $requestObj = new RequestOrm();
        $link = self::API_SENDSMSLIST_API;
        $data = [
            'account' => self::API_ACCOUNT,
            'pswd' => self::API_PASSWORD,
            'msg' => $msg,
            'pn' => $mobiles,
        ];
        return $requestObj->post($link, $data);
    }

    /**
     * @info 查询短信的状态报告,
     * 发送成功后才能拉取到
     * 该接口为增量拉取，只能拉取一次数据，拉过的就获取不到了
     * @return string
     */
    public function rptReport()
    {
        if (config('config.appDev') === "dev") {
            return <<<str
20220215102829,uid=839&uname=642799&seq=202202151124484514&pn=18515985536&stm=20220215102736&sc=DELIVRD&st=20220215102746&bid=202202151124484513&pid=1
uid=839&uname=642799&seq=202202151124484510&pn=18811310446&stm=20220215102737&sc=DELIVRD&st=20220215102749&bid=202202151124484508&pid=1
uid=839&uname=642799&seq=202202151124484509&pn=18515985536&stm=20220215102736&sc=DELIVRD&st=20220215102750&bid=202202151124484508&pid=1
str;

//            return <<<str
//20220215102406,frequency deny.please query slowly.f:5sec.
//str;

//            return <<<str
//20220215101646,""
//str;
        }
        $requestObj = new RequestOrm();
        $link = sprintf('%s?account=%s&pswd=%s', self::API_RPTREPORT_URL, self::API_ACCOUNT, self::API_PASSWORD);
        return $requestObj->get($link);
    }

    /**
     * 多变量群发接口 发送给多人
     * @param string $mobile 手机号码
     * @param string $msg 短信内容
     * @param string $needstatus 是否需要状态报告
     * http://serverip:8001/mts_var_json.ashx?account=xxx&pswd=xxx&vars=[{"pn":"pn1","msg ":"内容 1。 "},{"pn":"pn2","msg":"内容 2。"}]
     */
    public function sendVariableSMS($vars)
    {
        if (CommonUtil::getAppDev()) {
            return '{"ct":"20220214190142","bid":202202141124479688,"seqs":[202202141124479689],"smsids":[""]}';
        }
        $requestObj = new RequestOrm();
        $link = self::API_SENDVARIABLESMS_API;
        $data = [
            'account' => self::API_ACCOUNT,
            'pswd' => self::API_PASSWORD,
            'vars' => json_encode($vars),
        ];
        return $requestObj->post($link, $data);
    }

    /**
     * @return string
     * @response demo:"20220221180640,0,92 , Access:WEB ; Status:Pause ; Report:25 ; Mo:0 ; Today RptIn:78 ; Today RptOut:53 ; Today MoIn:0 ; Today MoOut:0 ;"
     * "20220318103758,0,250117 , Access: ; Status:Pause ; Report:0 ; Mo:0 ; Today RptIn:0 ; Today RptOut:0 ; Today MoIn:0 ; Today MoOut:0 ;"
     */
    public function getAccountData(){
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $link = sprintf('%s?account=%s&pswd=%s', self::API_ACCOUNT_API, self::API_ACCOUNT, self::API_PASSWORD);
        return $requestObj->get($link);
    }


    /**
     * @info test
     */
    public function testSendSMS()
    {
        $phone = 18515985536;
        $msg = "【音恋 语音】kkk,来领动态头像框~已放入您的背包rongqii.cn/1456391 退T";
        $result = $this->sendSMS($phone, $msg);
        var_dump($result);
    }

    /**
     * @info test
     * string(35) "20220214161538,0,202202141124278002"
     */
    public function testSendSMSList()
    {
        $phones = '18515985536,18811310446';
        $msg = "【音恋 语音】滔滔不绝,来领动态头像框~已放入您的背包rongqii.cn/1439778 退T";
        $result = $this->sendSMSList($phones, $msg);
        var_dump($result);
    }

    /**
     * @info test
     * string(90) "{"ct":"20220214190142","bid":202202141124479688,"seqs":[202202141124479689],"smsids":[""]}"
     * success
     */
    public function testSendVariableSMS()
    {
        $phones = '18515985536';
        $msg = "【音恋 语音】滔滔淘气var,来领动态头像框~已放入您的背包 rongqii.cn/1439442 退T";
        $varsArr = [];
        $itemVar = [
            'pn' => $phones,
            'msg' => $msg,
        ];
        $phones = '15810501263';
        $msg = "【音恋 语音】kkk小明,来领动态头像框~已放入您的背包 rongqii.cn/1439778 退T";
        $itemVarSecond = [
            'pn' => $phones,
            'msg' => $msg,
        ];
        $varsArr[] = $itemVar;
        $varsArr[] = $itemVarSecond;
        $result = $this->sendVariableSMS($varsArr);
        var_dump($result);
    }


    /**
     * @info test
     * success
     * string(35) "20220214190512,0,202202141124479690"
     */
    public function testSendSMSListPost()
    {
        $phones = '18515985536,18811310446';
        $msg = "【音恋 语音】滔滔不绝kk,来领动态头像框~已放入您的背包rongqii.cn/1439778 退T";
        $result = $this->sendSMSListPost($phones, $msg);
        var_dump($result);
    }


    /**
     * @info test
     *
     */
//""
//    20220214194551,uid=839&uname=642799&seq=202202141124479740&pn=18515985536&stm=20220214194504&sc=DELIVRD&st=20220214194520&bid=202202141124479739&pid=1
//    uid=839&uname=642799&seq=202202141124479738&pn=18515985536&stm=20220214194504&sc=DELIVRD&st=20220214194522&bid=202202141124479737&pid=1
//    uid=839&uname=642799&seq=202202141124479741&pn=18811310446&stm=20220214194504&sc=DELIVRD&st=20220214194522&bid=202202141124479739&pid=1
//    ""


    public function testRptReport()
    {
        $result = $this->rptReport();
        var_dump($result);
    }

}


?>
