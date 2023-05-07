<?php

namespace app\middleware;

use think\facade\App;
use think\facade\Log;

//记录请求返回值所有信息到日志
class ResponseLog
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        $runtime = number_format(microtime(true) - App::getBeginTime(), 10, '.', '');
        $reqs = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem = number_format((memory_get_usage() - App::getBeginMem()) / 1024, 2);
        Log::info('运行时间:' . $runtime . 's [ 吞吐率：' . $reqs . 'req/s ] 内存消耗：' . $mem . 'kb 文件加载：' . count(get_included_files()));
        return $response;
    }

}
