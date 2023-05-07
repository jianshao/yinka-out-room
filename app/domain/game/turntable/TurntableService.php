<?php


namespace app\domain\game\turntable;
use app\common\RedisCommon;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\AssetNotEnoughException2;
use app\domain\game\GameService;
use app\domain\game\poolbase\RewardPool;
use app\domain\game\poolbase\RunningRewardPool;
use app\domain\game\turntable\dao\ReGiftDao;
use app\domain\game\turntable\dao\ReGiftStates;
use app\domain\game\turntable\dao\TurntableUserDao;
use app\domain\gift\GiftSystem;
use app\domain\mall\MallIds;
use app\domain\mall\service\MallService;
use app\domain\prop\PropKindBubble;
use app\domain\room\dao\RoomModelDao;
use app\query\prop\service\PropQueryService;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftUtils;
use app\event\TurntableEvent;
use app\service\LockService;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class TurntableService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TurntableService();
        }
        return self::$instance;
    }

    public function buildTurntablePoolKey($boxId) {
        return 'turntable_run_pool:' . $boxId;
    }

    public function buildFuxingRankKey($date) {
        return 'rank_turntable_fuxing:' . $date;
    }

    public function buildFuDiRankKey($date) {
        return 'rank_turntable_fudi:' . $date;
    }

    public function buildJinliRankKey($turntableId) {
        return 'rank_turntable_jinli:' . $turntableId;
    }

    public function getFuxingRankList($offset, $count, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        return $redis->zRevRange($this->buildFuxingRankKey($date),0,9,true);
    }

    public function getFuxingRankScore($userId, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $key = $this->buildFuxingRankKey($date);
        $score = $redis->zScore($key, $userId);
        if ($score === false) {
            return [-1, 0];
        }
        $rank = $redis->zRevRank($key, $userId);
        if ($rank === false) {
            return [-1, $score];
        }
        return [$rank, $score];
    }

    public function getFudiRankList($offset, $count, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        return $redis->zRevRange($this->buildFuDiRankKey($date), $offset, $offset + $count - 1,true);
    }

    public function getJinliRankList($turntableId, $offset, $count) {
        $key = $this->buildJinliRankKey($turntableId);
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = $redis->lRange($key, $offset, $offset + $count - 1);
        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $jsonObj = json_decode($data, true);
                $ret[] = [
                    'userId' => $jsonObj['userId'],
                    'roomId' => $jsonObj['roomId'],
                    'giftId' => $jsonObj['giftId'],
                    'count' => $jsonObj['count'],
                    'time' => $jsonObj['time']
                ];
            }
        }
        $total = $redis->lLen($key);
        if ($total === false) {
            $total = 0;
        }
        return [$total, $ret];
    }

    private function processRank($userId, $roomId, Turntable $box, $giftMap, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $score = GiftUtils::calcTotalValue($giftMap);
        // 福星 福地
        $redis->zIncrBy($this->buildFuxingRankKey($date), $score, $userId);
//        $redis->zIncrBy($this->buildFuDiRankKey($date), $score, $roomId);
        //瓜分番茄豆榜单
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        if (!empty($luckStarConfig)) {
            if($timestamp >= strtotime($luckStarConfig['start_time']) && $timestamp < strtotime($luckStarConfig['end_time'])) {
                $redis->zIncrBy('divide:beans:' . $date, $score, $userId);
            }
        }

        // 锦鲤
        $pushJinliCount = 0;
        $jinliRankKey = $this->buildJinliRankKey($box->turntableId);
        foreach ($giftMap as $giftId => $count) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind->price->count >= $box->inJinliRankGiftValue) {
                $redis->lPush($jinliRankKey, json_encode([
                    'userId' => $userId,
                    'roomId' => $roomId,
                    'giftId' => $giftId,
                    'count' => $count,
                    'time' => $timestamp
                ]));
                $pushJinliCount += 1;
            }
        }
        if ($pushJinliCount > 0) {
            $redis->lTrim($jinliRankKey, 0, 200 - 1);
        }
    }

    public function loadRunningRewardPool($turntableId, $poolId) {
        $box = TurntableSystem::getInstance()->findBox($turntableId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }

        $rewardPool = $box->findRewardPool($poolId);
        if ($rewardPool == null) {
            throw new FQException('奖池参数错误', 500);
        }

        return $this->ensurePoolExists($box, $rewardPool);
    }

    /**
     * 用户开宝箱
     *
     * @param $userId
     * @param $roomId
     * @param $turntableId
     * @param $count
     * @param bool $isTest
     */
    public function turnTable($userId, $roomId, $turntableId, $count, $autoBuy, $isTest=false) {
        // 检查次数
        if (!is_int($count)
            || $count <= 0
            || (!in_array($count, TurntableSystem::getInstance()->defaultCounts)
                && $count < TurntableSystem::getInstance()->customCountRange[0]
                && $count > TurntableSystem::getInstance()->customCountRange[1])) {
            throw new FQException('次数参数错误', 500);
        }

        $box = TurntableSystem::getInstance()->findBox($turntableId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }

        if (!$this->isBoxOpen($box)) {
            throw new FQException('暂未开启', 500);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($turntableId == 1 && $userModel->lvDengji < 20){
            throw new FQException('等级为二十级可解锁转盘玩法哦~', 500);
        }

        if ($turntableId == 2 && $userModel->lvDengji < 30){
            throw new FQException('等级为三十级可解锁高级玩法哦~', 500);
        }

        $lockKey = 'turntable:lock:' . $box->turntableId . ':' . $userId;

        // 用户加锁
        //LockService::getInstance()->lock($lockKey, 10);
        try {
            // 抽奖消耗钥匙
            list($totalPrice, $balance, $poolId, $giftMap) = $this->turnTableImpl($userId, $roomId, $box, $count, $autoBuy, $isTest);
            return [$totalPrice, $balance, $giftMap];
        } finally {
          //  LockService::getInstance()->unlock($lockKey);
        }
    }

    public function encodeGiftKind($giftKind, $num, $isSpecial) {
        return [
            'id' => $giftKind->kindId,
            'gift_name' => $giftKind->name,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_coin' => $giftKind->price->count,
            'num' => $num,
            'is_special' => $isSpecial
        ];
    }

    /**
     * 组装转盘消息
     * @param $event TurntableEvent
     */
    public function packageScreenMessage($event) {
        $giftMap = $event->deliveryGiftMap;
        if (!empty($giftMap)) {
            $res = [];
            foreach ($giftMap as $giftId => $count) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                if ($giftKind) {
                    $res[] = $this->encodeGiftKind($giftKind, $count, 0);
                }
            }

            $field='id,pretty_room_id,user_id,room_name';
            $roomRes=RoomModelDao::getInstance()->loadRoomDataField($event->roomId,$field);

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->userId);

            $fullPublicGiftValue = TurntableSystem::getInstance()->fullPublicGiftValue;
            $fullFlutterGiftValue = TurntableSystem::getInstance()->fullFlutterGiftValue;
            $userInfo = UserModelDao::getInstance()->loadUserModel($event->userId);
            $bubble = PropQueryService::getInstance()->getWaredProp($event->userId, PropKindBubble::$TYPE_NAME);
            //判断消息是全服或者房间消息
            foreach ($res as $key => $value) {
                if ($value['gift_coin'] >= $fullFlutterGiftValue) { //全服飘屏
                    $socketFullFloatMsg[] = [
                        'userIdentity' => $userIdentity,
                        'userId' => $event->userId,
                        'prettyId' => $userInfo->prettyId,
                        'userLevel' => $userInfo->lvDengji,
                        'nickName' => $userInfo->nickname,
                        'roomName' => $roomRes['room_name'],
                        'showType' => 0,
                        'giftId' => $value['id'],
                        'giftName' => $value['gift_name'],
                        'giftUrl' => $value ['gift_image'],
                        'count' => $value['num'],
                        'isVip' => $userInfo->vipLevel,
                        'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                        'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                    ];
                }
                if ($value['gift_coin'] >= $fullPublicGiftValue) { //全服公屏
                    $socketFullScreenMsg[] = [
                        'userIdentity' => $userIdentity,
                        'userId' => $event->userId,
                        'prettyId' => $userInfo->prettyId,
                        'userLevel' => $userInfo->lvDengji,
                        'nickName' => $userInfo->nickname,
                        'roomName' => $roomRes['room_name'],
                        'showType' => 1,
                        'giftId' => $value['id'],
                        'giftName' => $value['gift_name'],
                        'giftUrl' => $value ['gift_image'],
                        'count' => $value['num'],
                        'isVip' => $userInfo->vipLevel,
                        'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                        'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                        '_full' => '1',
                        '_gift_coin' => $value['gift_coin'],
                        '_fullPublicGiftValue' => $fullPublicGiftValue
                    ];
                }
                //自己房间所有的公屏消息
                $socketMyselfRoomMsg[] = [
                    'userIdentity' => $userIdentity,
                    'userId' => $event->userId,
                    'prettyId'=> $userInfo->prettyId,
                    'userLevel' => $userInfo->lvDengji,
                    'nickName' => $userInfo->nickname,
                    'roomName' => $roomRes['room_name'],
                    'showType' => 1,
                    'giftId' => $value['id'],
                    'giftName' => $value['gift_name'],
                    'giftUrl' => $value['gift_image'],
                    'count' => $value['num'],
                    'isVip' => $userInfo->vipLevel,
                    'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                    'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                ];
            }
            //判断游戏房间不发消息
            $is_guild_id=RoomModelDao::getInstance()->loadRoomTypeForId($event->roomId);

            if (!in_array($is_guild_id, [4,5])) {
                if (!empty($socketFullFloatMsg)) {
                    $strFull = [
                        'msgId'=>2070,
                        'actionName' => '参与幸运转盘获得',
                        'actionType' => 'turntable',
                        'items'=>$socketFullFloatMsg,
                        '_t' => 'float'
                    ];
                    $msgFullFloat['msg'] = json_encode($strFull);
                    $msgFullFloat['roomId'] = 0;
                    $msgFullFloat['toUserId'] = '0';
                    $msgFullFloat['fromUserId'] = (int)$event->userId;
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgFullFloat);
                }
                if (!empty($socketFullScreenMsg)) {
                    $strFull = [
                        'msgId'=>2070,
                        'actionName' => '参与幸运转盘获得',
                        'actionType' => 'turntable',
                        'items'=>$socketFullScreenMsg
                    ];
                    $msgFullScreen['msg'] = json_encode($strFull);
                    $msgFullScreen['roomId'] = 0;
                    $msgFullScreen['toUserId'] = '0';
                    $msgFullScreen['fromRoomId'] = (int)$event->roomId;
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgFullScreen);
                }
                if (!empty($socketMyselfRoomMsg) && $event->roomId != 0) {
                    $strRoom = [
                        'msgId'=>2070,
                        'actionName' => '参与幸运转盘获得',
                        'actionType' => 'turntable',
                        'items'=>$socketMyselfRoomMsg
                    ];
                    $msgMyselfRoom['msg'] = json_encode($strRoom);
                    $msgMyselfRoom['roomId'] = (int)$event->roomId;
                    $msgMyselfRoom['toUserId'] = '0';
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgMyselfRoom);
                }
            }
        }
    }

    private function removeReGift(&$reGiftList, &$reGift) {
        for ($i = 0; $i < count($reGiftList); $i++) {
            if ($reGiftList[$i]->id == $reGift->id) {
                array_splice($reGiftList, $i,1);
                return true;
            }
        }
        return false;
    }

    private function doTurnRewardPool($userId, $roomId, $box, $poolId, $count, $reGiftList) {
        $breakReGiftList = [];
        try {
            $rewardPool = $box->findRewardPool($poolId);
            $key = $this->buildTurntablePoolKey($box->turntableId);
            $poolConfStr = $rewardPool->encodeToDaoRedisJson();
            $reGiftId = count($reGiftList) > 0 ? $reGiftList[0]->giftId : null;
            list($giftMap, $breakReGiftId) = RunningRewardPool::breakGift($key, $rewardPool->poolId, $count, $poolConfStr, $reGiftId);

            Log::info(sprintf('TurntableService::doTurnRewardPool userId=%d, roomId=%d turntableId=%d poolId=%d reGiftId=%d, breakReGiftId=%s',
                $userId, $roomId, $box->turntableId, $rewardPool->poolId, $reGiftId, $breakReGiftId));


            if ($breakReGiftId){
                $breakReGiftList[] = $reGiftList[0];
                $reGiftList = [];
            }

            Log::info(sprintf('TurntableService::doTurnRewardPool userId=%d, roomId=%d turntableId=%d poolId=%d giftMap=%s, breakReGiftList=%s reGiftList=%s',
                $userId, $roomId, $box->turntableId, $rewardPool->poolId, json_encode($giftMap), json_encode($breakReGiftList), json_encode($reGiftList)));

            return [$giftMap, $breakReGiftList, $reGiftList];
        } catch (\Exception $e) {
            Log::error(sprintf('TurntableService::doTurnRewardPool userId=%d roomId=%d turntableId=%d poolId=%d ex=%d:%s trace=%s',
                $userId, $roomId, $box->turntableId, $rewardPool->poolId,
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    private function ensurePoolExists($box, $rewardPool) {
        $key = $this->buildTurntablePoolKey($box->turntableId);
        $poolConfStr = $rewardPool->encodeToDaoRedisJson();
        list($ec, $curPoolStr) = RunningRewardPool::ensurePoolExists($key, $rewardPool->poolId, $rewardPool->encodeToDaoRedisJson());
        Log::info(sprintf('TurntableService::ensurePoolExists turntableId=%d poolId=%d poolConfStr=%s curPoolStr=%s',
            $box->turntableId, $rewardPool->poolId, $poolConfStr, $curPoolStr));

        $poolType = $rewardPool->poolType;
        $rewardPool = new RewardPool($box->turntableId, $rewardPool->poolId);
        $rewardPool->poolType = $poolType;
        $curPool = json_decode($curPoolStr, true);
        $rewardPool->decodeFromRedisJson($curPool);

        return $rewardPool;
    }

    private function updateReGiftList($breakReGiftList, $reGiftList) {
        if (!empty($breakReGiftList)) {
            foreach ($breakReGiftList as $reGift) {
                ReGiftDao::getInstance()->updateReGiftState($reGift->id, ReGiftStates::$SENT);
            }
        }
        if (!empty($reGiftList)) {
            foreach ($reGiftList as $reGift) {
                ReGiftDao::getInstance()->updateReGiftState($reGift->id, ReGiftStates::$NORMAL);
            }
        }
    }

    private function turnRewardPool($userId, $roomId, Turntable $box, $count, $totalPrice) {
        $reGiftList = ReGiftDao::getInstance()->loadAndProcessReGifts($userId, $box->turntableId, 1);
        $boxUser = TurntableUserDao::getInstance()->loadBoxUser($userId, $box->turntableId);
        $rewardPool = $box->chooseRewardPool($boxUser);
        $this->ensurePoolExists($box, $rewardPool);
        try {
            list($giftMap, $breakRGiftList, $reGiftList) = $this->doTurnRewardPool($userId, $roomId, $box, $rewardPool->poolId, $count, $reGiftList);
        } catch (\Exception $e) {
            // 有异常，恢复指定奖励
            $this->updateReGiftList([], $reGiftList);
            throw $e;
        }

        // 更新指定奖励
        $this->updateReGiftList($breakRGiftList, $reGiftList);

        // 如果更换了池子，清除更换前的池子
        $boxUser->entryPool($rewardPool->poolType, $rewardPool->poolId);
        $boxUser->add($rewardPool->poolId, $totalPrice, GiftUtils::calcTotalValue($giftMap));

        TurntableUserDao::getInstance()->saveBoxUser($boxUser);

        $reGiftMap = [];
        foreach ($breakRGiftList as $reGift) {
            if (array_key_exists($reGift->giftId, $reGiftMap)) {
                $reGiftMap[$reGift->giftId] += 1;
            } else {
                $reGiftMap[$reGift->giftId] = 1;
            }
        }
        return [$rewardPool ? $rewardPool->poolId : 0, $reGiftMap, $giftMap, $boxUser];
    }

    private function deliveryGifts($userId, $roomId, $box, $poolId, $count, $giftMap, $timestamp) {
        $deliveryGiftMap = [];
        foreach ($giftMap as $giftId => $giftCount) {
            $deliveryGiftMap[$giftId] = $giftCount;
        }

        $assetList = [];
        foreach ($deliveryGiftMap as $giftId => $giftCount) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind) {
                $giftValue = $giftKind->price != null ? $giftKind->price->count * $giftCount : 0;
                $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'turntable', $box->turntableId, $count, $giftValue);
                $assetList[] = [AssetUtils::makeGiftAssetId($giftId), $giftCount, $biEvent];
            }
        }

        AssetUtils::addAssets($userId, $assetList, $timestamp);
    }

    private function tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp) {
        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'turntable', $box->turntableId, $count);
        return AssetUtils::consumeAsset($userId, GameService::getInstance()->priceAssetId, $totalPrice, $timestamp, $biEvent);
    }

    private function collectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp) {
        list($consume, $balance) = $this->tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return $balance;
        }

        // 费用不足
        if (!$autoBuy) {
            throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
        }

        // 计算需要购买商品数量
        $rem = $totalPrice - $balance;
        $goods = GameService::getInstance()->getGoods();
        $countPerGoods = $goods->deliveryAsset->count;
        $goodsCount = intval(($rem + ($countPerGoods - 1)) / $countPerGoods);

        // 购买商品
        try {
            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $goodsCount, MallIds::$GAME, 'turntable');
        } catch (AssetNotEnoughException $e) {
            throw new AssetNotEnoughException2(AssetKindIds::$BEAN, '积分不足', 211);
        }

        list($consume, $balance) = $this->tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return $balance;
        }

        throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
    }

    private function turnTableImpl($userId, $roomId, $box, $count, $autoBuy, $isTest) {
        $timestamp = time();
        $balance = 0;
        $totalPrice = (int)$count * $box->price;

        if (!$isTest) {
            // 收费
            $balance = $this->collectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp);
        }

        list($poolId, $reGiftMap, $giftMap) = $this->turnRewardPool($userId, $roomId, $box, $count, $totalPrice);

        if (!$isTest) {
            $this->deliveryGifts($userId, $roomId, $box, $poolId, $count, $giftMap, $timestamp);
            $this->processRank($userId, $roomId, $box, $giftMap, $timestamp);
        }

        Log::info(sprintf('TurntableService::turnTableImpl userId=%d roomId=%d turntableId=%d poolId=%d count=%d isTest=%d totalPrice=%d balance=%d regiftMap=%s giftMap=%s',
            $userId, $roomId, $box->turntableId, $poolId, $count, $isTest, $totalPrice, $balance, json_encode($reGiftMap), json_encode($giftMap)));

        if (!$isTest) {
            event(new TurntableEvent($userId, $roomId, $box->turntableId, $count,
                [new AssetItem(GameService::getInstance()->priceAssetId, $totalPrice)],
                $giftMap, $timestamp));
        }
        return [$totalPrice, $balance, $poolId, $giftMap];
    }

    public function refreshRewardPool($turntableId, $poolId) {
        $box = TurntableSystem::getInstance()->findBox($turntableId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }
        $rewardPool = $box->findRewardPool($poolId);
        if ($rewardPool == null) {
            throw new FQException('奖池参数错误', 500);
        }

        $key = $this->buildTurntablePoolKey($box->turntableId);
        RunningRewardPool::refreshPool($key, $poolId, $rewardPool->encodeToDaoRedisJson());

        Log::info(sprintf('TurntableService::refreshRewardPool turntableId=%d poolId=%d runningRewardPool=%s',
            $box->turntableId, $rewardPool->poolId, json_encode($rewardPool->giftMap)));
        return $rewardPool;
    }

//    private function refreshRewardPoolImpl($box, $rewardPool) {
//        $giftMap = [];
//        foreach ($rewardPool->giftMap as $giftId => $weigth) {
//            $giftMap[$giftId] = $weigth;
//        }
//        $runningRewardPool = new RunningRewardPool($box->turntableId, $rewardPool->poolId);
//        $runningRewardPool->giftMap = $giftMap;
//        RunningRewardPoolDao::getInstance()->updateRewardPool($runningRewardPool);
//
//        Log::info(sprintf('TurntableService::refreshRewardPoolImpl turntableId=%d poolId=%d giftMap=%s',
//            $box->turntableId, $rewardPool->poolId, json_encode($giftMap)));
//
//        return $runningRewardPool;
//    }

    private function isBoxOpen($box) {
        return TurntableSystem::getInstance()->isOpen == 1;
    }
}