<?php


namespace app\domain\activity\king;

use app\domain\exceptions\FQException;
use app\domain\user\dao\UserModelDao;
use app\utils\CommonUtil;
use app\utils\TimeUtil;


class KingService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KingService();
        }
        return self::$instance;
    }

    /**
     * @deprecated
     */
    public function getReward($userId, $timestamp){
        $kingUser = KingUserDao::getInstance()->loadUser($userId);
        if ($kingUser->welfare2Status == 2){
            throw new FQException("您已领取过奖励",500);
        }

        $config = Config::loadConf();
//        Db::startTrans();
//        try {
//            $user = UserRepository::getInstance()->loadUser($userId);
//
//            if (empty($user) || $user->getUserModel()->dukeLevel != 5){
//                throw new FQException("您还不是国王爵位哦～");
//            }
//            $kingUser->welfare2Status = 2;
//            KingUserDao::getInstance()->saveUser($kingUser);
//
//            $reward = $config['welfare2_reward'];
//            $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'king', 1);
//            $assetId = AssetUtils::makeGiftAssetId($reward['giftId']);
//            $user->getAssets()->add($assetId, $reward['count'], $timestamp, $biEvent);
//
//            Log::info(sprintf('KingService.getReward ok userId=%d assetI=%s count=%d',
//                $userId, $reward['giftId'], $reward['count']));
//            Db::commit();
//
//            return $reward;
//        } catch (FQException $e) {
//            Db::rollback();
//            throw $e;
//        }
    }

    /**
     * @deprecated
     */
    public function postAddress($userId, $name, $mobile, $region, $address, $timestamp){
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel->dukeLevel < 5){
            throw new FQException("您还不是国王爵位哦～");
        }

        CommonUtil::validateMobile($mobile);

        $config = Config::loadConf();
//        Db::startTrans();
//        try {
//            $model = DeliveryAddressDao::getInstance()->loadModel($userId, 'king');
//            if (!empty($model)){
//                throw new FQException("您已领取过奖励",500);
//            }
//
//            $reward = $config['welfare1_reward'];
//            $model = new DeliveryAddressModel();
//            $model->userId = $userId;
//            $model->name = $name;
//            $model->reward = $reward['giftId'];
//            $model->count = $reward['count'];
//            $model->createTime = $timestamp;
//            $model->activityType = 'king';
//            $model->region = $region;
//            $model->mobile = $mobile;
//            $model->address = $address;
//            DeliveryAddressDao::getInstance()->addData($model);
//
//            $kingUser = KingUserDao::getInstance()->loadUser($userId);
//            $kingUser->welfare1Status = 2;
//            KingUserDao::getInstance()->saveUser($kingUser);
//
//            Log::info(sprintf('KingService.postAddress ok userId=%d assetI=%s count=%d',
//                $userId, $reward['giftId'], $reward['count']));
//            Db::commit();
//
//            return $reward;
//        } catch (FQException $e) {
//            Db::rollback();
//            throw $e;
//        }
    }

    public function isExpire(){
        $config = Config::loadConf();
        $timestamp = time();
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

}