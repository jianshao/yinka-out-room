<?php


namespace app\domain\activity\common\service;


use app\domain\user\service\OnlineTestService;

class ActivityService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ActivityService();
        }
        return self::$instance;
    }


    /**
     * 正在进行的活动
     * @param int $giftId
     * @param int $sendUid
     * @param array $toUidArr
     * @param int $num
     */
    public function underwayActivity(int $giftId, int $sendUid, array $toUidArr, int $num) {

    }

    /**
     * 检测用户是否可以参与活动
     * Notes:
     * User: echo
     * Date: 2021/11/2
     * Time: 11:44 上午
     * @param $userId
     * @return bool
     */
    public function checkUserEnable($userId): bool
    {
        $testerList = OnlineTestService::getInstance()->getOnlineTestUser();
        if (in_array($userId, $testerList)) {
            return true;
        }
        return false;
    }
}