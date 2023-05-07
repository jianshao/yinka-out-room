<?php
namespace app\middleware;

use app\domain\sensors\service\SensorsService;
use app\domain\sensors\service\SensorsUserService;
use app\utils\ArrayUtil;
use think\facade\Log;
use Exception;


class SensorsData
{

    // 过滤的路由
    private $filterUrl = [
        '/api/v1/sendsms',
        '/api/v3/sendsms',
        '/api/qq/v3/sendsms',

        '/api/v1/editUser',
        '/api/v3/editUser',
        '/api/qq/v3/editUser',

        '/api/v1/memberIdentityInit',
        '/api/v3/memberIdentityInit',
        '/api/qq/v3/memberIdentityInit',

        '/api/v1/queryIdentity',
        '/api/v3/queryIdentity',
        '/api/qq/v3/queryIdentity',

        '/api/v1/login',
        '/api/v1/userlogin',
        '/api/v3/login',
        '/api/v3/userlogin',
        '/api/qq/v3/login',
        '/api/qq/v3/userlogin',
    ];

    public function handle($request, \Closure $next)
    {
        $baseUrl = $request->baseUrl();
        if (!in_array($baseUrl, $this->filterUrl)) {
            return $next($request);
        }
        $response = $next($request);
        try {
            $result = $response->getData();
            if(is_array($result)){
                if(isset($result['sensorsData']) && !empty($result['sensorsData'])){
                    $sensorsData = $result['sensorsData'];
                    if(isset($sensorsData['service']) && $sensorsData['service'] == 'user'){
                        SensorsUserService::getInstance()->{$sensorsData['function']}($sensorsData['extra']);
                    }else{
                        SensorsService::getInstance()->{$sensorsData['function']}($sensorsData['extra'],['code'=>$result['code'],'desc'=>$result['desc']]);
                    }
                }
                $payload = $response->getContent();
                $payload = $this->payload($payload);
                $response->content($this->encodeData($payload));
                return $response;
            }else{
                return $response;
            }
        } catch (Exception $e) {
            Log::error(sprintf('middleware::handle ex=%d:%s file=%s:%d', $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return $response;
        }
    }

    /**
     * @param $content
     * @return array|mixed
     */
    private function payload($content)
    {
        if (empty($content)) {
            return [];
        }
        return json_decode($content);
    }

    /**
     * @param $payloadObject
     * @param $Aes
     * @return false|string
     */
    private function encodeData($payloadObject)
    {
        if (isset($payloadObject->sensorsData)) {
            unset($payloadObject->sensorsData);
        }
        return json_encode($payloadObject);
    }
}

