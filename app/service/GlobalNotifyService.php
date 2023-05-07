<?php

namespace app\service;


use app\domain\queue\producer\NotifyMessage;

use think\facade\Log;

class GlobalNotifyService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GlobalNotifyService();
        }
        return self::$instance;
    }

    /**
     * @param $userId
     * @param $data
     * @return string
     */
    public function redDotNotifyForUser($userId, $data)
    {
        $msg = [
            'userId' => intval($userId),
            'msg' => json_encode([
                'type' => 'reddot',
                'data' => $data,
            ]),
        ];
        $data = json_encode($msg);
        $url = config('config.socket_url_base') . 'iapi/globalUserNotify';
        return NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
    }

}