<?php


namespace app\domain\activity\duobao3;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\UserRepository;
use app\event\ThreeLootNoticeEvent;
use think\facade\Log;
use Exception;

class Duobao3Service
{
    protected $DUOBAO_INFO_SCRIPT = "
        local function makeOrder(infoKey, orderKey, issueNum, giftId, giftCoin, giftName, giftImage, price, seatCount, status, curTime, lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex)
            redis.call('hmset', infoKey, 'issueNum', issueNum, 'createTime', curTime, 'lastWinnerIssueNum', lastWinnerIssueNum, 'lastWinnerUserId', lastWinnerUserId, 'lastWinnerIndex', lastWinnerIndex)
            local seatInfos = '[]'
            redis.call('hmset', orderKey, 'giftId', giftId, 'giftCoin', giftCoin, 'giftName', giftName, 'giftImage', giftImage, 'price', price, 'seatCount', seatCount, 'status', status, 'createTime', curTime, 'seatInfos', seatInfos, 'winnerIndex', 0)
        end

        local duobaoType = tostring(KEYS[1])
        local initIssueNum = tonumber(KEYS[2])
        local giftId1 = tonumber(KEYS[3])
        local giftCoin1 = tonumber(KEYS[4])
        local giftName1 = tostring(KEYS[5])
        local giftImage1 = tostring(KEYS[6])
        local price1 = tonumber(KEYS[7])
        local giftId2 = tonumber(KEYS[8])
        local giftCoin2 = tonumber(KEYS[9])
        local giftName2 = tostring(KEYS[10])
        local giftImage2 = tostring(KEYS[11])
        local price2 = tonumber(KEYS[12])
        local seatCount = tonumber(KEYS[13])
        local expiresSeconds = tonumber(KEYS[14])
        local curTime = tonumber(KEYS[15])

        local infoKey = 'duobaoinfo:' .. duobaoType
        local infoExists = redis.call('exists', infoKey)
        if infoExists == 0 then
            local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. initIssueNum
            makeOrder(infoKey, orderKey, initIssueNum, giftId1, giftCoin1, giftName1, giftImage1, price1, seatCount, 0, curTime, 0, 0, 0)
            return {0, {initIssueNum, giftId1, giftCoin1, giftName1, giftImage1, price1, seatCount, 0, curTime, '[]', 0}, {0, 0, 0}}
        end

        local info = redis.call('hmget', infoKey, 'issueNum', 'lastWinnerIssueNum', 'lastWinnerUserId', 'lastWinnerIndex')
        local issueNum = tonumber(info[1])
        local lastWinnerIssueNum = tonumber(info[2])
        local lastWinnerUserId = tonumber(info[3])
        local lastWinnerIndex = tonumber(info[4])
        local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. issueNum
        local orderExists = redis.call('exists', orderKey)
        if orderExists == 0 then
            -- 订单不存在
            issueNum = issueNum + 1
            local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. issueNum
            makeOrder(infoKey, orderKey, issueNum, giftId1, giftCoin1, giftName1, giftImage1, price1, seatCount, 0, curTime, lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex)
            return {1, {issueNum, giftId1, giftCoin1, giftName1, giftImage1, price1, seatCount, 0, curTime, '[]', 0}, {lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex}}
        end

        local orderInfo = redis.call('hmget', orderKey, 'giftId', 'giftCoin', 'giftName', 'giftImage', 'price', 'seatCount', 'status', 'createTime', 'seatInfos', 'winnerIndex')
        local giftId = tonumber(orderInfo[1])
        local giftCoin = tonumber(orderInfo[2])
        local giftName = tostring(orderInfo[3])
        local giftImage = tostring(orderInfo[4])
        local price = tonumber(orderInfo[5])
        local seatCount = tonumber(orderInfo[6])
        local status = tonumber(orderInfo[7])
        local createTime = tonumber(orderInfo[8])
        local seatInfos = cjson.decode(orderInfo[9])
        local winnerIndex = tonumber(orderInfo[10])
        -- 看是否抢完了
        local grabCount = table.getn(seatInfos)
        if grabCount >= seatCount then
            -- 生成新的订单
            issueNum = issueNum + 1
            local newGiftId = giftId1
            local newGiftCoin = giftCoin1
            local newGiftName = giftName1
            local newGiftImage = giftImage1
            local newPrice = price1
            if newGiftId == giftId then
                newGiftId = giftId2
                newGiftCoin = giftCoin2
                newGiftName = giftName2
                newGiftImage = giftImage2
                newPrice = price2
            end
            local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. issueNum
            makeOrder(infoKey, orderKey, issueNum, newGiftId, newGiftCoin, newGiftName, newGiftImage, newPrice, seatCount, 0, curTime, lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex)
            return {2, {issueNum, newGiftId, newGiftCoin, newGiftName, newGiftImage, newPrice, seatCount, 0, curTime, '[]', 0}, {lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex}}
        end

        if createTime + expiresSeconds <= curTime then
            -- 过期了，设置状态，并加入过期队列，等待退款
            if status == 0 then
                redis.call('hset', orderKey, 'status', 2)
                local expKey = 'duobaoexp:' .. duobaoType
                redis.call('rpush', expKey, issueNum)
            end
            -- 生成新的订单
            issueNum = issueNum + 1
            local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. issueNum
            local newGiftId = giftId1
            local newGiftCoin = giftCoin1
            local newGiftName = giftName1
            local newGiftImage = giftImage1
            local newPrice = price1
            if newGiftId == giftId then
                newGiftId = giftId2
                newGiftCoin = giftCoin2
                newGiftName = giftName2
                newGiftImage = giftImage2
                newPrice = price2
            end
            makeOrder(infoKey, orderKey, issueNum, newGiftId, newGiftCoin, newGiftName, newGiftImage, newPrice, seatCount, 0, curTime, lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex)
            return {3, {issueNum, newGiftId, newGiftCoin, newGiftName, newGiftImage, newPrice, seatCount, 0, curTime, '[]', 0}, {lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex}}
        end

        return {4, {issueNum, giftId, giftCoin, giftName, giftImage, price, seatCount, status, createTime, orderInfo[9], winnerIndex}, {lastWinnerIssueNum, lastWinnerUserId, lastWinnerIndex}}
        ";

    protected $DUOBAO_GRAB_SCRIPT = "
        local duobaoType = tostring(KEYS[1])
        local userId = tonumber(KEYS[2])
        local issueNum = tonumber(KEYS[3])
        local expiresSeconds = tonumber(KEYS[4])
        local perUserMaxGrabCount = tonumber(KEYS[5])
        local curTime = tonumber(KEYS[6])
        local winnerIndex = tonumber(KEYS[7])
        local orderKey = 'duobaoorder:' .. duobaoType .. ':' .. issueNum
        local orderExists = redis.call('exists', orderKey)
        if orderExists == 0 then
            -- 订单不存在
            return {-1, 0, 0, ''}
        end

        local orderInfo = redis.call('hmget', orderKey, 'giftId', 'price', 'seatCount', 'status', 'createTime', 'grabCount', 'seatInfos')
        local seatCount = tonumber(orderInfo[3])
        local status = tonumber(orderInfo[4])

        if winnerIndex < 0 then
            -- 赢家索引错误
            return {-2, status, 0, ''}
        end
        if winnerIndex >= seatCount then
            -- 赢家索引错误
            return {-3, status, 0, ''}
        end

        local createTime = tonumber(orderInfo[5])
        local seatInfos = cjson.decode(orderInfo[7])
        if createTime + expiresSeconds <= curTime then
            -- 订单已过期
            return {-4, status, 0, ''}
        end

        -- 获取所有押注玩家, 检查是否押注已满
        local seatCount = tonumber(orderInfo[3])
        local totalGrab = table.getn(seatInfos)
        if totalGrab >= seatCount then
            -- 手慢无
            return {-5, status, 0, ''}
        end
        if totalGrab >= perUserMaxGrabCount then
            -- 检查用户ID抢的次数
            local userGrabCount = 0
            for i=1, totalGrab do
                local seatInfo = seatInfos[i]
                if seatInfo['userId'] == userId then
                    userGrabCount = userGrabCount + 1
                end
            end
            if userGrabCount >= perUserMaxGrabCount then
                -- 大于每个人的最大次数
                return {-6, status, 0, ''}
            end
        end
        -- 抢一个座位
        local seatInfo = {}
        seatInfo['userId'] = userId
        seatInfo['grabTime'] = curTime
        table.insert(seatInfos, seatInfo)
        local seatInfosStr = cjson.encode(seatInfos)
        if totalGrab + 1 >= seatCount then
            -- 设置为抢完状态
            redis.call('hmset', orderKey, 'status', 1, 'winnerIndex', winnerIndex, 'seatInfos', seatInfosStr)
            -- 设置最后赢的期号及玩家信息
            local winnerSeatInfo = seatInfos[winnerIndex + 1]
            local winnerUserId = winnerSeatInfo['userId']
            local infoKey = 'duobaoinfo:' .. duobaoType
            redis.call('hmset', infoKey, 'lastWinnerIssueNum', issueNum, 'lastWinnerUserId', winnerUserId, 'lastWinnerIndex', winnerIndex)
            return {1, 1, winnerIndex, seatInfosStr}
        else
            redis.call('hset', orderKey, 'seatInfos', seatInfosStr)
        end
        return {0, status, 0, ''}";

    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Duobao3Service();
        }
        return self::$instance;
    }

    private function payOrder($userId, $tableConfig, $order, $curTime) {
        $beanModel = BeanModelDao::getInstance()->loadBean($userId);
        if ($order->price > $beanModel->balance()) {
            Log::info(sprintf('GrabSeat BeanNotEnough userId=%d tableId=%d issueNum=%d giftId=%d',
                $userId, $tableConfig->tableId, $order->issueNum, $order->giftId));
            throw new FQException('抢占失败，当前番茄豆不足，请充值后参与。', 211);
        }

        $redis = RedisCommon::getInstance()->getRedis();
        // 抢座位赢家
        $winnerIndex = rand(0, 2);

        try {
            list($code, $status, $winnerIndex, $seatInfosStr) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $tableConfig, $order, $curTime, $redis, $winnerIndex) {
                $user = UserRepository::getInstance()->loadUser($userId);
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'duobao3',  $tableConfig->tableId,1, $order->issueNum, $order->giftId);
                try {
                    $user->getAssets()->consume(AssetKindIds::$BEAN,$order->price, $curTime, $biEvent);
                } catch (FQException $e) {
                    Log::info(sprintf('GrabSeat BeanNotEnough1 userId=%d tableId=%d issueNum=%d giftId=%d',
                        $userId, $tableConfig->tableId, $order->issueNum, $order->giftId));
                    throw new FQException('抢占失败，当前番茄豆不足，请充值后参与。', 211);
                }

                $duobaoExpiresSeconds = Config::getInstance()->getDuobaoExpiresSeconds();
                $perUserMaxGrabCount = Config::getInstance()->getPerUserMaxGrabCount();

                list($code, $status, $winnerIndex, $seatInfosStr) = $redis->eval($this->DUOBAO_GRAB_SCRIPT,
                    [$tableConfig->tableName, $userId, $order->issueNum, $duobaoExpiresSeconds, $perUserMaxGrabCount, $curTime, $winnerIndex],
                    7);

                Log::info(sprintf('GrabSeatGrab userId=%d tableId=%d issueNum=%d giftId=%d, price=%d code=%d status=%d winnerIndex=%d seatInfos=%s',
                    $userId, $tableConfig->tableId, $order->issueNum, $order->giftId, $order->price, $code, $status, $winnerIndex, $seatInfosStr));

                if ($code < 0) { //当前奖池已经结束
                    if ($code == -6) {
                        throw new FQException('最多可买两个座位哦～', 500);
                    }
                    throw new FQException('手速太慢了~本轮已结束', 500);
                }
                return [$code, $status, $winnerIndex, $seatInfosStr];
            });
        } catch (\Exception $e) {
            throw $e;
        }

        $order->status = $status;
        $order->winnerIndex = $winnerIndex;
        $order->seatInfos = OrderDao::getInstance()->decodeSeatInfos($seatInfosStr);
        return [$code, $order];
    }

    private function sendReward($userId, $tableConfig, $order, $curTime) {
        if ($order->winnerIndex >= 0 && $order->winnerIndex < count($order->seatInfos)) {
            $winnerSeatInfo = $order->seatInfos[$order->winnerIndex];
            try {
                Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($winnerSeatInfo, $curTime, $tableConfig, $order) {
                    $user = UserRepository::getInstance()->loadUser($winnerSeatInfo->userId);
                    $giftBag = $user->getAssets()->getGiftBag($curTime);
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent(0,'duobao3', $tableConfig->tableId, 1, $order->issueNum);
                    $giftBag->add($order->giftId, 1, $curTime, $biEvent);
                });
            } catch (\Exception $e) {
                throw $e;
            }
            Log::info(sprintf('Duobao3Service::sendReward userId=%d tableId=%d issueNum=%d giftId=%d',
                $userId, $tableConfig->tableId, $order->issueNum, $order->giftId));
        }
        event(new ThreeLootNoticeEvent($order, $tableConfig->tableId, $curTime));
    }

    /**
     * 抢座位
     *
     * @param $userId
     * @param $tableId
     * @param $issueNum
     */
    public function grabSeat($userId, $tableId, $issueNum) {
        $tableConfig = Config::getInstance()->getTableConfig($tableId);
        if ($tableConfig == null) {
            throw new FQException('奖池未开放', 500);
        }

        // 获取订单信息
        $order = OrderDao::getInstance()->loadOrder($tableConfig->tableName, $issueNum);
        if (empty($order)) {
            Log::info(sprintf('GrabSeat NoOrder userId=%d tableId=%d issueNum=%d',
                $userId, $tableId, $issueNum));
            throw new FQException('手速太慢了~本轮已结束', 500);
        }

        // 支付订单
        $curTime = time();

        list($code, $order) = $this->payOrder($userId, $tableConfig, $order, $curTime);

        // 支付抢座位

        Log::info(sprintf('GrabSeatPaid userId=%d tableId=%d issueNum=%d giftId=%d price=%d code=%d',
            $userId, $tableId, $issueNum, $order->giftId, $order->price, $code));

        // 触发夺宝事件
        if ($code == 1) {
            $this->sendReward($userId, $tableConfig, $order, $curTime);
        }
    }

    /**
     * 获取某个桌子的夺宝信息
     *
     * @param $tableId
     */
    public function getTableInfo($tableId) {
        $tableConfig = Config::getInstance()->getTableConfig($tableId);

        if ($tableConfig == null) {
            throw new FQException('奖池未开放', 500);
        }

        $expiresSeconds = Config::getInstance()->getDuobaoExpiresSeconds();
        $tableConfig = Config::getInstance()->getTableConfig($tableId);

        $curTime = time();
        $initIssueNum = 1;
        $maxSeatCount = 3;

        // 保证最少2个
        if (count($tableConfig->tableGifts) < 2) {
            $tableGift1 = $tableConfig->tableGifts[0];
            $tableGift2 = $tableConfig->tableGifts[0];
        } else {
            $poolIndexex = array_rand($tableConfig->tableGifts, 2);
            $tableGift1 = $tableConfig->tableGifts[$poolIndexex[0]];
            $tableGift2 = $tableConfig->tableGifts[$poolIndexex[1]];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $params = [$tableConfig->tableName, $initIssueNum,
            $tableGift1->giftId, $tableGift1->giftCoin, $tableGift1->giftName, $tableGift1->giftImage, $tableGift1->price,
            $tableGift2->giftId, $tableGift2->giftCoin, $tableGift2->giftName, $tableGift2->giftImage, $tableGift2->price,
//            $giftInfo2['gift_id'], $giftInfo2['gift_coin'], $giftInfo2['gift_name'], $giftInfo2['gift_image'], $giftInfo2['pay_price'],
            $maxSeatCount, $expiresSeconds, $curTime];
        list($code, $orderData, $infoData) = $redis->eval($this->DUOBAO_INFO_SCRIPT, $params,15);

        Log::info(sprintf('getDuobaoInfo duobaoType=%s params=%s code=%d orderData=%s infoData=%s',
            $tableConfig->tableName, json_encode($params), $code, json_encode($orderData), json_encode($infoData)));
        list($issueNum, $giftId, $giftCoin, $giftName, $giftImage, $price, $seatCount,
            $status, $createTime, $seatInfos, $winnerIndex) = $orderData;
        list($lastWinnerIssueNum, $lastWinnerUserId, $lastWinnerIndex) = $infoData;

        $order = new Order();
        $order->issueNum = intval($issueNum);
        $order->giftId = intval($giftId);
        $order->giftCoin = intval($giftCoin);
        $order->giftName = $giftName;
        $order->giftImage = $giftImage;
        $order->price = $price;
        $order->seatCount = $seatCount;
        $order->status = $status;
        $order->createTime = $createTime;
        $order->seatInfos = OrderDao::getInstance()->decodeSeatInfos($seatInfos);
        $order->winnerIndex = $winnerIndex;

        return new TableInfo($order, $lastWinnerIssueNum, $lastWinnerUserId, $lastWinnerIndex);
    }

    /**
     * 获取订单
     *
     * @param $tableId
     * @param $issueNum
     */
    public function getOrder($tableId, $issueNum) {
        $tableConfig = Config::getInstance()->getTableConfig($tableId);

        if ($tableConfig == null) {
            throw new FQException('奖池未开放', 500);
        }

        $order = OrderDao::getInstance()->loadOrder($tableConfig->tableName, $issueNum);

        if ($order->status == 0) {
            if ($order->createTime + Config::getInstance()->getDuobaoExpiresSeconds() <= time()) {
                Log::debug(sprintf('Duobao3Service::getOrder expires tableId=%d issueNum=%d createTime=%d',
                    $tableId, $issueNum, $order->createTime));
                $order->status = 2;
            }
        }

        return $order;
    }

    public function encodeOrderForLog($order) {
        return [
            'issueNum' => $order->issueNum,
            'giftId' => $order->giftId,
            'price' => $order->price,
            'seatCount' => $order->seatCount,
            'status' => $order->status,
            'createTime' => $order->createTime,
            'seatInfos' => json_encode($order->seatInfos),
            'winnerIndex' => $order->winnerIndex,
        ];
    }

    private function _tuikuan($tableConfig, $order, $curTime)
    {
        $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'duobao3',  $tableConfig->tableId, 1, $order->issueNum);
        foreach ($order->seatInfos as $seatInfo) {
            try {
                Sharding::getInstance()->getConnectModel('userMaster', $seatInfo->userId)->transaction(function () use($seatInfo, $curTime, $order, $biEvent) {
                    $user = UserRepository::getInstance()->loadUser($seatInfo->userId);
                    $bean = $user->getAssets()->getBean($curTime);
                    $bean->add($order->price, $curTime, $biEvent);
                });
            } catch (Exception $e) {
                Log::error(sprintf('DuobaoTuikuan Failed userId=%d tableId=%d issueNum=%d price=%d trace=%s',
                    $seatInfo->userId, $tableConfig->tableId, $order->issueNum, $order->price, $e->getTraceAsString()));
            }
            $msg = ['msg' => '抢占幸运位' . $tableConfig->tableId . '号桌抢座失败，您支付的' . $order->price . '番茄豆已为您退回至账户。'];
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $seatInfo->userId, 'type' => 0, 'msg' => $msg]);
            Log::info(sprintf('DuobaoTuikuan Yunxin userId=%d tableId=%s issueNum=%d price=%d resMsg=%s',
                $seatInfo->userId, $tableConfig->tableId, $order->issueNum, $order->price, $resMsg));
        }
    }

    public function tuikuan($tableId) {
        try {
            $tableConfig = Config::getInstance()->getTableConfig($tableId);
            $redis = RedisCommon::getInstance()->getRedis();
            $expKey = 'duobaoexp:' . $tableConfig->tableName;
            for ($i = 0; $i < 10; $i++) {
                $issueNum = $redis->lPop($expKey);
                $curTime = time();
                if (!empty($issueNum)) {
                    $issueNum = intval($issueNum);
                    $order = OrderDao::getInstance()->loadOrder($tableConfig->tableName, $issueNum);
                    $orderLog = $this->encodeOrderForLog($order);
                    if ($order->status == 2) {
                        Log::info(sprintf('DuobaoTuikuan WillTuikuan tableId=%s issueNum=%d orderInfo=%s',
                            $tableId, $issueNum, json_encode($orderLog)));
                        $orderKey = 'duobaoorder:' . $tableConfig->tableName . ':' . $issueNum;
                        $redis->hSet($orderKey, 'status', 3);
                        $this->_tuikuan($tableConfig, $order, $curTime);
                    } else {
                        Log::info(sprintf('DuobaoTuikuan AlreadyTuikuan tableId=%s issueNum=%d orderInfo=%s',
                            $tableId, $issueNum, json_encode($orderLog)));
                    }
                } else {
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage(). $e->getLine(). $e->getFile(). $e->getCode());
        }

    }

    public function makeOrderId($duobaoType, $issueNum) {
        return $duobaoType . '_' . $issueNum;
    }

    public function parseOrderId($orderId) {
        $parts = explode('_', $orderId);
        return [$parts[0], intval($parts[1])];
    }
}