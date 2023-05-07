<?php
namespace app\api\controller\v1;

use \app\facade\RequestAes as Request;
use think\cache\driver\Redis;
use think\facade\Log;
use app\common\GetuiCommon;

class PushOfGetuiController
{

    /**
     * 单推
     */

    public function pushToSingle()
    {
        $request = Request::param();
        if(empty($request)) {
            Log::record('单推参数错误：----'. json_encode($request), 'info');
            return rjson([], 500, '失败');
        }
        $cid = $request['uid'];
        $type = isset($request['type']) ? $request['type'] : 0 ;
        $result = GetuiCommon::getInstance()->pushMessageToSingle($cid, $type);
        if($result['result'] == 'ok') {
            Log::record('单推成功：----'. json_encode($result), 'info');
            return rjson([], 200, '成功');
        } else {
            //写入日志
            Log::record('单推失败：----'. json_encode($result), 'info');
            return rjson([], 500, '失败');
        }
    }

    /**
     * 群推
     */
    public function pushToList() {
        $cid = Request::param('uid');
        $uidArr = json_decode($cid, true);
        if(empty($uidArr)) {
            return rjson([], 500, '参数错误');
        }
        $uidArr = array_chunk($uidArr, 800);
        foreach($uidArr as $k=>$v) {
            $result = GetuiCommon::getInstance()->pushMessageToList($v);
            Log::record('推送结果：'. $result['result'], 'info');
        }
        return rjson([],'200','成功');
    }



}