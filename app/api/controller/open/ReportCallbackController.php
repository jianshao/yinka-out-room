<?php

namespace app\api\controller\open;

use app\domain\exceptions\FQException;
use app\domain\open\service\BiZhanService;
use app\domain\open\service\KuaishouService;
use app\domain\open\service\OppoService;
use app\domain\open\service\SmsService;
use app\domain\open\service\ToutiaoService;
use app\utils\Error;
use think\facade\Log;
use think\facade\Request;

class ReportCallbackController
{

    /**
     * @info 快手广告上报：接口一可以帮助客户接收聚星服务器提供的视频作品点击数据
     * @example https://www.example.com?xxx=XXXX&missionId=289382838&orderId=12398928&idfaMD5=D4C00E03AC3BFAFE392A310E5D35807F&imeiMD5=&callback=http://ad.partner.gifshow.com/track/activate?callback=DHAJASALKFyk1uCKBYCyXp-iIDS-uHDd_a5SJ9Dbwkqv46dahahd87TW7hhkJkd
     * @return \think\response\Json
     */
    public function kuaishouReport()
    {
        $missionId = Request::param('missionId');
        $orderId = Request::param('orderId');
        $idfaMD5 = Request::param('idfaMD5');
        $imeiMD5 = Request::param('imeiMD5');
        $callback = Request::param('callback');

        $result = KuaishouService::getInstance()->kuaishouReport($missionId, $orderId, $idfaMD5, $imeiMD5, $callback);

        Log::INFO(sprintf("ReportCallbackController::kuaishouReport success result=%d", $result));
        return rjson([], 200, '上报成功');
    }

    /**
     * @info 上报测试接口，根据设备号模拟有效上报
     */
    public function loadKuaishouReport()
    {
        $idfaMD5 = Request::param('idfaMD5');
        $imeiMD5 = Request::param('imeiMD5');
        $result = KuaishouService::getInstance()->juxingReport($idfaMD5, $imeiMD5);
        return rjson(['result' => $result], 200, 'success');
    }

    /**
     * 头条上报
     */
    public function toutiaoReport()
    {
        $aid = Request::param('aid');
        $cid = Request::param('cid');
        $idfa = Request::param('idfa');
        $imei = Request::param('imei');
        $mac = Request::param('mac');
        $oaid = Request::param('oaid');
        $androidid = Request::param('androidid');
        $os = Request::param('os');
        $tempstamp = Request::param('TIMESTAMP');
        $callback = Request::param('callback');

        $result = ToutiaoService::getInstance()->toutiaoReport($aid, $cid, $idfa, $imei, $mac, $oaid, $androidid, $os, $tempstamp, $callback);
        Log::INFO(sprintf("ReportCallbackController::toutiaoReport success result=%d", $result));
        return rjson([], 200, '上报成功');
    }


    /**
     * 星图上报
     * @监控链接 url: https://test.php.fqparty.com/api/v1/xingtuReport?idfa=__IDFA__&imei=__IMEI__&mac=__MAC__&oaid=__OAID__&oaidMd5=__OAID_MD5__&androidid=__ANDROIDID__&os=__OS__&TIMESTAMP=__TS__&demand_id=__DEMAND_ID__&item_id=__ITEM_ID__&callback=__CALLBACK_PARAM__
     * @request example: /api/v1/xingtuReport?idfa=__IDFA__&imei=__IMEI__&mac=b6457182250c7fd49717caab1d32ba70&oaid=D68016DBA93B4305B1316659FC9ECFA5338260df659a61ae4a2bfa8d57f279e8&oaidMd5=755bc6f6d47eccb7091830141c36b448&androidid=5e4418a6f2e75c9e5aa052cf950b5026&os=0&TIMESTAMP=1648631630&demand_id=__DEMAND_ID__&item_id=7069632380964769060&callback=star-f308e78a3cc098cee86d0997056662df
     */
    public function xingtuReport()
    {
        $idfa = Request::param('idfa');
        $imei = Request::param('imei');
        $mac = Request::param('mac');
        $oaid = Request::param('oaid');
        $androidid = Request::param('androidid');
        $os = Request::param('os');
        $tempstamp = Request::param('TIMESTAMP');
        $demandId = Request::param('demand_id');
        $itemId = Request::param('item_id');
        $callback = Request::param('callback');

        $result = ToutiaoService::getInstance()->xingtuReport($idfa, $imei, $mac, $oaid, $androidid, $os, $tempstamp, $demandId, $itemId, $callback);
        Log::INFO(sprintf("ReportCallbackController::xingtuReport success result=%d", $result));
        return rjson([], 200, '上报成功');
    }

    /**
     * @info 监控链接地址： Get: http://test.php.fqparty.com/api/v1/oppoReport?adid=__ADID__&imeiMd5=__IMEI__&oaid=__OAID__&timestamp=__TS__&androidid=__ANDROIDID__
     * @info 监控链接地址： Get: https://newmapi2.muayuyin.com/api/v1/oppoReport?adid=__ADID__&imeiMd5=__IMEI__&oaid=__OAID__&timestamp=__TS__&androidid=__ANDROIDID__
     * test： http://www.fanqieapi.com/api/v1/oppoReport?adid=109537846&imeiMd5=e1c3ef8265c65f7aa912250273d6ca29&oaid=C8E52A7DCF3E4675A13E43830021E9384d0c30122ec4186e59aceab74523513f&timestamp=1589869710000&androidid=9774d56d682e549c
     */
    public function oppoReport()
    {
        $adid = Request::param('adid', 0, 'intval');
        $imeiMd5 = Request::param('imeiMd5', "");
        $oaid = Request::param('oaid', "");
        $timestamp = Request::param('timestamp', 0, 'intval');
        $androidId = Request::param('androidid', "");
        $result = OppoService::getInstance()->oppoReport($adid, $imeiMd5, $oaid, $timestamp, $androidId);
        Log::INFO(sprintf("ReportCallbackController::oppoReport success result=%d", $result));
        return rjson([], 200, '上报成功');
    }


    /**
     * @Info 蓉通达短信状态报告
     * @return \think\response\Json
     * @throws FQException
     */
    public function rongtongdaReport()
    {
        $paramRaw = file_get_contents("php://input");
        if (empty($paramRaw)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $originList = explode("\n", $paramRaw);
        if (empty($originList)) {
            throw new FQException("ReportCallbackController::rongtongdaReport fatal error originList is empty", 500);
        }
//        遍历store数据
        foreach ($originList as $itemStr) {
            $result[] = SmsService::getInstance()->storeItemRongtongdaData($itemStr);
        }
        return rjson([], 200, 'success');
    }


    /**
     * http:/url/advstat/track/bili_feedback_url?trackid=__TRACKID__&crid=__CREATIVEID__&os=__OS__&model=__MODEL__&mac=__MAC1__&idfa=__IDFAMD5__&ip=__IP__&ua=__UA__&click_ts=__TS__
     * test report url: http://www.fanqieapi.com/api/v1/bizhanReport?trackId=__TRACKID__&accountId=__ACCOUNTID__&campaignId=__CAMPAIGNID__&unitId=__UNITID__&creativeId=__CREATIVEID__&os=__OS__&imei=__IMEI__&callbackUrl=__CALLBACKURL__&mac1=__MAC1__&idfaMd5=__IDFAMD5__&caId=__CAID__&aaId=__AAID__&androidId=__ANDROIDID__&oaidMd5=__OAIDMD5__&ip=__IP__&ua=__UA__&model=__MODEL__&ts=__TS__
     * online report url: https://newmapi2.muayuyin.com/api/v1/bizhanReport?trackId=__TRACKID__&accountId=__ACCOUNTID__&campaignId=__CAMPAIGNID__&unitId=__UNITID__&creativeId=__CREATIVEID__&os=__OS__&imei=__IMEI__&callbackUrl=__CALLBACKURL__&mac1=__MAC1__&idfaMd5=__IDFAMD5__&caId=__CAID__&aaId=__AAID__&androidId=__ANDROIDID__&oaidMd5=__OAIDMD5__&ip=__IP__&ua=__UA__&model=__MODEL__&ts=__TS__
     */
    public function bizhanReport()
    {
        $trackId = Request::param('trackId', "");
        $accountId = Request::param('accountId', "");
        $campaignId = Request::param('campaignId', "");
        $unitId = Request::param('unitId', "");
        $creativeId = Request::param('creativeId', "");
        $os = Request::param('os', "");
        $imei = Request::param('imei', "");
        $callbackUrl = Request::param('callbackUrl', "");
        $mac1 = Request::param('mac1', "");
        $idfaMd5 = Request::param('idfaMd5', "");
        $aaId = Request::param('aaId', "");
        $androidId = Request::param('androidId', "");
        $oaidMd5 = Request::param('oaidMd5', "");
        $ts = Request::param('ts', "");
        if ($imei === "__IMEI__" && $oaidMd5 === "__OAIDMD5__" && $idfaMd5 === "__IDFAMD5__") {
            return rjson();
        }
        $result = BiZhanService::getInstance()->report($trackId, $accountId, $campaignId, $unitId, $creativeId, $os, $imei, $callbackUrl, $mac1, $idfaMd5, $aaId, $androidId, $oaidMd5, $ts);
        Log::INFO(sprintf("ReportCallbackController::bizhanReport success result=%d", $result));
        return rjson([], 200, '上报成功');
    }

}