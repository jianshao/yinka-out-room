<?php

namespace app\middleware;

use app\utils\Aes;
use think\facade\App;
use think\facade\Log;

// 记录请求返回值所有信息到日志
class BaseRawLog
{
    private $requestHash;

    public function handle($request, \Closure $next)
    {
        $this->requestHash = generateToken("requestHash");
        $params = $this->getParam($request);
        $this->setParam($request, $params);
        $response = $next($request);
//        $this->WriteAfterResponse($response->getContent());
        $this->WriteRunTime();
        return $response;
    }

    /**
     * @param $request
     * @return array
     * @throws \Exception
     */
    private function getParam($request)
    {
        $result = [];
        $param = $request->param(false);
        if (empty($param)) {
            return $result;
        }
        return $param;
    }

    /**
     * @param $request
     * @param $params
     */
    private function setParam($request, $params)
    {
        if (empty($params)) {
            return;
        }
        $request->withMiddleware($params);
    }

    /**
     * @param $requestLink
     * @param $requestParam
     */
    private function WriteBeforeParam($requestLink, $requestParam)
    {
        Log::info(sprintf('WriteBeforeParam--%s--link:%s--param:%s', $this->requestHash, $requestLink, json_encode($requestParam)));
    }


    /**
     * @param $content
     */
    private function WriteAfterResponse($content)
    {
        Log::info(sprintf('WriteAfterResponse--%s--param:%s', $this->requestHash, $content));
    }

    private function WriteRunTime()
    {
        $runtime = number_format(microtime(true) - App::getBeginTime(), 10, '.', '');
        Log::info('运行时间:' . $runtime);
    }

}
