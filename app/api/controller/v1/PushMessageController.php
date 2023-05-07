<?php


namespace app\api\controller\v1;


use app\BaseController;
use app\domain\exceptions\FQException;
use app\domain\notice\dao\PushReportModelDao;
use app\domain\notice\model\PushReportModel;
use app\domain\open\model\GetuiCallbackModel;
use app\domain\open\service\GetuiService;
use app\domain\recall\dao\PushRecallConfModelDao;
use app\domain\recall\service\MemberRecallService;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;
use app\utils\Error;
use think\facade\Log;
use \app\facade\RequestAes as Request;


//消息推送相关

class PushMessageController extends BaseController
{

    /**
     * @info 创蓝回调上报  业务调整 （废弃）
     * @example  url:http://client_url?receiver=&pswd=&msgid=1811&reportTime=1811&mobile=1520000000&status=DELIVRD&notifyTime=181119000031&statusDesc=成功&length=1
     * array:9 [
     * "receiver" => ""
     * "pswd" => ""
     * "msgid" => "1811"
     * "reportTime" => "1811"
     * "mobile" => "1520000000"
     * "status" => "DELIVRD"
     * "notifyTime" => "181119000031"
     * "statusDesc" => "成功"
     * "length" => "1"
     * ]
     */
    public function chuanglanSmsCallback()
    {
        $params = Request::param();
        if (empty($params)) {
            return $this->rjsonOut("111111");
        }
        try {
            $originParamsStr = json_encode($params);
            $model = new PushReportModel();
            $model->platform = "chuanglansms";
            $model->receiver = $params['receiver'] ?? "";
            $model->pswd = $params['pswd'] ?? "";
            $model->msgId = $params['msg_id'] ?? "";
            $model->reportTime = $params['report_time'] ?? "";
            $model->mobile = $params['mobile'] ?? "";
            $model->status = $params['status'] ?? "";
            $model->notifyTime = $params['notify_time'] ?? "";
            $model->statusDesc = $params['status_desc'] ?? "";
            $model->uid = $params['uid'] ?? "";
            $model->length = $params['length'] ?? 0;
            $model->originParam = $originParamsStr;
            $model->createTime = time();
            PushReportModelDao::getInstance()->store($model);
            return $this->rjsonOut("000000");
        } catch (\Exception $e) {
            Log::error(sprintf('errorMsg %s errorCode %d errorLine %d', $e->getMessage(), $e->getCode(), $e->getLine()));
            return $this->rjsonOut("111111");
        }
    }

    private function rjsonOut($codeMsg)
    {
        $out['clcode'] = $codeMsg;
        Log::info(Request::action() . '---' . Request::header('token') . '---返回值 : ' . json_encode($out));
        Log::record("\n\r", 'debug');
        return json($out);
    }


    /**
     * @throws FQException
     * @info 个推上报 todo https://docs.getui.com/getui/server/receipt/
     * 1.1 数据传递 协议说明
     * 1） 协议使用 HTTP POST 方式，通过调用第三方预先提供的 URL 来传递信息;
     * 2） POST 数据的内容采用文本格式，每行表示一条消息回执，一次请求可以有一行或多行，每一行的文本内容为 JSON 串;
     * 3） 第三方接口接收请求后有 HTTP 请求响应，无论是否第三方正常处理都不会再尝试重发;
     * 4） 如果请求第三方接口异常（如：发生网络异常、无响应等），尝试三次后如果还是失败将丢弃;
     * {
     * "code":"200",
     * "recvtime":1559252242250, 回执上传时间
     * "appid":"laaN0B4deu8HMBXm141",  应用 ID
     * "sign":"84702a155dc2a13ad7e037b29c751a",
     * "msgid":"1559252299573551004101",
     * "actionId":"110000",
     * "taskid":"OSS-0531_RZBMR2SwQj66QmqOy86",
     * "desc":"ok",
     * "cid":"d12403fad5a8b5e94896f8b65be228"
     * }
     */
    public function getuiCallback()
    {
        $originData = file_get_contents('php://input');
        if (empty($originData)) {
            throw new FQException("param error", 500);
        }
        $originStrArr = explode("\n", $originData);
        $haveError = false;
        $unixTime = time();
        $masterSecret = $this->getMasterSecret();
        foreach ($originStrArr as $itemOriginData) {
            try {
                $getuiCallbackModel = new GetuiCallbackModel();
                $getuiCallbackModel->dataToModel(json_decode($itemOriginData, true));
                if ((int)$getuiCallbackModel->code !== 200) {
                    throw new FQException("push error", 500);
                }
                if (empty($getuiCallbackModel->taskid) || empty($getuiCallbackModel->cid)) {
                    throw new FQException("getuiCallback decode error", 500);
                }
//                过滤日志上报记录
                GetuiService::getInstance()->filterItemGetuiCall($getuiCallbackModel);
                GetuiService::getInstance()->itemGetuiCallBack($getuiCallbackModel, $itemOriginData, $unixTime, $masterSecret);
            } catch (\Exception $e) {
                Log::INFO(sprintf("PushMessageController getuiCallback error errcode:%d errmsg:%s errstrace:%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                $haveError = true;
                continue;
            }
        }
        if ($haveError) {
            return rjson([], 500, 'store error');
        }
        return rjson([], 200, 'success');
    }


    private function getMasterSecret()
    {
        $getuiConfig = config('config.getui');
        return $getuiConfig['mastersecret'] ?? "";
    }

//    /**
//     * @info 个推单条推送
//     * 验签：sign string Sign=MD5（appid+cid+taskid+msgid+masterSecret）
//     * @param $originData
//     * @param $unixTime
//     * @throws FQException
//     */
//    private function itemGetuiCallBack($originData, $unixTime, $masterSecret)
//    {
//        $paramsArr = json_decode($originData, true);
//        if (empty($paramsArr)) {
//            throw new FQException("getuiCallback decode error", 500);
//        }
//        $code = $paramsArr['code'] ? (int)$paramsArr['code'] : 0;
//        if ($code !== 200) {
//            throw new FQException("push error", 500);
//        }
//        $this->authGetuiSign($paramsArr, $masterSecret);
//        $model = new PushReportModel();
//        $model->platform = "getuipush";
//        $model->msgId = $paramsArr['msgid'] ?? "";
//        $model->taskId = $paramsArr['taskid'] ?? "";
//        $model->reportTime = $paramsArr['recvtime'] ?? "";
//        $model->mobile = $paramsArr['cid'] ?? "";
//        $model->status = $paramsArr['actionId'] ?? "";
//        $model->statusDesc = $paramsArr['desc'] ?? "";
//        $model->ext_1 = $paramsArr['appid'] ?? "";
//        $model->originParam = $originData;
//        $model->createTime = $unixTime;
//        PushReportModelDao::getInstance()->store($model);
//    }

    /**
     * 后台手动触发用户
     */
    public function touchUsers()
    {
        $id = Request::param("id", 0, 'intval');
        if (empty($id)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $pushTypeConfModel = PushRecallConfModelDao::getInstance()->loadModel($id);
        if ($pushTypeConfModel === null) {
            throw new FQException("push data error", 500);
        }
//        创建任务
        $result = MemberRecallService::getInstance()->createQueue($pushTypeConfModel);
        return rjson(['result' => $result], 200, 'success');
    }


    /**
     * @throws FQException
     * demo :"{"id":4,"push_when":{"charge_max":500,"charge_min":100,"time":3600},"push_type":"getuipush","template_ids":"[102,109]"}"
     */
    public function testCusumer()
    {
        $originStr = '{"id":4,"push_when":{"charge_max":500,"charge_min":100,"time":3600},"push_type":"getuipush","template_ids":"[3,4]"}';
        $originData = json_decode($originStr, true);
        $re = MemberRecallService::getInstance()->handlerQueueConsumer($originData);
        var_dump($re);
        die;
    }


    public function testCusumerUserPush()
    {
        $originStr = '{"push_recall_conf":"{\"id\":4,\"push_when\":{\"charge_max\":500,\"charge_min\":100,\"time\":3600},\"push_type\":\"getuipush\",\"template_ids\":\"[3,4]\"}","user_ids":"[1456410,1456408,1456402]"}';
        $originData = json_decode($originStr, true);
        $re = MemberRecallService::getInstance()->handlerUserPushConsumer($originData);
        var_dump($re);
        die;
    }


    /**
     * @throws FQException
     * demo :"{"id":117,"push_when":{"charge_max":0,"charge_min":0,"time":86400},"push_type":"rtdsms","template_ids":"[14]"}"
     */
    public function testRtdSmsCusumerTask()
    {
        $originStr = '{"id":123,"push_when":{"charge_max":0,"charge_min":0,"time":2592000},"push_type":"rtdsms","template_ids":"[\"20\",\"21\",\"22\"]"}';
        $originData = json_decode($originStr, true);
        $re = MemberRecallService::getInstance()->handlerQueueConsumer($originData);
        var_dump($re);
        die;
    }

    /**
     * @throws FQException
     * @demo {"push_recall_conf":"{\"id\":117,\"push_when\":{\"charge_max\":0,\"charge_min\":0,\"time\":86400},\"push_type\":\"rtdsms\",\"template_ids\":\"[14]\"}","user_ids":"[1702863,1702862,1697567,1697566]"}
     */
    public function testRtdSmsCusumerUserPush()
    {
//        $originStr = '{"push_recall_conf":"{\"id\":117,\"push_when\":{\"charge_max\":0,\"charge_min\":0,\"time\":86400},\"push_type\":\"rtdsms\",\"template_ids\":\"[14]\"}","user_ids":"[1702863,1702862,1697567,1697566]"}';
        $originStr = '{"push_recall_conf":"{\"id\":123,\"push_when\":{\"charge_max\":0,\"charge_min\":0,\"time\":2592000},\"push_type\":\"rtdsms\",\"template_ids\":\"[20,21,22]\"}","user_ids":"[1702863,1702862,1697567,1697566,1697565]"}';
//        $originStr = '{"push_recall_conf":"{\"id\":4,\"push_when\":{\"charge_max\":500,\"charge_min\":100,\"time\":3600},\"push_type\":\"getuipush\",\"template_ids\":\"[3,4]\"}","user_ids":"[1456410,1456408,1456402]"}';
        $originData = json_decode($originStr, true);
        $re = MemberRecallService::getInstance()->handlerUserPushConsumer($originData);
        var_dump($re);
        die;
    }


    /**
     * @return \think\response\Json
     * @throws FQException
     */
    public function shumeiCheck()
    {
        $message = Request::param("message", "");
        $msgType = Request::param("msgType", "");
        if ($message === "" || $msgType === "") {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        if ($msgType === "text") {
            /* 文本检测 */
            $checkStatus = ShuMeiCheck::getInstance()->textCheck($message, ShuMeiCheckType::$TEXT_MESSAGE_EVENT, 9999999999);
            if (!$checkStatus) {
                throw new FQException('昵称包含敏感字符', 500);
            }
        }
        return rjson([], 200, 'success');
    }

}


























