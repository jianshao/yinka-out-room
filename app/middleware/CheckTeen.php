<?php

namespace app\middleware;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\user\service\MonitoringService;
use app\utils\HelperUnit;
use think\facade\Log;

/**
 * @info  验证青少年模式
 * Class CheckTeen
 * @package app\middleware
 */
class CheckTeen
{
    // 青少年模式禁止访问的接口
    private $urlList = [
        "/api/v1/iosBuyProduct",    // 充值
        "/api/v3/iosBuyProduct",
        "/api/v1/AndroidBuyProduct",
        "/api/v3/AndroidBuyProduct",

        "/api/v1/appVipPayment",    // 开通会员
        "/api/v3/appVipPayment",

        "/api/v1/game/buyGoods",    // 购买游戏徽章
        "/api/v3/game/buyGoods",

        "/api/v1/beanchanggecoin",    // 兑换
        "/api/v3/beanchanggecoin",
        "/api/v1/diamondexchanggecoin",
        "/api/v3/diamondexchanggecoin",

        "/api/v1/sendroomgift", // 送礼
        "/api/v3/sendroomgift",

        "/api/v1/sendpackets", // 发红包
        "/api/v3/sendpackets",
    ];

    public function handle($request, \Closure $next)
    {
        $this->fithandle($request);
        return $next($request);
    }

    private function fithandle($request)
    {
        $baseUrl = $request->baseUrl();
        if (!in_array($baseUrl, $this->urlList)) {
            return;
        }

        $token = HelperUnit::getToken();
        $redis = RedisCommon::getInstance()->getRedis();
        $uid = $redis->get($token);
        // 青少年过滤
        list($enable, $monitoringTime) = MonitoringService::getInstance()->getMonitor($uid);
        Log::info("middleware.CheckTeen uid:$uid token:$token baseUrl:$baseUrl enable:$enable");
        if ($enable) {
            // 青少年模式禁止充值和消费
            throw new FQException("青少年模式下无法使用", 500);
        }
    }
}
