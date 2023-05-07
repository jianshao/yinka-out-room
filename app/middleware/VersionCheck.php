<?php
namespace app\middleware;

use app\domain\Config;
use think\facade\Log;
use Exception;

/**
 * 版本提审中 数据处理
 * Class VersionCheck
 * @package app\middleware
 */
class VersionCheck
{

    public function handle($request, \Closure $next)
    {
        $versionCheckStatus = $this->getVersionInfo($request);
        $request->versionCheckStatus = $versionCheckStatus;
//        $request->versionCheckStatus = 1;
        return $next($request);
    }

    private function getVersionInfo($request)
    {
        try {
            $version = $request->header('VERSION');
            $channel = $request->header('CHANNEL');
            $source = $request->header('source');
            $channelVersionList = config::getInstance()->getChannelVersionConf();
            $status = 0;
            if(!empty($channelVersionList)){
                foreach($channelVersionList as $channelInfo){
                    if($channelInfo['app_version'] == $version && $channelInfo['channel_name'] == $channel && $channelInfo['app_type'] == $source){
                        $status = $channelInfo['status'] == 1 ? 0 : 1;
                        break;
                    }
                }
            }
            return $status;
        } catch (Exception $e) {
            Log::error(sprintf('VersionCheck middleware::handle ex=%d:%s file=%s:%d', $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return 0;
        }
    }

}
