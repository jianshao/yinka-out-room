<?php


namespace app\domain\activity\christmas;

use app\core\mysql\Sharding;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class ChristmasService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChristmasService();
        }
        return self::$instance;
    }

    public function doExchange($userId, $propId, $timestamp){
        $lingdang = ChristmasUserDao::getInstance()->getLindDang($userId);

        $config = Config::loadConf();
        $exchangeConf = ArrayUtil::safeGet($config, 'exchangeConf', []);
        $needLindDang = ArrayUtil::safeGet($exchangeConf, $propId, 0);
        if ($needLindDang == 0){
            throw new FQException("参数错误",500);
        }

        if ($lingdang < $needLindDang){
            throw new FQException("铃铛数量不足~",500);
        }

        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $needLindDang, $propId, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);

                $lingdang = ChristmasUserDao::getInstance()->incrLindDang($userId, -$needLindDang);

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'christmas_lingdang', $needLindDang);
                $user->getAssets()->add(AssetUtils::makePropAssetId($propId), 1, $timestamp, $biEvent);

                Log::info(sprintf('ChristmasService.doExchange ok userId=%d propId=%d needLindDang=%d',
                    $userId, $propId, $needLindDang));
                return $lingdang;
            });
        } catch (FQException $e) {
            $lingdang = ChristmasUserDao::getInstance()->incrLindDang($userId, $needLindDang);
        }

        return $lingdang;
    }

    public function onSendGiftEvent($event){
        if ($this->isExpire()){
            return;
        }

        $config = Config::loadConf();
        try {
            $giftIds = ArrayUtil::safeGet($config, 'giftIds', []);
            if (in_array($event->giftKind->kindId, $giftIds)){
                $priceCount = $event->giftKind->price ? $event->giftKind->price->count:0;
                $lingdangCount = $priceCount * $event->count;

                # 加铃铛
                ChristmasUserDao::getInstance()->incrLindDang($event->fromUserId, $lingdangCount);
            }
        }catch (Exception $e) {
            Log::error(sprintf('onSendGiftEvent.incrLindDang Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
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