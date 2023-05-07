<?php

namespace app\domain\queue\consumer;

use app\common\YunxinCommon;
use app\utils\RequestOrm;
use think\facade\Log;

class NotifyMessage
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NotifyMessage();
        }
        return self::$instance;
    }

    public function notify($app, $message)
    {
        $data = $message;
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $result = $requestObj->post($data['url'], $data['data']);
        Log::info(sprintf("consumer NotifyMessage messageId=%s resMsg=%s params:%s", $app->getJobId(), $result,json_encode($message)));
        $app->delete();
    }
}