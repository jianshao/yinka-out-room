<?php


namespace app\domain\redpacket;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\pay\ChargeService;
use app\domain\user\UserRepository;
use app\event\RedPacketGrabEvent;
use app\event\SendRedPacketEvent;
use Exception;
use think\facade\Log;

class RedPacketService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketService();
        }
        return self::$instance;
    }

    public function buildDetailKey($redPacketId) {
        return 'hongbao_' . $redPacketId;
    }

    public function buildGrabUsersKey($redPacketId) {
        return 'hongbao:grabusers:' . $redPacketId;
    }

    private function sendRewardGrabDetail($userId, $redPacketModel, $detail) {
        $timestamp = time();
        $biEvent = BIReport::getInstance()->makeGrabRedPacketsBIEvent($userId, $redPacketModel->roomId, $redPacketModel->type, $redPacketModel->id);
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $detail, $biEvent, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                $bean = $user->getAssets()->getBean($timestamp);
                $bean->add($detail->beanCount, $timestamp, $biEvent);
            });
        } catch (Exception $e) {
            throw $e;
        }

        Log::info(sprintf('RedPacketService::sendRewardGrabDetail userId=%d redPacketId=%d detailId=%d beanCount=%d',
            $userId, $redPacketModel->id, $detail->id, $detail->beanCount));
    }

    private function returnDetail($redPacketModel, $detail) {
        $timestamp = time();
        $biEvent = BIReport::getInstance()->makeReturnRedPacketsBIEvent($redPacketModel->sendUserId, $redPacketModel->roomId, $redPacketModel->type, $redPacketModel->id);
        try {
            $userId = $redPacketModel->sendUserId;
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $detail, $biEvent, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                $bean = $user->getAssets()->getBean($timestamp);
                $bean->add($detail->beanCount, $timestamp, $biEvent);
            });

        } catch (Exception $e) {
            throw $e;
        }

        Log::info(sprintf('RedPacketService::returnDetail userId=%d redPacketId=%d detailId=%d beanCount=%d',
            $redPacketModel->sendUserId, $redPacketModel->id, $detail->id, $detail->beanCount));
    }

    public function returnRedPacket($redPacketId) {
        // 设置红包状态
        $redPacketModel = RedPacketModelDao::getInstance()->findById($redPacketId);
        if ($redPacketModel == null) {
            Log::warning(sprintf('RedPacketService::returnRedPacket NotFound redPacketId=%d', $redPacketId));
            return;
        }

        $detailKey = $this->buildDetailKey($redPacketId);
        $redis = RedisCommon::getInstance()->getRedis();
        $detailId = $redis->lPop($detailKey);
        $returnTotalBean = 0;
        while (!empty($detailId)) {
            $detail = RedPacketDetailModelDao::getInstance()->findById($redPacketId);
            if ($detail->isGet == 0 && $detail->getUserId <= 0) {
                if (RedPacketDetailModelDao::getInstance()->updateModelForGet($detail)) {
                    $this->returnDetail($redPacketModel, $detail);
                    $returnTotalBean += $detail->beanCount;
                }
            }
            $detailId = $redis->lPop($detailKey);
        }

        Log::info(sprintf('RedPacketService::returnRedPacket redPacketId=%d userId=%d returnTotal=%d',
            $redPacketId, $redPacketModel->sendUserId, $returnTotalBean));
    }

    private function finishRedPacket($redPacketId) {
        RedPacketModelDao::getInstance()->updateDatas($redPacketId, ['status' => 2]);
    }

    public function grabRedPacket($userId, $redPacketId) {
        $retPacketModel = RedPacketModelDao::getInstance()->findById($redPacketId);

        if ($retPacketModel == null) {
            throw new FQException('红包参数错误', 500);
        }

        $detailKey = $this->buildDetailKey($redPacketId);

        $redis = RedisCommon::getInstance()->getRedis();
        $detailId = $redis->lPop($detailKey);

        if (empty($detailId)) {
            $this->finishRedPacket($redPacketId);
            throw new FQException('已经抢光了', 500);
        }

        try {
            $grabKey = $this->buildGrabUsersKey($redPacketId);

            $added = $redis->sAdd($grabKey, $userId);
            Log::debug(sprintf('RedPacketService::grabRedPacket userId=%d redPacketId=%d added=%d',
                $userId, $redPacketId, $added));
            if ($added === false || $added == 0) {
                throw new FQException('你已经领取过了', 500);
            }

            $detail = RedPacketDetailModelDao::getInstance()->findById($detailId);

            if ($detail->getUserId > 0 || $detail->isGet != 0) {
                throw new FQException('红包数据错误', 500);
            }

            $detail->getUserId = $userId;
            if (!RedPacketDetailModelDao::getInstance()->updateModelForGet($detail)) {
                throw new FQException('红包数据错误', 500);
            }

            $this->sendRewardGrabDetail($userId, $retPacketModel, $detail);
        } catch (Exception $e) {
            $redis->rPush($detailKey, $detailId);
            throw $e;
        }

        $detailLen = $redis->lLen($detailKey);
        if (empty($detailLen)) {
            $this->finishRedPacket($redPacketId);
        }

        Log::info(sprintf('RedPacketService::grabRedPacket userId=%d redPacketId=%d detailId=%d beanCount=%d',
            $userId, $redPacketId, $detail->id, $detail->beanCount));

        event(new RedPacketGrabEvent($userId, $redPacketId, $detailId, time()));

        return $detail->beanCount;
    }

    public function makeAndBuyRedPacket($userId, $roomId, $type, $payChannel, $displayTime, $totalBean, $count, $config) {
        $productId = RedPacketSystem::getInstance()->findProductIdByBean('android', $totalBean);
        if ($productId == null) {
            throw new FQException('发红包金额错误,请重试', 500);
        }

        $seconds = RedPacketSystem::getInstance()->getSecondsByDisplay($displayTime);
        if ($seconds == null) {
            throw new FQException('发红包选项错误,请重试', 500);
        }

        list($payResult, $orderId) = ChargeService::getInstance()->androidBuyRedPacketProduct($userId, $productId, $payChannel, $config);

        $this->makeRedPacket($userId, $roomId, $orderId, $type, $totalBean, $count, $seconds);

        return [$payResult, $orderId];
    }

    public function makeAndSendRedPacket($userId, $roomId, $type, $totalBean, $count, $orderId, $dealId, $countdownTime) {
        $redPacketModel = $this->makeRedPacket($userId, $roomId, $orderId, $type, $totalBean, $count, $countdownTime);

        $this->payAndSendRedPacket($redPacketModel, $orderId, $dealId);
    }

    public function payAndSendRedPacketByOrderId($orderId, $dealId) {
        $redPacketModel = RedPacketModelDao::getInstance()->findByOrderId($orderId);
        if ($redPacketModel != null) {
            $this->payAndSendRedPacket($redPacketModel, $orderId, $dealId);
        } else {
            Log::warning(sprintf('RedPacketService::payAndSendRedPacketByOrderId NotFoundRedPacket orderId=%s dealId=%s',
                $orderId, $dealId));
        }
    }

    private function payAndSendRedPacket($redPacketModel, $orderId, $dealId) {
        // 支付豆
        $this->payRedPackets($redPacketModel, $orderId, $dealId);

        // 发红包
        $valueList = $this->getBonusNew($redPacketModel->totalBean, 1, $redPacketModel->count);

        $timestamp = time();
        $redis = RedisCommon::getInstance()->getRedis();

        foreach ($valueList as $value) {
            $detail = new RedPacketDetailModel();
            $detail->redPacketId = $redPacketModel->id;
            $detail->getUserId = 0;
            $detail->getTime = $timestamp;
            $detail->beanCount = $value;
            $detail->isGet = 0;
            $detail->createTime = $timestamp;
            $detail->updateTime = $timestamp;
            RedPacketDetailModelDao::getInstance()->createModel($detail);
            $redis->rPush('hongbao_' . $redPacketModel->id, $detail->id);
        }

        Log::info(sprintf('SendRedPacket userId=%d roomId=%d type=%d totalBean=%d count=%d orderId=%s redPacketId=%d values=%s',
            $redPacketModel->sendUserId, $redPacketModel->roomId, $redPacketModel->type, $redPacketModel->totalBean,
            $redPacketModel->count, $redPacketModel->orderId, $redPacketModel->id, json_encode($valueList)));

        // 触发事件
        event(new SendRedPacketEvent($redPacketModel->sendUserId, $redPacketModel->roomId, $redPacketModel->id, $redPacketModel->totalBean, $redPacketModel->count,$orderId));
    }

    private function getRedPacketInfoImpl($userId, $redPacketModel, $remCount) {
        $grabKey = $this->buildGrabUsersKey($redPacketModel->id);
        $redis = RedisCommon::getInstance()->getRedis();
        $isGrab = $redis->sIsMember($grabKey, $userId);

        $ret = new RedPacketInfo();
        $ret->id = $redPacketModel->id;
        $ret->sendUserId = $redPacketModel->sendUserId;
        $ret->totalBean = $redPacketModel->totalBean;
        $ret->status = $redPacketModel->status;
        $ret->count = $redPacketModel->count;
        $ret->sendTime = $redPacketModel->sendTime;
        $ret->countdownTime = $redPacketModel->countdown;
        $ret->remCount = $remCount;
        $ret->type = $redPacketModel->type;
        $ret->isGet = $isGrab;
        return $ret;
    }

    public function getRedPacketInfo($userId, $redPacketId) {
        $redPacketModel = RedPacketModelDao::getInstance()->findById($redPacketId);
        if ($redPacketModel == null || $redPacketModel->status != 1) {
            return null;
        }
        $detailKey = $this->buildDetailKey($redPacketModel->id);
        $redis = RedisCommon::getInstance()->getRedis();
        $remCount = $redis->lLen($detailKey);
        if (empty($remCount)) {
            $remCount = 0;
        }
        return $this->getRedPacketInfoImpl($userId, $redPacketModel, $remCount);
    }

    public function getRoomRedPacketCount($userId, $roomId) {
        $redPacketModels = RedPacketModelDao::getInstance()->findByRoomId($roomId);
        if (empty($redPacketModels)) {
            return [0, 0];
        }

        $canGrabData = null;
        $count = 0;
        $end = 0;
        $redis = RedisCommon::getInstance()->getRedis();
        foreach ($redPacketModels as $redPacketModel) {
            $grabKey = $this->buildGrabUsersKey($redPacketModel->id);
            $isGrab = $redis->sIsMember($grabKey, $userId);
            if (!$isGrab) {
                $count += 1;
                if ($canGrabData == null) {
                    $canGrabData = RedPacketModelDao::getInstance()->modelToData($redPacketModel);
                }
            }
        }

        if ($canGrabData != null) {
            $end = max(0, $canGrabData['send_time'] + $canGrabData['count_down'] - time());
        }

        return [$count, $end];
    }

    public function getRoomRedPacketInfo($userId, $roomId) {
        $redPacketModels = RedPacketModelDao::getInstance()->findByRoomId($roomId);
        if (empty($redPacketModels)) {
            return null;
        }

        $lastRedPacketModel = null;
        $lastRemCount = 0;

        foreach ($redPacketModels as $redPacketModel) {
            $detailKey = $this->buildDetailKey($redPacketModel->id);
            $redis = RedisCommon::getInstance()->getRedis();
            $remCount = $redis->lLen($detailKey);
            if (empty($remCount)) {
                $remCount = 0;
            }
            $lastRemCount = 0;
            $lastRedPacketModel = $redPacketModel;

            if ($remCount > 0) {
                $grabKey = $this->buildGrabUsersKey($redPacketModel->id);
                $isGrab = $redis->sIsMember($grabKey, $userId);
                if (!$isGrab) {
                    return $this->getRedPacketInfoImpl($userId, $redPacketModel, $remCount);
                }
            }
        }
        if ($lastRedPacketModel != null) {
            return $this->getRedPacketInfoImpl($userId, $lastRedPacketModel, $lastRemCount);
        }
        return null;
    }

    public function makeRedPacket($userId, $roomId, $orderId, $type, $totalBean, $count, $countdownTime) {
        $timestamp = time();
        $redPacketModel = new RedPacketModel();
        $redPacketModel->count = $count;
        $redPacketModel->totalBean = $totalBean;
        $redPacketModel->sendUserId = $userId;
        $redPacketModel->sendTime = $timestamp;
        $redPacketModel->countdown = $countdownTime;
        $redPacketModel->roomId = $roomId;
        $redPacketModel->status = 0;
        $redPacketModel->createTime = $timestamp;
        $redPacketModel->orderId = $orderId;
        $redPacketModel->type = $type;
        RedPacketModelDao::getInstance()->createModel($redPacketModel);

        Log::info(sprintf('RedPacketService::makeRedPacket userId=%d roomId=%d orderId=%d type=%d totalBean=%d count=%d countdownTime=%d redPacketId=%d',
            $userId, $roomId, $orderId, $type, $totalBean, $count, $countdownTime, $redPacketModel->id));

        return $redPacketModel;
    }

    public function getBonusNew($totalValue, $minValue, $count) {
        $ret = [];
        $remainValue = $totalValue;
        $remainCount = $count - 1;
        while ($remainCount > 0) {
            $valueLimit = $remainValue - $minValue * $remainCount;
            $maxValue = min($valueLimit, $remainValue / $remainCount * 2);
            $value = random_int($minValue, max($minValue, $maxValue));
            $remainValue -= $value;
            $remainCount -= 1;
            $ret[] = $value;
        }
        $ret[] = $remainValue;
        return $ret;
    }

    private function xRandom($bonusMin, $bonusMax){
        $sqr = intval($this->sqr($bonusMax - $bonusMin));
        $value = rand(0, ($sqr - 1));
        return intval(sqrt($value));
    }

    private function sqr($n){
        return $n * $n;
    }

    public function getBonusOld($bonusTotal, $bonusCount, $bonusMin, $bonusMax) {
        $result = [];

        $average = $bonusTotal / $bonusCount;

        for ($i = 0; $i < $bonusCount; $i++) {
            if (rand($bonusMin, $bonusMax) > $average) {
                // 在平均线上减钱
                $temp = $bonusMin + $this->xRandom($bonusMin, $average);
                $result[$i] = $temp;
                $bonusTotal -= $temp;
            } else {
                // 在平均线上加钱
                $temp = $bonusTotal - $this->xRandom($average, $bonusMax);
                $result[$i] = $temp;
                $bonusTotal -= $temp;
            }
        }

        while ($bonusTotal > 0) {
            for ($i = 0; $i < $bonusCount; $i++) {
                if ($bonusTotal > 0 && $result[$i] < $bonusMax) {
                    $result[$i]++;
                    $bonusTotal--;
                }
            }
        }

        while ($bonusTotal < 0) {
            for ($i = 0; $i < $bonusCount; $i++) {
                if ($bonusTotal < 0 && $result[$i] > $bonusMin) {
                    $result[$i]--;
                    $bonusTotal++;
                }
            }
        }
        return $result;
    }

    private function payRedPackets($redPacketModel, $orderId, $dealId) {
        try {
            $userId = $redPacketModel->sendUserId;
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $redPacketModel) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $biEvent = BIReport::getInstance()->makeSendRedPacketsBIEvent(0, $redPacketModel->roomId, $redPacketModel->type, $redPacketModel->id);
                $user->getAssets()->consume(AssetKindIds::$BEAN, $redPacketModel->totalBean, time(), $biEvent);
            });

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function() use($redPacketModel, $orderId, $dealId) {
                $redPacketModel->status = 1;
                $redPacketModel->orderId = $orderId;
                $redPacketModel->dealId = $dealId;
                $redPacketModel->sendTime = time();

                if (!RedPacketModelDao::getInstance()->updateModel($redPacketModel, 0)) {
                    throw new FQException('红包状态错误', 500);
                }
            });

        } catch (Exception $e) {
            throw $e;
        }
    }
}