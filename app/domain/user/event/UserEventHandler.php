<?php


namespace app\domain\user\event;


use app\domain\asset\AssetKindIds;
use app\domain\elastic\service\ElasticService;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserService;
use app\domain\user\UserRepository;
use app\event\UserLoginEvent;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use app\web\service\OpenInstallService;
use Exception;
use think\facade\Log;

class UserEventHandler
{
    public function onReceiveGiftDomainEvent($event) {
        try {
            $totalDiamond = $event->calcReceiverAssetCount(AssetKindIds::$DIAMOND);
            if ($totalDiamond > 0) {
                $todayEarnings = $event->user->getTodayEarnings($event->timestamp);
                $todayEarnings->addEarnings($totalDiamond, $event->timestamp);
            }
        } catch (Exception $e) {
            Log::error(sprintf('UserEventHandler::onReceiveGiftDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 用户登录 如果是注册用户，并且为qrcode用户 创建派单系统消息队列
     * @param UserLoginEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $result = OpenInstallService::getInstance()->onUserLoginEvent($event);
            Log::info(sprintf('UserEventHandler::onUserLoginEvent userId=%d result=%d',
                $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('UserEventHandler::onUserLoginEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }

        // 更新用户所属城市
        try {
            if ($event->clientInfo) {
                $ip = $event->clientInfo->clientIp;
                $url = sprintf("https://api.ipdatacloud.com/v2/query?ip=%s&key=%s",$ip,config("config.ip_cloud_key"));
                $response = trim(file_get_contents($url));
                Log::info(sprintf('UserEventHandler::getIp ip=%s result=%s', $ip, $response));
                $res = json_decode($response,true);
                $city = $res['data']['location']['city']?? '';
                if ($city) {
                    UserModelDao::getInstance()->updateDatas($event->userId, ['city' => $city]);
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('UserEventHandler::getIp userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }

    }

    /**
     * @desc vip过期动态头像变更
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            $userId = $event->userId;
            $user = UserRepository::getInstance()->loadUser($userId);
            //更改用户头像
            if (CommonUtil::checkImgIsGif(CommonUtil::buildImageUrl($user->getUserModel()->avatar))) {
                $updateAvatar = $user->getUserModel()->sex == 1 ? 'Public/Uploads/image/male.png' : 'Public/Uploads/image/female.png';
                $user->updateAvatar($updateAvatar);

                $userModel = UserModelDao::getInstance()->loadUserModel($userId);
                UserService::getInstance()->upYunxinUserInfo($userModel);
                RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
                ElasticService::getInstance()->syncUserModel($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('UserEventHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

}