<?php

namespace app\common;

use ZEGO\ZegoServerAssistant;
use ZEGO\ZegoErrorCodes;
use think\facade\Log;

class ZeGoCommon
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZegoCommon();
        }
        return self::$instance;
    }

    public function getToken($userId)
    {
        try {
            $appId = config('config.zeGo.appId');
            $secret = config('config.zeGo.secret');
            $token = '';
            $i = 0;
            while ($i < 2) {
                $token = ZegoServerAssistant::generateToken04((int)$appId, (string)$userId, $secret, 86400);
                Log::info(sprintf('ZeGoCommon getToken Exception userId=%d info=%s', $userId, json_encode($token)));
                if ($token->code == ZegoErrorCodes::success) {
                    return $token->token;
                } else {
                    Log::error(sprintf('ZeGoCommon getToken Error Exception userId=%d info=%s', $userId, json_encode($token)));
                }
                $i++;
            }
            return $token;
        }catch (\Exception $e) {
            Log::error(sprintf("ZeGoCommon getToken Error userId:%s errCode:%s errEx:%s", $userId,$e->getCode(), $e->getMessage()));
            return '';
        }
    }

}