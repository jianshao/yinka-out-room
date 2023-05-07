<?php
namespace app\api\controller\inner;

use app\Base2Controller;
use app\service\RoomNotifyService;
use think\facade\Log;
use think\facade\Request;
use app\domain\exceptions\FQException;
use app\domain\shumei\ShuMeiCheckType;

class RoomNewsController extends Base2Controller
{

    /**
     * @info 后台操作 数美音频流检测开关 状态通知：
     * @param int status  状态 0关闭 1 打开
     * @return mixed
     * @throws FQException
     */
    public function audioStreamCheckSwitch()
    {

        try {
            $status = intval(Request::param('status'));
            $strFull = [
                'msgId'=>2101,
                'actionType' => 'audioStream', //类型
                'audioStreamSwitch' => $status, //音频流检测开关 1开 0关
                'audioStreamCheckRule' => ShuMeiCheckType::$AUDIO_STREAM_STREAM_TYPE,

            ];
            $msgFullScreen['msg'] = json_encode($strFull);
            $msgFullScreen['roomId'] = 0;
            $msgFullScreen['toUserId'] = '0';
            RoomNotifyService::getInstance()->notifyRoomMsg(0, $msgFullScreen);
        }catch (FQException $e) {
            Log::error(sprintf('RoomNewsController::audioStreamCheckSwitch ex=%d:%s file=%s:%d',$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }

}