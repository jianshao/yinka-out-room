<?php


namespace app\domain\game\box\service;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\dao\MonitoringModelDao;
use app\domain\dao\ReUserGiftModelDao;
use app\domain\exceptions\FQException;
use app\domain\game\box\BoxIds;
use app\domain\game\box\BoxInfo;
use app\domain\game\box\BoxSystem;
use app\domain\gift\GiftSystem;
use app\domain\prop\PropKindBubble;
use app\domain\room\dao\RoomModelDao;
use app\query\prop\service\PropQueryService;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\event\BreakBoxEvent;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;

class BoxService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BoxService();
        }
        return self::$instance;
    }

    public function getBoxInfo($userId, $boxId) {
        $box = BoxSystem::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }
        return $this->getBoxInfoImpl($userId, $box);
    }

    private function getBoxInfoImpl($userId, $box) {
        $ret = new BoxInfo($box);
        $ret->selfProgress = $this->loadSelfProgress($userId, $box->boxId);
        $ret->globalProgress = $this->loadGlobalProgress($box->boxId);
        return $ret;
    }

    public function buildSelfProgressKey($userId, $boxId) {
        if ($boxId == BoxIds::$GOLD) {
            return 'self_rate_gold_rey_' . $userId;
        } elseif ($boxId == BoxIds::$SILVER) {
            return 'self_rate_silver_rey_' . $userId;
        }
        throw new FQException('宝箱参数错误', 500);
    }

    public function buildGlobalProgressKey($boxId) {
        if ($boxId == BoxIds::$GOLD) {
            return 'all_rate_gold_key';
        } elseif ($boxId == BoxIds::$SILVER) {
            return 'all_rate_silver_key';
        } else {
            throw new FQException('宝箱参数错误', 500);
        }
    }

    public function loadSelfProgress($userId, $boxId) {
        $key = $this->buildSelfProgressKey($userId, $boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->get($key);
        return empty($ret) ? 0 : intval($ret);
    }

    public function incrSelfProgress($userId, $boxInfo, $count) {
        $key = $this->buildSelfProgressKey($userId, $boxInfo->box->boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        if ($boxInfo->selfProgress > 0) {
            $boxInfo->selfProgress = $redis->incrBy($key, $count);
        } else {
            //个人过期时间  明天五点
            $tomorrow = strtotime(date("Y-m-d",strtotime("+1 day"))) - time() + 18000;
            $redis->setex($key, $tomorrow, $count);//添加个人进度
            $boxInfo->selfProgress = $count;
        }
        return $boxInfo->selfProgress;
    }

    public function clearSelfProgress($userId, $box) {
        $key = $this->buildSelfProgressKey($userId, $box->boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del($key);
    }

    public function clearGlobalProgress($box) {
        $key = $this->buildGlobalProgressKey($box->boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del($key);
    }

    public function loadGlobalProgress($boxId) {
        $key = $this->buildGlobalProgressKey($boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = $redis->get($key);
        return empty($ret) ? 0 : intval($ret);
    }

    public function incrGlobalProgress($boxInfo, $count) {
        $key = $this->buildGlobalProgressKey($boxInfo->box->boxId);
        $redis = RedisCommon::getInstance()->getRedis();

        if ($boxInfo->globalProgress > 0) {
            $boxInfo->globalProgress = $redis->incrBy($key, $count);
        } else {
            $redis->set($key, $count);
            $boxInfo->globalProgress = $count;
        }
        return $boxInfo->globalProgress;
    }

    public function buildPoolKey($boxId) {
        if ($boxId == BoxIds::$GOLD) {
            return 'PoolNumPropKey_G';
        } elseif ($boxId == BoxIds::$SILVER) {
            return 'PoolNumPropKey_S';
        }
        throw new FQException('宝箱参数错误', 500);
    }

    public function buildPoolFullKey($boxId) {
        if ($boxId == BoxIds::$GOLD) {
            return 'PoolNumPropFullKey_G';
        } elseif ($boxId == BoxIds::$SILVER) {
            return 'PoolNumPropFullKey_S';
        } else {
            throw new FQException('宝箱参数错误', 500);
        }
    }

    public function incrPool($boxId, $count) {
        $key = $this->buildPoolKey($boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->incrBy($key, $count);
    }

    public function setPool($boxId, $count) {
        $key = $this->buildPoolKey($boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->set($key, $count);
    }

    public function setPoolFull($boxId, $count) {
        $key = $this->buildPoolFullKey($boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->set($key, $count);
    }

    public function incrPoolFull($boxId, $count) {
        $key = $this->buildPoolFullKey($boxId);
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->incrBy($key, $count);
    }

    public function loadWinRecords($boxId, $offset, $count) {
        $key = $boxId == BoxIds::$GOLD ? 'winrecord_jin_box' : 'winrecord_yin_box';
        $redis = RedisCommon::getInstance()->getRedis();
        $winrecordBox = $redis->Zrevrange($key, $offset, $count, true);
        $ret = [];
        if (!empty($winrecordBox)) {
            foreach ($winrecordBox as $key => $value) {
                $parts = explode('_', $key);
                $ret[] = [
                    $parts[0], $parts[1], $value
                ];
            }
        }
        return $ret;
    }

    public function breakBox($userId, $roomId, $boxId, $count) {
        $monitoringModel = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($monitoringModel != null) {
            throw new FQException('青少年模式已开启', 500);
        }

        if (!is_integer($count) || $count <= 0) {
            throw new FQException('开启次数参数错误', 500);
        }

        $box = BoxSystem::getInstance()->findBox($boxId);

        if ($box == null) {
            throw new FQException('宝箱参数错误', 500);
        }

        $timestamp = time();

        $consumeAssets = $this->collectFee($userId, $roomId, $box, $count, $timestamp);

        // 砸蛋
        list($giftMap, $selfSpecialGiftId, $globalSpecialGiftId) = $this->breakEgg($userId, $box, $count, $timestamp);

        // 发礼物
        $this->deliveryGifts($userId, $roomId, $box, $count, $giftMap, $selfSpecialGiftId, $globalSpecialGiftId, $timestamp);

        $redis = RedisCommon::getInstance()->getRedis();
        $key = $boxId == BoxIds::$GOLD ? 'winrecord_jin_box' : 'winrecord_yin_box';
        if ($selfSpecialGiftId != null) {
            $box = $userId.'_'.$selfSpecialGiftId.'_'.$timestamp;
            $redis->ZADD($key, $timestamp, $box);
        }
        if ($globalSpecialGiftId != null) {
            $box = $userId.'_'.$globalSpecialGiftId.'_'.$timestamp;
            $redis->ZADD($key, $timestamp, $box);
        }

        event(new BreakBoxEvent($userId, $roomId, $boxId, $count, $consumeAssets, $giftMap, $selfSpecialGiftId, $globalSpecialGiftId, $timestamp));

        return [$giftMap, $selfSpecialGiftId, $globalSpecialGiftId];
    }

    private function collectFee($userId, $roomId, $box, $count, $timestamp) {
        $hammerCount = 0;
        $priceTotal = 0;
        $consumeAssets = [];
        try {
            $consumeAssets = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
                $userId, $roomId, $box, $count, $timestamp, $hammerCount, $consumeAssets) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $userAssets = $user->getAssets();
                if ($box->hammerPropId != null) {
                    $hammerCount = $userAssets->getPropBag($timestamp)->balance($box->hammerPropId, $timestamp);
                    $hammerCount = min($count, $hammerCount);
                }
                $priceCount = $count - $hammerCount;
                $priceTotal = $priceCount * $box->price->count;
                if ($priceCount > 0) {
                    if ($userAssets->balance($box->price->assetId, $timestamp) < $priceTotal) {
                        throw new FQException('余额不足', 211);
                    }
                }
                if ($hammerCount > 0) {
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'box', $box->boxId, $count);
                    $userAssets->getPropBag($timestamp)->consumePropByUnit($box->hammerPropId, $hammerCount, $timestamp, $biEvent);
                    $consumeAssets[] = new AssetItem(AssetUtils::makePropAssetId($box->hammerPropId), $hammerCount);
                }

                if ($priceTotal > 0) {
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'box', $box->boxId, $count);
                    $userAssets->consume($box->price->assetId, $priceTotal, $timestamp, $biEvent);
                    $consumeAssets[] = new AssetItem($box->price->assetId, $priceTotal);
                }
                return $consumeAssets;
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('BoxService::collectFee userId=%d roomId=%d boxId=%s count=%d ex=%d:%s',
                    $userId, $roomId, $box->boxId, $count, $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('BoxService::collectFee userId=%d roomId=%d boxId=%s count=%d ex=%d:%s trace=%s',
                    $userId, $roomId, $box->boxId, $count, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
        Log::info(sprintf('BoxService::collectFee userId=%d roomId=%d boxId=%s count=%d hammerCount=%d priceTotal=%d',
            $userId, $roomId, $box->boxId, $count, $hammerCount, $priceTotal));

        return $consumeAssets;
    }

    private function getReGift($userId, $reType, $maxCount) {
        // map<giftId, count>
        $reGifts = ReUserGiftModelDao::getInstance()->getReUserGift($userId, $reType, $maxCount);

        foreach ($reGifts as $reGift) {
            ReUserGiftModelDao::getInstance()->updateGiftStatus($userId, $reGift['id']);
        }
        $ret = [];
        foreach ($reGifts as $reGift) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($reGift['gift_id']);
            if ($giftKind != null) {
                if (array_key_exists($giftKind->kindId, $ret)) {
                    $ret[$giftKind->kindId] += 1;
                } else {
                    $ret[$giftKind->kindId] = 1;
                }
            }
        }
        return $ret;
    }

    private function breakOnce($userId, $box) {
        $randomValue = mt_rand(1, $box->totalWeight);
        foreach ($box->giftWeightList as list($gift, $weightLimit, $weight)) {
            if ($randomValue <= $weightLimit) {
                return $gift;
            }
        }
        assert(0);
    }

    private function breakSpecial($userId, $box, $count, $timestamp) {
        $selfSpecialGiftId = null;
        $globalSpecialGiftId = null;

        $boxInfo = $this->getBoxInfoImpl($userId, $box);

        // 累加个人进度
        $this->incrSelfProgress($userId, $boxInfo, $count);
        // 累加全局进度
        $this->incrGlobalProgress($boxInfo, $count);

        // 累加奖池
        $poolValue = $box->price->count * $count * 0.5;
        $curPoolValue = $this->incrPool($box->boxId, $poolValue);
        $curPoolFullValue = $this->incrPoolFull($box->boxId, $poolValue);

        $selfSpecialGiftId = null;
        $globalSpecialGiftId = null;

        // 个人特殊礼物
        $percent = $curPoolValue ? floor($curPoolValue * 0.01) : 0;
        if ($percent >= $box->maxPool) {
            if ($boxInfo->selfProgress > $box->maxPersonalProgress) {
                // 个人进度满了
                $rankValue = mt_rand(1, $box->personalProgressFullTotalWeight);
                if ($rankValue <= 1) {
                    if (!empty($box->personalSpecialGifts) && $selfSpecialGiftId == null) {
                        $selfSpecialGiftId = $box->personalSpecialGifts[array_rand($box->personalSpecialGifts)]->kindId;
                    }
                    $this->clearSelfProgress($userId, $box);
                    $this->setPool($box->boxId, $curPoolValue - $box->personalSpecialGiftValue);
                }
            }
        }

        // 全局特殊礼物
        $percent = $curPoolFullValue ? floor($curPoolFullValue * 0.01) : 0;
        if ($percent >= $box->maxPool) {
            if ($boxInfo->globalProgress > $box->maxGlobalProgress) {
                // 个人进度满了
                $rankValue = mt_rand(1, $box->globalProgressFullTotalWeight);
                if ($rankValue <= 1) {
                    if (!empty($box->globalSpecialGifts) && $globalSpecialGiftId == null) {
                        $globalSpecialGiftId = $box->globalSpecialGifts[array_rand($box->globalSpecialGifts)]->kindId;
                    }
                    $this->clearGlobalProgress($box);
                    $this->setPoolFull($box->boxId, $curPoolFullValue - $box->globalSpecialGiftValue);
                }
            }
        }

        return [$selfSpecialGiftId, $globalSpecialGiftId];
    }

    private function breakEgg($userId, $box, $count, $timestamp) {
        $retType = $box->boxId == BoxIds::$GOLD ? 2 : 1;
        $giftMap = $this->getReGift($userId, $retType, $count);

        $remCount = $count - count($giftMap);

        list($selfSpecialGiftId, $globalSpecialGiftId) = $this->breakSpecial($userId, $box, $count, $timestamp);

        for ($i = 0; $i < $remCount; $i++) {
            // 砸一次蛋
            $giftKind = $this->breakOnce($userId, $box);
            if (array_key_exists($giftKind->kindId, $giftMap)) {
                $giftMap[$giftKind->kindId] += 1;
            } else {
                $giftMap[$giftKind->kindId] = 1;
            }
        }

        Log::info(sprintf('BoxSerivce::breakEgg userId=%d boxId=%s count=%d giftMap=%s selfSpecial=%d globalSpecial=%d',
            $userId, $box->boxId, $count, json_encode($giftMap),
            $selfSpecialGiftId, $globalSpecialGiftId));

        return [$giftMap, $selfSpecialGiftId, $globalSpecialGiftId];
    }

    private function breakEgg1($userId, $box, $count, $timestamp) {
        $retType = $box->boxId == BoxIds::$GOLD ? 2 : 1;
        $giftMap = $this->getReGift($userId, $retType, $count);

        $remCount = $count - count($giftMap);

        $selfSpecialGiftId = null;
        $globalSpecialGiftId = null;

        for ($i = 0; $i < $remCount; $i++) {
            Log::trace(sprintf('BoxService::breakEgg1 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            $boxInfo = $this->getBoxInfoImpl($userId, $box);

            Log::trace(sprintf('BoxService::breakEgg2 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            // 砸一次蛋
            $giftKind = $this->breakOnce($userId, $box);
            if (array_key_exists($giftKind->kindId, $giftMap)) {
                $giftMap[$giftKind->kindId] += 1;
            } else {
                $giftMap[$giftKind->kindId] = 1;
            }

            Log::trace(sprintf('BoxService::breakEgg3 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            // 累加个人进度
            $this->incrSelfProgress($userId, $boxInfo, 1);
            // 累加全局进度
            $this->incrGlobalProgress($boxInfo, 1);

            Log::trace(sprintf('BoxService::breakEgg4 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            // 累加奖池
            $poolValue = $box->price->count * 0.5;
            $curPoolValue = $this->incrPool($box->boxId, $poolValue);
            $curPoolFullValue = $this->incrPoolFull($box->boxId, $poolValue);

            Log::trace(sprintf('BoxService::breakEgg5 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            // 个人特殊礼物
            $percent = $curPoolValue ? floor($curPoolValue * 0.01) : 0;
            if ($percent >= $box->maxPool) {
                if ($boxInfo->selfProgress > $box->maxPersonalProgress) {
                    // 个人进度满了
                    $rankValue = mt_rand(1, $box->personalProgressFullTotalWeight);
                    if ($rankValue <= 1) {
                        if (!empty($box->personalSpecialGifts) && $selfSpecialGiftId == null) {
                            $selfSpecialGiftId = $box->personalSpecialGifts[array_rand($box->personalSpecialGifts)]->kindId;
                        }
                        $this->clearSelfProgress($userId, $box);
                        $this->setPool($box->boxId, $curPoolValue - $box->personalSpecialGiftValue);
                    }
                }
            }

            Log::trace(sprintf('BoxService::breakEgg6 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));

            // 全局特殊礼物
            $percent = $curPoolFullValue ? floor($curPoolFullValue * 0.01) : 0;
            if ($percent >= $box->maxPool) {
                if ($boxInfo->globalProgress > $box->maxGlobalProgress) {
                    // 个人进度满了
                    $rankValue = mt_rand(1, $box->globalProgressFullTotalWeight);
                    if ($rankValue <= 1) {
                        if (!empty($box->globalSpecialGifts) && $globalSpecialGiftId == null) {
                            $globalSpecialGiftId = $box->globalSpecialGifts[array_rand($box->globalSpecialGifts)]->kindId;
                        }
                        $this->clearGlobalProgress($box);
                        $this->setPoolFull($box->boxId, $curPoolFullValue - $box->globalSpecialGiftValue);
                    }
                }
            }

            Log::trace(sprintf('BoxService::breakEgg7 userId=%d boxId=%s count=%d remCount=%d cur=%d',
                $userId, $box->boxId, $count, $remCount, $i));
        }

        Log::info(sprintf('BoxSerivce::breakEgg userId=%d boxId=%s count=%d giftMap=%s selfSpecial=%d globalSpecial=%d',
            $userId, $box->boxId, $count, json_encode($giftMap),
            $selfSpecialGiftId, $globalSpecialGiftId));

        return [$giftMap, $selfSpecialGiftId, $globalSpecialGiftId];
    }

    private function deliveryGifts($userId, $roomId, $box, $count, $giftMap, $selfSpecialGiftKindId, $globalSpecialGiftKindId, $timestamp) {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
                $userId, $roomId, $box, $count, $giftMap, $selfSpecialGiftKindId, $globalSpecialGiftKindId, $timestamp
            ){
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                // 发头像框
                $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'box', $box->boxId, $count);
                if ($box->avatarKind) {
                    $propBag = $user->getAssets()->getPropBag($timestamp);
                    $propBag->addPropByUnit($box->avatarKind->kindId, $count, $timestamp, $biEvent);
                }
                $giftBag = $user->getAssets()->getGiftBag($timestamp);
                foreach ($giftMap as $kindId => $count) {
                    $giftBag->add($kindId, $count, $timestamp, $biEvent);
                }
                if ($selfSpecialGiftKindId != null) {
                    $giftBag->add($selfSpecialGiftKindId, 1, $timestamp, $biEvent);
                }
                if ($globalSpecialGiftKindId) {
                    $giftBag->add($globalSpecialGiftKindId, 1, $timestamp, $biEvent);
                }
            });
        } catch (Exception $e) {
            Log::error(sprintf('BoxService::deliveryGifts userId=%d roomId=%d boxId=%s giftMap=%s selfSpecial=%d globalSpecial=%d ex=%s',
                    $userId, $roomId, $box->boxId, json_encode($giftMap),
                    $selfSpecialGiftKindId, $globalSpecialGiftKindId,
                    $e->getTraceAsString()));
            throw $e;
        }

        Log::info(sprintf('BoxService::deliveryGifts userId=%d roomId=%d boxId=%s giftMap=%s selfSpecial=%d globalSpecial=%d',
            $userId, $roomId, $box->boxId, json_encode($giftMap),
            $selfSpecialGiftKindId, $globalSpecialGiftKindId));
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
        $selfSpecialGiftId = $event->deliverySelfSpecialGiftKindId;
        $globalSpecialGiftId = $event->deliveryGlobalSpecialGiftKindId;
        $giftMap = $event->deliveryGiftMap;
        if (!empty($giftMap)) {
            $res = [];
            foreach ($giftMap as $giftId => $count) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                if ($giftKind) {
                    $res[] = $this->encodeGiftKind($giftKind, $count, 0);
                }
            }
            if ($selfSpecialGiftId != null ) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($selfSpecialGiftId);
                if ($giftKind) {
                    array_unshift($res, $this->encodeGiftKind($giftKind, 1, 1));
                }
            }
            if ($globalSpecialGiftId != null ) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($globalSpecialGiftId);
                if ($giftKind) {
                    array_unshift($res, $this->encodeGiftKind($giftKind, 1, 1));
                }
            }
            $field='id,pretty_room_id,user_id,room_name';
            $roomRes=RoomModelDao::getInstance()->loadRoomDataField($event->roomId,$field);

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->userId);

            $eggCoin = BoxSystem::getInstance()->eggCoin;
            $fullServerCoin = BoxSystem::getInstance()->fullServerCoin;
            $userInfo = UserModelDao::getInstance()->loadUserModel($event->userId);
            $bubble = PropQueryService::getInstance()->getWaredProp($event->userId, PropKindBubble::$TYPE_NAME);
            //判断消息是全服或者房间消息     0float 1screen 2special
            foreach ($res as $key => $value) {
                if ( $value['is_special'] == 1 || $value['gift_coin'] >= $fullServerCoin) { //全服飘屏
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
                        'giftId' => $value['id'],
                        'giftName' => $value['gift_name'],
                        'giftUrl' => $value ['gift_image'],
                        'count' => $value['num'],
                        'isVip' => $userInfo->vipLevel,
                        'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                    ];
                }
                if ($value['gift_coin'] >= $eggCoin) { //全服公屏
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
                ];
            }
            //判断游戏房间不发消息
            $is_guild_id=RoomModelDao::getInstance()->loadRoomTypeForId($event->roomId);
            if (!in_array($is_guild_id, [4,5])) {
                if (!empty($socketFullFloatMsg)) {
                    $strFull = [
                        'msgId'=>2070,
                        'actionName' => '开宝箱',
                        'actionType' => 'box',
                        'items'=>$socketFullFloatMsg
                    ];
                    $msgFullFloat['msg'] = json_encode($strFull);
                    $msgFullFloat['roomId'] = 0;
                    $msgFullFloat['toUserId'] = '0';
                    RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msgFullFloat);
                }
                if (!empty($socketFullScreenMsg)) {
                    $strFull = [
                        'msgId'=>2070,
                        'actionName' => '开宝箱',
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
                        'actionName' => '开宝箱',
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
}