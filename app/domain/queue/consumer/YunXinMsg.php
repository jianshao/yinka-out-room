<?php

namespace app\domain\queue\consumer;

use app\common\YunxinCommon;
use think\facade\Log;

class YunXinMsg
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YunXinMsg();
        }
        return self::$instance;
    }

    /**
     * @param $app \app\domain\queue\Job
     * @param $message
     * @return false|string
     */
    public function sendMsg($app, $message)
    {
        $data = $message;
        $res = YunxinCommon::getInstance()->sendMsg($data['from'], $data['ope'], $data['toUid'], $data['type'], $data['msg']);
        Log::info(sprintf("consumer YunXinCommon messageId=%s resMsg=%s", $app->getJobId(), json_encode($res)));
        $app->delete();
    }

}