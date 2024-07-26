<?php


namespace app\api\controller\v1;


use app\BaseController;
use app\domain\models\AuditNotifyModel;
use \app\facade\RequestAes as Request;
use think\facade\Log;

class NotifyController extends BaseController
{
    /*
     https://cloud.tencent.com/document/product/436/60769
     {
    "code":0,
    "message":"success",
    "data":{
        "url":"http://like-game-1318171620.cos.ap-beijing.myqcloud.com/1056141/1686985702870.amr",
        "result":1,
        "forbidden_status":0,
        "trace_id":"aabf67d3660cdd11eeac825254009dadc6",
        "event":"ReviewAudio",
        "porn_info":{
            "hit_flag":0,
            "score":3,
            "label":""
        },
        "terrorist_info":{
            "hit_flag":1,
            "score":91,
            "label":"傻逼"
        },
        "politics_info":{
            "hit_flag":0,
            "score":0,
            "label":""
        },
        "ads_info":{
            "hit_flag":0,
            "score":0,
            "label":""
        }
    }
}
     */
    public function tencentAuditAutoCheck() {
        $params = Request::param();
        Log::info('tencentAuditAutoCheck----'.json_encode($params));
        if (!isset($params['code']) || $params['code'] != 0) {
            return;
        }

        $data = $params['data'] ?? [];
        if (!isset($data['url'])) {
            return;
        }

        $time = time();
        $insert = [
            'url' => $data['url'] ?? '',
            'result' => $data['result'] ?? 0,
            'forbidden_status' => $data['forbidden_status'] ?? 0,
            'trace_id' => $data['trace_id'] ?? '',
            'event' => $data['event'] ?? '',
            'path' => substr($data['url'],strlen(config('config.APP_URL_image')) ),
            'porn_info' => json_encode($data['porn_info'] ?? []),
            'ads_info' => json_encode($data['ads_info'] ?? []),
            'terrorist_info' => json_encode($data['terrorist_info'] ?? []),
            'politics_info' => json_encode($data['politics_info'] ?? []),
            'params' => json_encode($params),
            'create_time' => $time,
            'update_time' => $time,
        ];

        AuditNotifyModel::getInstance()->getModel()->insert($insert);
    }

}