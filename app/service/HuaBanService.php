<?php

namespace app\service;

use app\common\RedisCommon;
use app\common\TLSSigAPIv2;

class HuaBanService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new HuaBanService();
        }
        return self::$instance;
    }

    public function getTls($userId) {
        $key = 'huaban_' . $userId;
        $redis = RedisCommon::getInstance()->getRedis();
        $huaban = $redis->get($key);
        $huabanTtl = $redis->ttl($key);
        if (empty($huaban) || $huabanTtl < 86400) {
            $tls = TLSSigAPIv2::getInstance()->genSig($userId);
            $redis->setex($key, 86400 * 180, $tls);
            return $tls;
        } else {
            return $huaban;
        }
    }
}