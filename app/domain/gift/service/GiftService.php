<?php

namespace app\domain\gift\service;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\gift\event\OpenGiftDomainEvent;
use app\domain\gift\event\ReceiveGiftDomainEvent;
use app\domain\gift\event\SendGiftDomainEvent;
use app\domain\gift\GiftKind;
use app\domain\gift\GiftSystem;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\user\service\UnderAgeService;
use app\domain\user\UserRepository;
use app\event\SendGiftEvent;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;
use Exception;

class GiftService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GiftService();
        }
        return self::$instance;
    }

    public function findReceiveUser($receiveUsers, $userId)
    {
        foreach ($receiveUsers as $receiveUser) {
            if ($userId == $receiveUser->userId) {
                return $receiveUser;
            }
        }
        return null;
    }

    /**
     * 背包礼物doAction
     *
     * @param $userId :
     * @param $giftIds : 礼物id
     * @param $action : 执行的动作
     * @param $actionParams : 扩展参数
     */
    public function doActionByGiftIds($userId, $giftIds, $action, $actionParams)
    {
        $timestamp = time();
        $totalAssetList = [];
        try {
           $totalAssetList = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use ($userId, $giftIds, $action, $actionParams, $timestamp, $totalAssetList) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                foreach ($giftIds as $giftId) {
                    $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                    if (empty($giftKind)) {
                        throw new FQException($giftKind->name . "ID错误", 500);
                    }

                    $giftAction = ArrayUtil::safeGet($giftKind->actionMap, $action);
                    if ($giftAction == null) {
                        throw new FQException($giftKind->name . '不能进行此操作', 500);
                    }

                    $userGiftBag = $user->getAssets()->getGiftBag($timestamp);
                    $count = $userGiftBag->balance($giftKind->kindId, $timestamp);
                    if ($count <= 0) {
                        throw new AssetNotEnoughException($giftKind->name . '数量不足', 500);
                    }

                    $assetList = $giftAction->doAction($userGiftBag, $giftKind, $count, $actionParams, $timestamp);

                    $totalAssetList[] = [$assetList, $count];
                    Log::info(sprintf('GiftService::doActionByGiftIds userId=%d action=%s giftIds=%s assetItemList=%s',
                        $userId, $action, json_encode($giftIds), json_encode($totalAssetList)));
                }
               return $totalAssetList;
            });
        } catch (Exception $e) {
            Log::error(sprintf('GiftService::doActionByGiftIds Exception userId=%d action=%s giftIds=%s ex=%d:%s',
                $userId, $action, json_encode($giftIds), $e->getCode(), $e->getMessage()));
            throw $e;
        }

        return $totalAssetList;
    }

    /**
     * 打开背包礼物
     *
     * @param roomId: 房间ID，如果为0表示私聊送礼
     * @param giftKind: 礼物
     * @param count: 数量
     */
    public function openGiftFromBag($roomId, $userId, $giftKind, $count)
    {
        assert(is_integer($roomId));
        assert(is_integer($count) && $count > 0);

        $timestamp = time();
        return $this->openGiftImpl($roomId, $userId, $giftKind, $count, $timestamp);
    }

    /**
     * M豆直送礼物实现
     * @param $roomId
     * @param $fromUserId
     * @param $receiveUsers
     * @param $giftKind
     * @param $count
     * @param $timestamp
     * @return array
     * @throws Exception
     */
    private function openGiftImpl($roomId, $userId, $giftKind, $count, $timestamp)
    {
        try {
           return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
               $userId, $timestamp, $roomId, $giftKind, $count
           ) {
                $user = $this->ensureUserExists($userId);

                $userAssets = $user->getAssets();
                $userGiftBag = $userAssets->getGiftBag($timestamp);
                $bagBalance = $userGiftBag->balance($giftKind->kindId, $timestamp);
                if ($bagBalance < $count) {
                    throw new AssetNotEnoughException('礼物数量不足', 500);
                }

                // 减去打开的礼物数量
                $biEvent = BIReport::getInstance()->makeOpenGiftBIEvent($roomId, $giftKind->kindId, $count);
                $balance = $userGiftBag->consume($giftKind->kindId, $count, $timestamp, $biEvent);

                // 自己获得奖励
                $gainAssets = [];
                foreach ($giftKind->gainContents as $content) {
                    $gainAssets[] = $content->getItem();
                }
                foreach ($gainAssets as $gainAsset) {
                    $user->getAssets()->add($gainAsset->assetId, $gainAsset->count, $timestamp, $biEvent);
                }

                event(new OpenGiftDomainEvent($roomId, $user, $giftKind, $count, $gainAssets, $timestamp));
                return [$balance, $gainAssets];
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('OpenGiftException roomId=%d userId=%d %d ex=%d:%s',
                    $roomId, $userId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('OpenGiftException roomId=%d userId=%d %d ex=%d:%s trace=%s',
                    $roomId, $userId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 用户送礼
     *
     * @param roomId: 房间ID，如果为0表示私聊送礼
     * @param fromUserId: 打赏人
     * @param receiveUsers: 被打赏人列表
     * @param giftKind: 礼物
     * @param count: 数量
     * @param string $edition : 版本信息区分v1,v2
     */
    public function sendGift($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $edition = "")
    {
        assert(is_integer($roomId));
        assert(is_integer($fromUserId));
        assert(is_array($receiveUsers));
        assert(is_integer($count) && $count > 0);

        // 检查receiveUsers参数正确性
        $this->checkReceiveUsers($receiveUsers);

//        if ($this->findReceiveUser($receiveUsers, $fromUserId) != null) {
//            throw new FQException('不能送礼给自己', 500);
//        }

        $this->ensureRoomExists($roomId);

        $this->checkReceiveUsersExists($receiveUsers);

        $timestamp = time();
        // [list<ReceiveUser, list<GiftDetails>>]
        list($sendDetails, $superAssets ,$fromUserBeanBalance) = $this->sendGiftImpl($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $timestamp);
        list($receiveDetails,$receiverUserDiamondBalance) = $this->receiveGift($roomId, $fromUserId, $sendDetails, $giftKind, $count, false);

        event(new SendGiftEvent($roomId, $fromUserId, $receiveUsers, $giftKind, $count, false, $sendDetails, $receiveDetails, $timestamp, $superAssets, $edition ,$fromUserBeanBalance , $receiverUserDiamondBalance));

        return [$sendDetails, $receiveDetails, $superAssets];
    }

    /**
     * 用户从背包送礼
     *
     * @param roomId: 房间ID，如果为0表示私聊送礼
     * @param fromUserId: 打赏人
     * @param receiveUsers: 被打赏人列表
     * @param giftKind: 礼物
     * @param count: 数量
     * @param string $edition : 版本信息区分v1,v2
     */
    public function sendGiftFromBag($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $skip, $edition = "")
    {
        assert(is_integer($roomId));
        assert(is_integer($fromUserId));
        assert(is_array($receiveUsers));
        assert(is_integer($count) && $count > 0);

        // 检查toUserIds参数正确性
        $this->checkReceiveUsers($receiveUsers);

        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($fromUserId);
        if($isUnderAge){
            throw new FQException('未满18周岁用户暂不支持此功能', 500);
        }
//        if ($this->findReceiveUser($receiveUsers, $fromUserId) != null) {
//            throw new FQException('不能打赏自己', 500);
//        }

        if (!$giftKind->canSendFromBag()) {
            throw new FQException('不支持背包赠送', 500);
        }

        $this->ensureRoomExists($roomId);

        $this->checkReceiveUsersExists($receiveUsers);

        $timestamp = time();

        // [ReceiveUser, [GiftDetails]
        $sendDetails = $this->sendGiftFromBagImpl($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $timestamp, $skip);
        // [ReceiveUser, [GiftDetails]]
        list($receiveDetails) = $this->receiveGift($roomId, $fromUserId, $sendDetails, $giftKind, $count, true);

        event(new SendGiftEvent($roomId, $fromUserId, $receiveUsers, $giftKind, $count, true, $sendDetails, $receiveDetails, $timestamp, $edition));

        return [$sendDetails, $receiveDetails];
    }

    private function receiveGift($roomId, $fromUserId, $sendDetails, $giftKind, $count, $fromBag)
    {
        $realReceiveDetails = [];
        $receiverUserDiamondBalance = [];
        foreach ($sendDetails as $item) {
            $timestamp = time();
            $receiveUser = $item[0];
            $detailsList = $item[1];
            try {
                list($receiverUser,$receiverUserDiamondBalance) = $this->receiveGiftImpl($roomId, $receiveUser, $fromUserId, $detailsList, $timestamp, $fromBag, $receiverUserDiamondBalance);
                $realReceiveDetails[] = $item;
            } catch (Exception $e) {
                Log::error(sprintf('GiftService::sendGiftFromBag roomId=%d fromUserId=%d receiverUser=%d:%d giftId=%d count=%d',
                    $roomId, $fromUserId, $receiveUser->userId, $receiveUser->micId, $giftKind->kindId, $count));
            }
        }
        return [$realReceiveDetails,$receiverUserDiamondBalance];
    }

    private function ensureUserExists($userId)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        if ($user == null) {
            throw new FQException('用户不存在', 500);
        }
        return $user;
    }

    private function checkReceiveUsersExists($receiveUsers)
    {
        foreach ($receiveUsers as $receiveUser) {
            if (!UserModelDao::getInstance()->isUserIdExistsNotCancel($receiveUser->userId)) {
                throw new FQException('用户不存在', 500);
            }
        }
    }

    public function ensureRoomExists($roomId)
    {
        if ($roomId > 0) {
            if (!RoomModelDao::getInstance()->isRoomExists($roomId)) {
                throw new FQException('房间不存在', 500);
            }
        }
    }

    private function checkReceiveUsers($receiveUsers)
    {
        if (count($receiveUsers) <= 0) {
            throw new FQException('收礼人参数错误', 500);
        }
    }

    private function ensureCanSend($user, $giftKind)
    {
        $userModel = $user->getUserModel();
        if (empty($userModel->username)) {
            throw new FQException('您还没有绑定手机号', 5100);
        }

        if ($giftKind->price == null || $giftKind->price->count <= 0) {
            return false;
        }

        if ($giftKind->vipLevel > 0) {
            if ($userModel->vipLevel < $giftKind->vipLevel) {
                if ($giftKind->vipLevel == 1) {
                    throw new FQException('开通VIP后可赠送此礼物', 213);
                } else {
                    throw new FQException('开通SVIP后可赠送此礼物', 214);
                }
            }
        }

        if ($giftKind->dukeLevel > 0) {
            $duke = $user->getDuke(time());
            $duke->adjust(time());
            if ($duke->getModel()->dukeLevel < $giftKind->dukeLevel) {
                throw new FQException('当前爵位等级不足', 215);
            }
        }
        return true;
    }

    private function ensureCanSendFromBag($user, $giftKind)
    {
        if (empty($user->getUserModel()->username)) {
            throw new FQException('您还没有绑定手机号', 5100);
        }
        return true;
    }

    /**
     * @return: list<($giftKind, $count)>
     */
    private function randomGifts($giftKind, $count)
    {
        $giftItemMap = [];
        for ($i = 0; $i < $count; $i++) {
            $deliveryGiftKind = $giftKind->randomGift();
            $item = ArrayUtil::safeGet($giftItemMap, $deliveryGiftKind->kindId);
            if ($item == null) {
                $item = [$deliveryGiftKind, 0];
            }
            $giftItemMap[$deliveryGiftKind->kindId] = [$deliveryGiftKind, $item[1] + 1];
        }
        $ret = [];
        foreach ($giftItemMap as $giftId => $item) {
            $ret[] = $item;
        }
        return $ret;
    }

    private function buildSenderReceiverAssets($giftKind, $count, $deliveryGiftKind)
    {
        $senderAssets = null;
        $receiverAssets = null;
        if ($deliveryGiftKind->senderAssets != null) {
            $senderAssets = [];
            foreach ($deliveryGiftKind->senderAssets as $senderAsset) {
                $senderAssets[] = new AssetItem($senderAsset->assetId, $senderAsset->count * $count);
            }
        }
        if ($deliveryGiftKind->receiverAssets != null) {
            $receiverAssets = [];
            foreach ($deliveryGiftKind->receiverAssets as $receiverAsset) {
                $receiverAssets[] = new AssetItem($receiverAsset->assetId, $receiverAsset->count * $count);
            }
        }
        Log::debug(sprintf('GiftService::buildSenderReceiverAssets giftId=%d deliveryGiftId=%d count=%d senderAssets=%s receiverAssets=%s',
            $giftKind->kindId, $deliveryGiftKind->kindId, $count,
            $senderAssets != null ? json_encode($senderAssets) : null,
            $receiverAssets != null ? json_encode($receiverAssets) : null));
        return [$senderAssets, $receiverAssets];
    }

    /**
     * 从礼物背包送礼
     *
     * @return: SendGiftDetails
     */
    private function buildSendGiftDetailsByBag($giftKind, $count, $deliveryGiftKind)
    {
        $consumeAsset = new AssetItem(AssetUtils::makeGiftAssetId($giftKind->kindId), $count);
        list($senderAssets, $receiverAssets) = $this->buildSenderReceiverAssets($giftKind, $count, $deliveryGiftKind);
        return new GiftDetails($giftKind, $deliveryGiftKind, $consumeAsset, $senderAssets, $receiverAssets, $deliveryGiftKind->deliveryCharm, $count);
    }

    /**
     * 使用资产送礼
     *
     * @return: SendGiftDetails
     */
    private function buildSendGiftDetailsByPrice($giftKind, $count, $deliveryGiftKind)
    {
        $consumeAsset = $giftKind->price != null ? new AssetItem($giftKind->price->assetId, $count * $giftKind->price->count) : null;
        list($senderAssets, $receiverAssets) = $this->buildSenderReceiverAssets($giftKind, $count, $deliveryGiftKind);
        return new GiftDetails($giftKind, $deliveryGiftKind, $consumeAsset, $senderAssets, $receiverAssets, $deliveryGiftKind->deliveryCharm, $count);
    }

    /**
     * 计算送giftKind礼物需要的SendGiftDetails
     *
     * @return: list<SendGiftDetails>
     */
    private function buildSendGiftDetails($giftKind, $count, $bagBalance)
    {
        $ret = [];
        assert($count > 0 && $bagBalance >= 0);
        if ($giftKind->giftWeightList != null) {
            $items = $this->randomGifts($giftKind, $count);
            foreach ($items as $item) {
                assert($item[1] > 0);
                $consumeBagCount = min($item[1], $bagBalance);
                if ($consumeBagCount > 0) {
                    $bagBalance -= $consumeBagCount;
                    $ret[] = $this->buildSendGiftDetailsByBag($giftKind, $consumeBagCount, $item[0]);
                }
                $consumePriceCount = $item[1] - $consumeBagCount;
                if ($consumePriceCount > 0) {
                    $ret[] = $this->buildSendGiftDetailsByPrice($giftKind, $consumePriceCount, $item[0]);
                }
            }
        } else {
            // 送礼人消耗
            $consumeBagCount = min($count, $bagBalance);
            if ($consumeBagCount > 0) {
                $ret[] = $this->buildSendGiftDetailsByBag($giftKind, $consumeBagCount, $giftKind);
            }
            $consumePriceCount = $count - $consumeBagCount;
            if ($consumePriceCount > 0) {
                $ret[] = $this->buildSendGiftDetailsByPrice($giftKind, $consumePriceCount, $giftKind);
            }
        }
        return $ret;
    }

    /**
     * 计算送giftKind礼物需要的SendGiftDetails
     *
     * @return: list<SendGiftDetails>
     */
    private function buildSendMagicCubeDetails($giftKind, $count, $items)
    {
        $bagBalance = 0;
        $ret = [];
        assert($count > 0);
        if ($giftKind->giftWeightList != null) {
            foreach ($items as $item) {
                $item = current($item);
                assert($item[1] > 0);
                $consumeBagCount = min($item[1], $bagBalance);
                if ($consumeBagCount > 0) {
                    $bagBalance -= $consumeBagCount;
                    $ret[] = $this->buildSendGiftDetailsByBag($giftKind, $consumeBagCount, $item[0]);
                }
                $consumePriceCount = $item[1] - $consumeBagCount;
                if ($consumePriceCount > 0) {
                    $ret[] = $this->buildSendGiftDetailsByPrice($giftKind, $consumePriceCount, $item[0]);
                }
            }
        } else {
            // 送礼人消耗
            $consumeBagCount = min($count, 0);
            if ($consumeBagCount > 0) {
                $ret[] = $this->buildSendGiftDetailsByBag($giftKind, $consumeBagCount, $giftKind);
            }
            $consumePriceCount = $count - $consumeBagCount;
            if ($consumePriceCount > 0) {
                $ret[] = $this->buildSendGiftDetailsByPrice($giftKind, $consumePriceCount, $giftKind);
            }
        }
        return $ret;
    }

    /**
     * 送礼给指定用户
     */
    private function sendGiftToUser($roomId, $fromUser, $receiveUser, $giftKind, $count, $timestamp, $items = '')
    {
        if (!empty($items)) {
            $detailsList = $this->buildSendMagicCubeDetails($giftKind, $count, $items);
        } else {
            $detailsList = $this->buildSendGiftDetails($giftKind, $count, 0);
        }
        foreach ($detailsList as $details) {
            $price = 0;
            if (!is_null($details->deliveryGiftKind->price) && $details->deliveryGiftKind->price->assetId == AssetKindIds::$BEAN) {
                $price = $details->deliveryGiftKind->price->count * $details->count;
            }
            $biEvent = BIReport::getInstance()->makeSendGiftBIEvent($roomId, $receiveUser->userId, $details->giftKind->kindId, $details->deliveryGiftKind->kindId, $details->count, false, $price);
            if ($details->consumeAsset) {
                $fromUser->getAssets()->consume($details->consumeAsset->assetId, $details->consumeAsset->count, $timestamp, $biEvent);
            }
            if ($details->senderAssets) {
                foreach ($details->senderAssets as $sendAsset) {
                    $fromUser->getAssets()->add($sendAsset->assetId, $sendAsset->count, $timestamp, $biEvent);
                }
            }
        }

        event(new SendGiftDomainEvent($roomId, $fromUser, $receiveUser, $detailsList, false, $timestamp));
        return $detailsList;
    }

    /**
     * 送礼给指定用户
     */
    private function sendGiftToUserFromBag($roomId, $fromUser, $receiveUser, $giftKind, $count, $timestamp)
    {
        $giftBag = $fromUser->getAssets()->getGiftBag($timestamp);
        $bagBalance = $giftBag->balance($giftKind->kindId, $timestamp);
        $detailsList = $this->buildSendGiftDetails($giftKind, $count, $bagBalance);

        foreach ($detailsList as $details) {
            $price = 0;
            if (!is_null($details->deliveryGiftKind->price) && $details->deliveryGiftKind->price->assetId == AssetKindIds::$BEAN) {
                $price = $details->deliveryGiftKind->price->count * $details->count;
            }
            $biEvent = BIReport::getInstance()->makeSendGiftBIEvent($roomId, $receiveUser->userId, $details->giftKind->kindId, $details->deliveryGiftKind->kindId, $details->count, true, $price);
            if ($details->consumeAsset) {
                $fromUser->getAssets()->consume($details->consumeAsset->assetId, $details->consumeAsset->count, $timestamp, $biEvent);
            }
            if ($details->senderAssets) {
                foreach ($details->senderAssets as $sendAsset) {
                    $fromUser->getAssets()->add($sendAsset->assetId, $sendAsset->count, $timestamp, $biEvent);
                }
            }
        }
        event(new SendGiftDomainEvent($roomId, $fromUser, $receiveUser, $detailsList, true, $timestamp));
        return $detailsList;
    }

    /**
     * 收礼
     *
     * @param $roomId
     * @param $receiveUser
     * @param $fromUserId
     * @param $detailsList
     * @param $timestamp
     * @param $fromBag
     * @throws FQException
     */
    private function receiveGiftImpl($roomId, $receiveUser, $fromUserId, $detailsList, $timestamp, $fromBag ,$receiverUserDiamondBalance)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $receiveUser->userId)->transaction(function() use(
                $roomId, $receiveUser, $fromUserId, $detailsList, $timestamp, $fromBag ,$receiverUserDiamondBalance
            ) {
                // 收礼人
                $user = $this->ensureUserExists($receiveUser->userId);

                foreach ($detailsList as $details) {
                    if ($details->receiverAssets) {
                        $price = 0;
                        if (!is_null($details->deliveryGiftKind->price) && $details->deliveryGiftKind->price->assetId == AssetKindIds::$BEAN) {
                            $price = $details->deliveryGiftKind->price->count * $details->count;
                        }
                        // 发货
                        $biEvent = BIReport::getInstance()->makeReceiveGiftBIEvent($roomId, $fromUserId, $details->giftKind->kindId, $details->deliveryGiftKind->kindId, $details->count, $fromBag, $price);
                        foreach ($details->receiverAssets as $receiverAsset) {
                            $receiverUserDiamondBalance[$receiveUser->userId] = $user->getAssets()->add($receiverAsset->assetId, $receiverAsset->count, $timestamp, $biEvent);
                        }
                    }
                }
                event(new ReceiveGiftDomainEvent($roomId, $user, $fromUserId, $receiveUser, $detailsList, $fromBag, $timestamp));
                return [$user,$receiverUserDiamondBalance];
            });
        } catch (Exception $e) {
            Log::error(sprintf('ReceiveGiftException roomId=%d userId=%d sendUserId=%d %d ex=%d:%s',
                $roomId, $receiveUser->userId,
                $fromUserId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * M豆直送礼物实现
     * @param $roomId
     * @param $fromUserId
     * @param $receiveUsers
     * @param $giftKind
     * @param $count
     * @param $timestamp
     * @return array
     * @throws Exception
     */
    private function sendGiftImpl($roomId, $fromUserId, $receiveUsers,GiftKind $giftKind, $count, $timestamp)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $fromUserId)->transaction(function() use(
                $roomId, $fromUserId, $receiveUsers, $giftKind, $count, $timestamp) {
                $sendDetails = [];
                // 送礼人
                $fromUser = $this->ensureUserExists($fromUserId);

                $this->ensureCanSend($fromUser, $giftKind);

                $totalCost = $giftKind->price->count * $count * count($receiveUsers);

                // 判断送礼人的余额
                $fromUserBeanBalance = $fromUser->getAssets()->balance($giftKind->price->assetId, $timestamp);
                if ($fromUserBeanBalance < $totalCost) {
                    throw new AssetNotEnoughException('余额不足', 500);
                }
                $superAssets = [];
                if($giftKind->superRewardList) {
                    $clientParams = $giftKind->clientParams;
                    if (!empty($clientParams)) {
                        if (ArrayUtil::safeGet($clientParams, 'sendLimit')) {
                            if ($count > ArrayUtil::safeGet($clientParams, 'sendLimit')) {
                                throw new FQException('单次单人只能赠送一个');
                            }
                        }
                    }

                    $item = $this->randomGifts($giftKind, 1);
                    $items = [];
                    for ($i = 0; $i< $count; $i++) {
                        $items[] = $item;
                    }
                    foreach ($receiveUsers as $receiveUser) {
                        $detailsList = $this->sendGiftToUser($roomId, $fromUser, $receiveUser, $giftKind, $count, $timestamp, $items);
                        $sendDetails[] = [$receiveUser, $detailsList];
                    }
                    //处理是否产出超级奖励
                    $superAssets = $this->produceSuperReward($giftKind, $fromUser, $receiveUsers, $roomId, $count, $timestamp);
                } else {
                    // 给所有玩家送礼
                    foreach ($receiveUsers as $receiveUser) {
                        $detailsList = $this->sendGiftToUser($roomId, $fromUser, $receiveUser, $giftKind, $count, $timestamp);
                        $sendDetails[] = [$receiveUser, $detailsList];
                    }
                }
                return [$sendDetails, $superAssets ,$fromUserBeanBalance];
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('SendGiftException roomId=%d userId=%d %d ex=%d:%s',
                    $roomId, $fromUserId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('SendGiftException roomId=%d userId=%d %d ex=%d:%s trace=%s',
                    $roomId, $fromUserId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    public function produceSuperReward($giftKind, $fromUser, $receiveUsers, $roomId, $count, $timestamp) {
        $superAssets = [];
        if(count($receiveUsers) >= $giftKind->superRewardRule->limit) {
            for ($i = 0; $i < $count; $i++) {
                $randNum = rand(1, $giftKind->superRewardRule->randValues);
                if ($randNum == 1) {
                    foreach ($giftKind->superRewardList as $receiverAsset) {
                        if (array_key_exists($receiverAsset->assetId, $superAssets)) {
                            $superAssets[$receiverAsset->assetId]['count'] = $superAssets[$receiverAsset->assetId]['count'] + 1;
                        } else {
                            $superAssets[$receiverAsset->assetId]['assetItem'] = new AssetItem($receiverAsset->assetId, $receiverAsset->count);
                            $superAssets[$receiverAsset->assetId]['count'] = 1;
                        }
                    }
                }
            }
        }
        if (!empty($superAssets)) {
            foreach ($superAssets as $superAssetInfo) {
                $superCount = $superAssetInfo['count'];
                $assetItem = $superAssetInfo['assetItem'];
                $biEvent = BIReport::getInstance()->makeGiftSuperRewardBIEvent($roomId, $giftKind->kindId, $count, count($receiveUsers));
                $fromUser->getAssets()->add($assetItem->assetId, $assetItem->count * $superCount, $timestamp, $biEvent);
            }
        }
        return $superAssets;
    }

    /**
     * 背包送礼实现
     * @param $roomId
     * @param $fromUserId
     * @param $receiveUsers
     * @param $giftKind
     * @param $count
     * @param $timestamp
     * @return array
     * @throws Exception
     */
    private function sendGiftFromBagImpl($roomId, $fromUserId, $receiveUsers, $giftKind, $count, $timestamp, $skip)
    {

        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $fromUserId)->transaction(function() use(
                $roomId, $fromUserId, $receiveUsers, $giftKind, $count, $timestamp, $skip) {
                $sendDetails = [];
                // 送礼人
                $fromUser = $this->ensureUserExists($fromUserId);

                $this->ensureCanSendFromBag($fromUser, $giftKind);

                $fromUserAssets = $fromUser->getAssets();
                $fromUserGiftBag = $fromUserAssets->getGiftBag($timestamp);
                $bagBalance = $fromUserGiftBag->balance($giftKind->kindId, $timestamp);

                $totalCount = $count * count($receiveUsers);

                if ($giftKind->isAllMicGift()){
                    $redis = RedisCommon::getInstance()->getRedis();
                    $micUser = $redis->zRange('mic_online_users_'.$roomId, 0, -1);
                    $micCount = in_array($fromUserId, $micUser) ? count($micUser)-1:count($micUser);
                    if ($micCount != count($receiveUsers)) {
                        Log::warning(sprintf('SendGiftFromBagAllMicException roomId=%d userId=%d giftId=%d userCount=%s micUser=%s',
                            $roomId, $fromUserId, $giftKind->kindId, json_encode($receiveUsers), json_encode($micUser)));
                        throw new FQException('此礼物为全麦礼物', 500);
                    }

                    if ($bagBalance < $count) {
                        throw new FQException('背包礼物不足', 210);
                    }

                    # 不够系统补
                    $addCount = $totalCount-$count;
                    if ($addCount > 0){
                        $price = 0;
                        if (!is_null($giftKind->price) && $giftKind->price->assetId == AssetKindIds::$BEAN) {
                            $price = $giftKind->price->count * $addCount;
                        }

                        $biEvent = BIReport::getInstance()->makeSystemSendGiftBIEvent($roomId, $fromUserId, $giftKind->kindId, $giftKind->kindId, $addCount, true, $price);
                        $assetId = AssetUtils::makeGiftAssetId($giftKind->kindId);
                        $fromUser->getAssets()->add($assetId, $addCount, $timestamp, $biEvent);
                    }
                }elseif ($bagBalance < $totalCount) {
                    //逻辑调整，背包数量不足直接报错，没有余额抵扣逻辑了
                    throw new FQException('背包礼物不足', 210);
                }
                // 给所有玩家送礼
                foreach ($receiveUsers as $receiveUser) {
                    $detailsList = $this->sendGiftToUserFromBag($roomId, $fromUser, $receiveUser, $giftKind, $count, $timestamp);
                    $sendDetails[] = [$receiveUser, $detailsList];
                }
                return $sendDetails;
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('SendGiftFromBagException roomId=%d userId=%d giftId=%d ex=%d:%s',
                    $roomId, $fromUserId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('SendGiftFromBagException roomId=%d userId=%d giftId=%d ex=%d:%s trace=%s',
                    $roomId, $fromUserId, $giftKind->kindId,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    public function buildBoxIntroduction($giftKind)
    {
        if ($giftKind->boxIntroduction == null) {
            return null;
        }
        $data['ruleInfo'] = $giftKind->boxIntroduction['ruleInfo'];
        $data['specialInfo'] = $giftKind->boxIntroduction['specialInfo'];
        $boxBackgrounds = [];
        foreach ($giftKind->boxIntroduction['boxBackgrounds'] as $key => $image){
            $boxBackgrounds[$key] = CommonUtil::buildImageUrl($image);
        }
        $data['backgrounds'] = empty($boxBackgrounds) ? null : $boxBackgrounds;
        $data['boxGiftList'] = $this->buildBoxWeightList($giftKind->giftWeightList, $giftKind->totalWeight);
        return $data;
    }

    public function buildBoxWeightList($giftWeightList, $totalWeight)
    {
        $list = [];
        if (!empty($giftWeightList)) {
            foreach ($giftWeightList as $value) {
                $giftList['giftId'] = $value[0]->kindId;
                $giftList['giftName'] = $value[0]->name;
                $giftList['giftImage'] = CommonUtil::buildImageUrl($value[0]->image);
                $giftList['giftValue'] = $value[0]->price->count;
                $giftList['baolv'] = strval(round(floatval($value[2]) / floatval($totalWeight)*100, 2));
                $list[] = $giftList;
            }
            $valueList = array_column($list, 'giftValue');
            array_multisort($valueList, SORT_DESC, $list);
        }
        return $list;
    }

    /**
     * @desc 获取动画礼物地址mp4列表
     * @return array
     */
    public function giftMp4AnimationList(): array
    {
        $giftKindMap = GiftSystem::getInstance()->getKindMap();
        $giftMp4AnimationList = [];
        foreach ($giftKindMap as $giftKind) {
            // giftMp4Animation 属性不为空
            if ($giftKind->giftMp4Animation) {
                $giftMp4Animation = [];
                $giftMp4Animation['kind_id'] = $giftKind->kindId;
                $giftMp4Animation['gift_mp4_animation'] = CommonUtil::buildImageUrl($giftKind->giftMp4Animation);
                $giftMp4AnimationList[] = $giftMp4Animation;
            }
        }

        return $giftMp4AnimationList;
    }
}


