<?php
namespace app\api\controller\v1;

use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\dao\MonitoringModelDao;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\RoomRepository;
use app\domain\user\service\UnderAgeService;
use app\form\ReceiveUser;
use app\service\CharmService;
use app\domain\gift\service\GiftService;
use app\utils\CommonUtil;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class SendGiftController extends ApiBaseController
{
    //清空魅力
    public function delCharm()
    {
        $micIdsStr = Request::param('mic');
        $roomId = intval(Request::param('room_id'));
        $userId = (int)$this->headUid;

        if (empty($micIdsStr) || empty($roomId)) {
            return rjson([],500,'清空失败');
        }

        $room = RoomRepository::getInstance()->loadRoom($roomId);
        if ($room == null) {
            throw new FQException('此房间不存在', 500);
        }

        //判断用户是否为管理员
        $manager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
        if ($userId != $room->getModel()->userId && $manager == null) {
            throw new FQException('该用户权限不足无法操作', 500);
        }

        $micIds = [];
        foreach (explode(',', $micIdsStr) as $micIdStr) {
            $micIds[] = intval($micIdStr);
        }

        try {
            CharmService::getInstance()->clearCharm($roomId, $micIds);
            return rjson();
        } catch (FQException $e) {
            Log::error(sprintf('delCharm roomId=%d micIds=%s',
                $roomId, $micIdsStr));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 送礼
     * @param string $uid
     * @param string $toUids
     * @param string $num
     * @param string $roomId
     * @param string $mic
     * @param string $ispack
     */
    public function sendRoomGift()
    {
        $skip = Request::param('skip');
        $count = intval(Request::param('num'));
        $roomId = intval(Request::param('roomid') ? : 0);
        $giftId = intval(Request::param('giftid'));
        $toMicIdsStr = Request::param('mic');
        $toUserIdsStr = Request::param('touid');
        $isPack = Request::param('ispack');

        $userId = intval($this->headUid);
        $micIds = explode(',', $toMicIdsStr);
        $toUserIds = explode(',', $toUserIdsStr);

        if ($count <= 0) {
            return rjson([], 500, '请输入正确的礼物数量');
        }

        if ($roomId <= 0) {
            return rjson([], 500, '暂不支持房间外送礼');
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
        if ($giftKind == null) {
            return rjson([],500,'礼物不存在');
        }

        $monitoringModel = MonitoringModelDao::getInstance()->findByUserId($userId);
        if ($monitoringModel != null) {
            return rjson([],500,'青少年模式已开启');
        }

        $data = [];
        try {
            $receiveUsers = ReceiveUser::fromUserMicIdArray($toUserIds, $micIds, $userId);
            if($isPack == 1) {
                GiftService::getInstance()->sendGiftFromBag($roomId, $userId, $receiveUsers, $giftKind, $count, $skip);
            } else {
                list($sendDetails, $receiveDetails, $superAssets) = GiftService::getInstance()->sendGift($roomId, $userId, $receiveUsers, $giftKind, $count);
                if (!empty($superAssets)) {
                    $gift = [];
                    foreach ($superAssets as $superAssetInfo) {
                        $count = $superAssetInfo['count'];
                        $assetItem = $superAssetInfo['assetItem'];
                        if (AssetUtils::isGiftAsset($assetItem->assetId)) {
                            $giftKindId = AssetUtils::getGiftKindIdFromAssetId($assetItem->assetId);
                            $giftInfo = GiftSystem::getInstance()->findGiftKind($giftKindId);
                            $gift['giftName'] = $giftInfo->name;
                            $gift['giftImage'] = CommonUtil::buildImageUrl($giftInfo->image);
                            $gift['giftCount'] = $count;
                            $data['rewardList'][] = $gift;
                        }
                    }
                    $data['type'] = 'superReward';
                }
            }
            return rjson($data);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 打开背包礼物
     * @param int $giftId
     * @param string $roomId
     * @param int $count
     */
    public function openBagGift()
    {
        $userId = intval($this->headUid);
        $giftId = (int)Request::param('giftId');
        $roomId = intval(Request::param('roomId') ? : 0);
        $count = intval(Request::param('count') ? : 1);
        if (!$giftId) {
            return rjson([], 500, '参数错误');
        }
        try {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if($giftKind == null){
                return rjson([], 500, '礼物ID错误');
            }

            if(!$giftKind->canOpenFromBag()){
                return rjson([], 500, '礼物不能打开');
            }

            list($balance, $gainAssets) = GiftService::getInstance()->openGiftFromBag($roomId, $userId, $giftKind, $count);

            $gains = [];
            foreach ($gainAssets as $assetItem){
                $assetKind = AssetSystem::getInstance()->findAssetKind($assetItem->assetId);
                $gains[] = [
                    'name' => $assetKind->displayName,
                    'count' => $assetItem->count,
                ];
            }

            $ret = [
                'giftId' => $giftId,
                'balance' => $balance,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'gains' => $gains
            ];

            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }
}