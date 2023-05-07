<?php

namespace app\middleware;

use think\Exception;

//限流器 default:60s2次 间隔30s1次
class LeakyBucket
{
    public function handle($request, \Closure $next)
    {
        try {
            $this->fitHandle($request);
            return $next($request);
        } catch (\Exception $e) {
            return rjsonFit([], $e->getCode(), $e->getMessage());
        }
    }

    private function fitHandle($request)
    {
        $key = $this->getLeakykey($request);
        $max_burst = config('leaky_bucket.max_burst', 2);
        $tokens = config('leaky_bucket.tokens', 5);
        $seconds = config('leaky_bucket.seconds', 60);
        $server = new \app\common\server\LeakyBucket($key, $max_burst, $tokens, $seconds);
        if ($server->isPass()) {
            throw new Exception('操作频繁，请稍后再试', 500);
        }
        return true;
    }

    private function getLeakykey($request)
    {
        $token = $request->header('token', "");
        $ip = $request->ip(0, false);
        return sprintf("%s-%s", $ip, $token);
    }
}
