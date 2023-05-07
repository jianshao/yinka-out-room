<?php


namespace app\domain\activity\recall;


use app\core\mysql\Sharding;
use app\domain\asset\rewardcontent\ContentRegister;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\pay\dao\UserChargeStaticsModelDao;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class RecallService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RecallService();
        }
        return self::$instance;
    }

    private function getRewards($conf, $diffDays, $rmb) {
        $daysConf = ArrayUtil::safeGet($conf, 'days', []);
        foreach ($daysConf as $dayConf) {
            $dayRange = ArrayUtil::safeGet($dayConf, 'dayRange');
            if ($dayRange != null
                && ($diffDays >= $dayRange[0]
                    && ($dayRange[1] < 0 || $diffDays < $dayRange[1]))) {
                $items = ArrayUtil::safeGet($dayConf, 'items', []);
                foreach ($items as $item) {
                    $rmbRange = ArrayUtil::safeGet($item, 'rmbRange');
                    if ($rmb >= $rmbRange[0]
                        && ($rmb[1] < 0 || $rmb < $rmb[1])) {
                        return ContentRegister::getInstance()->decodeList(ArrayUtil::safeGet($item, 'rewards', []));
                    }
                }
            }
        }
        return [];
    }

    private function sendReward($user, $rewards, $timestamp) {
        $userAssets = $user->getAssets();
        $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'recall');

        foreach ($rewards as $reward) {
            $userAssets->add($reward->content->assetId, $reward->content->count, $timestamp, $biEvent);
        }
    }

    private function recallRewardImpl($userId, $conf) {
        $rmb = 0;
        $statics = UserChargeStaticsModelDao::getInstance()->loadUserChargeStatics($userId);
        if (!empty($statics)) {
            $rmb = $statics->chargeAmount;
        }

        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $conf, $rmb) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $diffDays = TimeUtil::calcDiffDays($user->getUserModel()->loginTime, time());

                $rewards = $this->getRewards($conf, $diffDays, $rmb);

                $this->sendReward($user, $rewards, time());

                return $rewards;
            });

        } catch (Exception $e) {
            Log::error(sprintf('RecallReward userId=%d, ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    public function recallReward($userId) {
        $conf = Config::loadRecallConf();

        if ($conf == null) {
            return [];
        }

        $timestamp = time();

        // 判断是否在活动期间
        $startTimeStr = ArrayUtil::safeGet($conf, 'startTime');
        $stopTimeStr = ArrayUtil::safeGet($conf, 'stopTime');

        if (!empty($startTimeStr)) {
            $startTime = TimeUtil::strToTime($startTimeStr);
            if ($timestamp < $startTime) {
                return [];
            }
        }
        if (!empty($stopTimeStr)) {
            $stopTime = TimeUtil::strToTime($stopTimeStr);
            if ($timestamp >= $stopTime) {
                return [];
            }
        }

        $rewards = $this->recallRewardImpl($userId, $conf);

        $logInfo = [];
        foreach ($rewards as $reward) {
            $logInfo[] = $reward->content->name;
        }

        Log::info(sprintf('RecallReward ok userId=%d rewards=%s', $userId, json_encode($logInfo)));

        return $rewards;
    }
}