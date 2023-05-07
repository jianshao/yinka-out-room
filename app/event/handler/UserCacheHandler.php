<?php


namespace app\event\handler;
use app\domain\queue\producer\NotifyMessage;
use app\domain\user\service\UserInfoService;
use Jobby\Exception;
use think\facade\Log;


class UserCacheHandler
{
    // 用户登录
    public function onUserLoginEvent($event) {
        try {
            Log::info(sprintf('UserCacheHandler::onUserLoginEvent userId=%d',
                $event->userId));
            // v1踢v2的 发给python做校验
            $msg = ['userId' => (int) $event->userId];
            $socket_url = config('config.socket_url_base').'iapi/checkUserLogin';
            NotifyMessage::getInstance()->notify(['url' => $socket_url, 'data' => json_encode($msg), 'method' => 'POST', 'type' => 'json']);
        } catch (Exception $e) {
            Log::error(sprintf('UserCacheHandler::onUserLoginEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function onUserBindMobileEvent($event) {
        try {
            Log::info(sprintf('UserCacheHandler::onUserBindMobileEvent userId=%d',
                $event->userId));
            // TODO cache user info
        } catch (Exception $e) {
            Log::error(sprintf('UserCacheHandler::onUserBindMobileEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function onVisitUserInfoEvent($event) {
        try {
            UserInfoService::getInstance()->visitRecord($event->userId, $event->visitUserId, $event->isVisit);
        } catch (\Exception $e) {
            Log::error(sprintf('VisitUserInfoHandler::onVisitUserInfoEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}