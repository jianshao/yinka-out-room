<?php

namespace app\domain\queue\consumer;

use app\common\GetuiCommon;
use think\facade\Log;

class GetuiMessage
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GetuiMessage();
        }
        return self::$instance;
    }

    public function notify($app, $data)
    {
        $forumId = $data['forumId'];
        $type = $data['type'];
        $res = GetuiCommon::getInstance()->pushMessageToSingle($forumId, $type);
        Log::info(sprintf("consumer GetuiMessage messageId=%s resMsg=%s", $app->getJobId(), json_encode($res)));
        $app->delete();
    }

    public function notifyList($app, $data)
    {
        $forumId = $data['forumId'];
        $type = $data['type'];
        $res = GetuiCommon::getInstance()->pushMessageToList($forumId, $type);
        Log::info(sprintf("consumer GetuiMessage messageId=%s resMsg=%s", $app->getJobId(), json_encode($res)));
        $app->delete();
    }
}