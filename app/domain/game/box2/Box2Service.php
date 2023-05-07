<?php


namespace app\domain\game\box2;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\AssetNotEnoughException2;
use app\domain\game\GameService;
use app\domain\gift\GiftSystem;
use app\domain\mall\MallIds;
use app\domain\mall\service\MallService;
use app\domain\prop\PropKindBubble;
use app\domain\room\dao\RoomModelDao;
use app\query\prop\service\PropQueryService;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\BreakBoxNewEvent;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftUtils;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class Box2Service
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Box2Service();
        }
        return self::$instance;
    }

    public function buildFuxingRankKey($date) {
        return 'rank_box2_fuxing:' . $date;
    }

    public function buildFuDiRankKey($date) {
        return 'rank_box2_fudi:' . $date;
    }

    public function buildJinliRankKey($boxId) {
        return 'rank_box2_jinli:' . $boxId;
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

    public function getJinliRankList($boxId, $offset, $count) {
        $key = $this->buildJinliRankKey($boxId);
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

    public function buyGoods($userId, $count, $roomId) {
        // 购买商品
        return MallService::getInstance()->buyGoodsByGoods($userId, GameService::getInstance()->getGoods(),
            $count, MallIds::$GAME, 'box2', $roomId);
    }

    private function processRank($userId, $roomId, Box2 $box, $giftMap, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $date = TimeUtil::timeToStr($timestamp, '%Y%m%d');
        $score = GiftUtils::calcTotalValue($giftMap);
        // 福星 福地
        $redis->zIncrBy($this->buildFuxingRankKey($date), $score, $userId);
        $redis->zIncrBy($this->buildFuDiRankKey($date), $score, $roomId);
        //瓜分番茄豆榜单
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        if (!empty($luckStarConfig)) {
            if($timestamp >= strtotime($luckStarConfig['start_time']) && $timestamp <= strtotime($luckStarConfig['end_time'])) {
                $redis->zIncrBy('divide:beans:' . $date, $score, $userId);
            }
        }
        // 锦鲤
        $pushJinliCount = 0;
        $jinliRankKey = $this->buildJinliRankKey($box->boxId);
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

    public function loadRunningRewardPool($boxId, $poolId) {
        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }
        $rewardPool = $box->findRewardPool($poolId);
        if ($rewardPool == null) {
            throw new FQException('奖池参数错误', 500);
        }
        $this->ensurePoolExists($box, $rewardPool);
        return RunningRewardPoolDao::getInstance()->loadRewardPool($box->boxId, $rewardPool->poolId);
    }

    /**
     * 用户开宝箱
     *
     * @param $userId
     * @param $roomId
     * @param $boxId
     * @param $count
     * @param bool $isTest
     */
    public function breakBox($userId, $roomId, $boxId, $count, $autoBuy, $isTest=false) {
        // 检查次数
        if (!is_int($count)
            || $count <= 0
            || (!in_array($count, Box2System::getInstance()->defaultCounts)
                && $count < Box2System::getInstance()->customCountRange[0]
                && $count > Box2System::getInstance()->customCountRange[1])) {
            throw new FQException('次数参数错误', 500);
        }

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }

        if (!$this->isBoxOpen($box)) {
            throw new FQException('暂未开启', 500);
        }

        $lockKey = 'box2:lock:' . $box->boxId . ':' . $userId;

        // 用户加锁
        //LockService::getInstance()->lock($lockKey, 10);
        try {
            // 抽奖消耗钥匙
            list($totalPrice, $balance, $poolId, $giftMap, $specialProgress, $specialGiftId) = $this->breakBoxImpl($userId, $roomId, $box, $count, $autoBuy, $isTest);
            return [$totalPrice, $balance, $giftMap, $specialProgress, $specialGiftId];
        } finally {
          //  LockService::getInstance()->unlock($lockKey);
        }
    }

    public function getSpecialProgressRate($boxId) {
        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('宝箱不存在', 500);
        }
        $progress = $this->getSpecialProgress($boxId);
        $progress = $box->specialConf->maxProgress != 0 ? intval($progress * 100 / $box->specialConf->maxProgress) : 0;
        return min($progress, 100);
    }

    public function getSpecialProgress($boxId) {
        $key = 'box2_special_progress';
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->hGet($key, $boxId);
        return empty($ret) ? 0 : intval($ret);
    }

    public function incSpecialProgress($boxId, $count) {
        $key = 'box2_special_progress';
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->hIncrBy($key, $boxId, $count);
        return intval($ret);
    }

    public function clearSpecialProgress($boxId) {
        $key = 'box2_special_progress';
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hSet($key, $boxId, 0);
    }

    public function incSpecialPoolValue($boxId, $value) {
        $key = 'box2_special_pool';
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->hIncrBy($key, $boxId, $value);
        return intval($ret);
    }

    public function getSpecialPoolValue($boxId) {
        $key = 'box2_special_pool';
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->hget($key, $boxId);
        return $ret === false ? 0 : intval($ret);
    }

    /**
     * 指定礼物
     *
     * @param $userId
     * @param $roomId
     * @param Box2 $box
     * @param $count
     * @return mixed|null
     */
    public function breakSpecial($userId, $roomId, Box2 &$box, $count) {
        // 累加全局进度
        $specialProgress = $this->incSpecialProgress($box->boxId, $count);

        // 累加奖池
        $poolValue = intval($box->price * $count * 0.5);
        $specialPoolValue = $this->incSpecialPoolValue($box->boxId, $poolValue);

        $specialGiftId = null;

        Log::info(sprintf('Box2Service::breakSpecial userId=%d roomId=%d boxId=%d count=%d poolValue=%d special=%d:%d giftWeight=%d',
            $userId, $roomId, $box->boxId, $count, $poolValue, $specialProgress, $specialPoolValue, $box->specialConf->giftWeight));

        if (!empty($box->specialConf)) {
            // 全局特殊礼物
            if ($specialProgress > $box->specialConf->maxProgress
                && $specialPoolValue >= $box->specialConf->maxPoolValue) {
                // 进度满了
                $randValue = mt_rand(1, $box->specialConf->giftWeight);

                if ($randValue <= 1) {
                    if (!empty($box->specialConf->giftIds)) {
                        $specialGiftId = $box->specialConf->giftIds[array_rand($box->specialConf->giftIds)];
                    }
                    $this->clearSpecialProgress($box->boxId);
                    $curPoolValue = $this->incSpecialPoolValue($box->boxId, -$box->specialConf->giftValue);
                    if ($curPoolValue < 0) {
                        $this->incSpecialPoolValue($box->boxId, $box->specialConf->giftValue);
                        $specialGiftId = null;
                    }
                    Log::info(sprintf('Box2Service::breakSpecial userId=%d roomId=%d boxId=%d count=%d poolValue=%d special=%d:%d giftWeight=%d randValue=%d giftIds=%s specialGiftId=%d',
                        $userId, $roomId, $box->boxId, $count, $poolValue, $specialProgress,
                        $specialPoolValue, $box->specialConf->giftWeight,
                        $randValue, json_encode($box->specialConf->giftIds), $specialGiftId));
                }
            }
        }

        return [$specialProgress, $specialGiftId];
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
     * 组装砸蛋消息
     * @param $event
     */
    public function packageScreenMessage($event) {
        $deliverySpecialGiftId = $event->deliverySpecialGiftId;
        $giftMap = $event->deliveryGiftMap;
        if (!empty($giftMap)) {
            $res = [];
            foreach ($giftMap as $giftId => $count) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                if ($giftKind) {
                    $res[] = $this->encodeGiftKind($giftKind, $count, 0);
                }
            }
            if ($deliverySpecialGiftId != null ) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($deliverySpecialGiftId);
                if ($giftKind) {
                    array_unshift($res, $this->encodeGiftKind($giftKind, 1, 1));
                }
            }

            $field='id,pretty_room_id,user_id,room_name';
            $roomRes=RoomModelDao::getInstance()->loadRoomDataField($event->roomId,$field);

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->userId);

            $fullPublicGiftValue = Box2System::getInstance()->fullPublicGiftValue;
            $fullFlutterGiftValue = Box2System::getInstance()->fullFlutterGiftValue;
            $userInfo = UserModelDao::getInstance()->loadUserModel($event->userId);
            $bubble = PropQueryService::getInstance()->getWaredProp($event->userId, PropKindBubble::$TYPE_NAME);
            //判断消息是全服或者房间消息     0float 1screen 2special
            foreach ($res as $key => $value) {
                if ( $value['is_special'] == 1 || $value['gift_coin'] >= $fullFlutterGiftValue) { //全服飘屏
                    if ($value['is_special'] == 1) {
                        $type = 2;
                    } else {
                        $type = 0;
                    }
                    $socketFullFloatMsg[] = [
                        'userIdentity' => $userIdentity,
                        'userId' => $event->userId,
                        'prettyId' => $userInfo->prettyId,
                        'userLevel' => $userInfo->lvDengji,
                        'nickName' => $userInfo->nickname,
                        'roomName' => $roomRes['room_name'],
                        'showType' => $type,
                        'isSpecial' => $value['is_special'],
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
                        'actionName' => '开宝箱获得',
                        'actionType' => 'box',
                        'items'=>$socketFullFloatMsg,
                        '_t' => 'float'
                    ];
                    $msgFullFloat['msg'] = json_encode($strFull);
                    $msgFullFloat['roomId'] = 0;
                    $msgFullFloat['toUserId'] = '0';
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgFullFloat);
                }
                if (!empty($socketFullScreenMsg)) {
                    $strFull = [
                        'msgId'=>2070,
                        'actionName' => '开宝箱获得',
                        'actionType' => 'box',
                        'items'=>$socketFullScreenMsg
                    ];
                    $msgFullScreen['msg'] = json_encode($strFull);
                    $msgFullScreen['roomId'] = 0;
                    $msgFullScreen['toUserId'] = '0';
                    $msgFullScreen['fromRoomId'] = (int)$event->roomId;
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgFullScreen);
                }
                if (!empty($socketMyselfRoomMsg)) {
                    $strRoom = [
                        'msgId'=>2070,
                        'actionName' => '开宝箱获得',
                        'actionType' => 'box',
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

    private function doBreakRewardPool($userId, $roomId, $box, $rewardPool, $count, $reGiftList) {
        try {
            return Sharding::getInstance()->getConnectModel('commonMaster',$userId)->transaction(function() use($userId, $box, $rewardPool, $count, $reGiftList) {
                $breakGiftMap = [];
                $breakReGiftList = [];
                $runningRewardPool = RunningRewardPoolDao::getInstance()->loadRewardPool($box->boxId, $rewardPool->poolId);
                if ($runningRewardPool == null) {
                    throw new FQException('奖池不存在', 500);
                }

                for ($i = 0; $i < $count; $i++) {
                    list($giftId, $reGift) = $runningRewardPool->randomGift($reGiftList);
                    if ($giftId == null) {
                        $runningRewardPool = $this->refreshRewardPoolImpl($box, $rewardPool);
                        list($giftId, $reGift) = $runningRewardPool->randomGift($reGiftList);
                    }
                    if ($giftId == null) {
                        throw new FQException('奖池配置错误', 500);
                    }

                    if ($reGift != null) {
                        $breakReGiftList[] = $reGift;
                        $this->removeReGift($reGiftList, $reGift);
                    }

                    if (array_key_exists($giftId, $breakGiftMap)) {
                        $breakGiftMap[$giftId] = $breakGiftMap[$giftId] + 1;
                    } else {
                        $breakGiftMap[$giftId] = 1;
                    }
                }
                RunningRewardPoolDao::getInstance()->updateRewardPool($runningRewardPool);
                return [$breakGiftMap, $breakReGiftList, $reGiftList];
            });
        } catch (\Exception $e) {
            Log::error(sprintf('Box2Service::doBreakBox userId=%d roomId=%d boxId=%d poolId=%d ex=%d:%s trace=%s',
                $userId, $roomId, $box->boxId, $rewardPool->poolId,
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    private function ensurePoolExists($box, $rewardPool) {
        if (!RunningRewardPoolDao::getInstance()->isRewardPoolExists($box->boxId, $rewardPool->poolId)) {
            try {
                $runningRewardPool = new RunningRewardPool($box->boxId, $rewardPool->poolId);
                RunningRewardPoolDao::getInstance()->insertRewardPool($runningRewardPool);
            } catch (\Exception $e) {
                Log::warning(sprintf('BreakEggService::ensurePoolExists boxId=%d poolId=%d ex=%d:%s trace=%s',
                    $box->boxId, $rewardPool->poolId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
        }
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

    private function breakRewardPool($userId, $roomId, Box2 $box, $count, $totalPrice) {
        $reGiftList = ReGiftDao::getInstance()->loadAndProcessReGifts($userId, $box->boxId, $count);
        $boxUser = Box2UserDao::getInstance()->loadBoxUser($userId, $box->boxId);
        $rewardPool = $box->chooseRewardPool($boxUser);
        $this->ensurePoolExists($box, $rewardPool);
        try {
            list($giftMap, $breakRGiftList, $reGiftList) = $this->doBreakRewardPool($userId, $roomId, $box, $rewardPool, $count, $reGiftList);
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

        Box2UserDao::getInstance()->saveBoxUser($boxUser);

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

    private function deliveryGifts($userId, $roomId, $box, $poolId, $count, $giftMap, $specialGiftId, $timestamp) {
        $deliveryGiftMap = [];
        foreach ($giftMap as $giftId => $giftCount) {
            $deliveryGiftMap[$giftId] = $giftCount;
        }
        if ($specialGiftId != null) {
            if (array_key_exists($specialGiftId, $deliveryGiftMap)) {
                $deliveryGiftMap[$specialGiftId] += 1;
            } else {
                $deliveryGiftMap[$specialGiftId] = 1;
            }
        }

        $assetList = [];
        foreach ($deliveryGiftMap as $giftId => $giftCount) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind) {
                $giftValue = $giftKind->price != null ? $giftKind->price->count * $giftCount : 0;
                $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'box2', $box->boxId, $count, $giftValue);
                $assetList[] = [AssetUtils::makeGiftAssetId($giftId), $giftCount, $biEvent];
            }
        }
        AssetUtils::addAssets($userId, $assetList, $timestamp);
    }

    private function tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp) {
        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'box2', $box->boxId, $count);
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
            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $goodsCount, MallIds::$GAME, 'box2');
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

    private function breakBoxImpl($userId, $roomId, $box, $count, $autoBuy, $isTest) {
        $timestamp = time();
        $balance = 0;
        $totalPrice = (int)$count * $box->price;

        if (!$isTest) {
            // 收费
            $balance = $this->collectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp);
        }

        list($specialProgress, $specialGiftId) = $this->breakSpecial($userId, $roomId, $box, $count);

        list($poolId, $reGiftMap, $giftMap) = $this->breakRewardPool($userId, $roomId, $box, $count, $totalPrice);

        if (!$isTest) {
            $this->deliveryGifts($userId, $roomId, $box, $poolId, $count, $giftMap, $specialGiftId, $timestamp);
            $this->processRank($userId, $roomId, $box, $giftMap, $timestamp);
        }

        Log::info(sprintf('Box2Service::breakBoxImpl userId=%d roomId=%d boxId=%d poolId=%d count=%d isTest=%d totalPrice=%d balance=%d specialGiftId=%d regiftMap=%s giftMap=%s',
            $userId, $roomId, $box->boxId, $poolId, $count, $isTest, $totalPrice, $balance, $specialGiftId, json_encode($reGiftMap), json_encode($giftMap)));

        if (!$isTest) {
            event(new BreakBoxNewEvent($userId, $roomId, $box->boxId, $count,
                [new AssetItem(GameService::getInstance()->priceAssetId, $totalPrice)],
                $giftMap, $specialGiftId, $timestamp));
        }
        return [$totalPrice, $balance, $poolId, $giftMap, $specialProgress, $specialGiftId];
    }

    public function refreshRewardPool($boxId, $poolId) {
        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }
        $rewardPool = $box->findRewardPool($poolId);
        if ($rewardPool == null) {
            throw new FQException('奖池参数错误', 500);
        }
        $this->ensurePoolExists($box, $rewardPool);
        $runningRewardPool = RunningRewardPoolDao::getInstance()->loadRewardPool($box->boxId, $rewardPool->poolId);
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() use($box, $rewardPool, $runningRewardPool) {
                if ($runningRewardPool == null) {
                    throw new FQException('奖池不存在', 500);
                }
                $runningRewardPool = $this->refreshRewardPoolImpl($box, $rewardPool);
                Log::info(sprintf('Box2Service::refreshRewardPool boxId=%d poolId=%d runningRewardPool=%s',
                    $box->boxId, $rewardPool->poolId, json_encode($runningRewardPool->giftMap)));
                return $runningRewardPool;
            });
        } catch (\Exception $e) {
            Log::error(sprintf('Box2Service::refreshRewardPool boxId=%d poolId=%d ex=%d:%s trace=%s',
                $box->boxId, $rewardPool->poolId,
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        Log::info(sprintf('Box2Service::refreshRewardPool boxId=%d poolId=%d runningRewardPool=%s',
            $box->boxId, $rewardPool->poolId, json_encode($runningRewardPool->giftMap)));
        return $runningRewardPool;
    }

    private function refreshRewardPoolImpl($box, $rewardPool) {
        $giftMap = [];
        foreach ($rewardPool->giftMap as $giftId => $weigth) {
            $giftMap[$giftId] = $weigth;
        }
        $runningRewardPool = new RunningRewardPool($box->boxId, $rewardPool->poolId);
        $runningRewardPool->giftMap = $giftMap;
        RunningRewardPoolDao::getInstance()->updateRewardPool($runningRewardPool);

        Log::info(sprintf('Box2Service::refreshRewardPoolImpl boxId=%d poolId=%d giftMap=%s',
            $box->boxId, $rewardPool->poolId, json_encode($giftMap)));

        return $runningRewardPool;
    }

    private function isBoxOpen($box) {
        return Box2System::getInstance()->isOpen == 1;
//        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
//        $isOpen = $redis->hGet('box2_switch', $box->boxId);
//        if ($isOpen === false) {
//            return true;
//        }
//        return $isOpen == 1;
    }
}