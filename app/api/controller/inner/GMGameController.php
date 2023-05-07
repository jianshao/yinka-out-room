<?php


namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\game\GameService;
use app\domain\gift\GiftSystem;
use app\domain\led\LedService;
use app\domain\mall\MallIds;
use app\domain\mall\service\MallService;
use app\domain\prop\PropKindBubble;
use app\query\prop\service\PropQueryService;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\query\user\cache\UserModelCache;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use app\domain\exceptions\FQException;

class GMGameController extends Base2Controller
{
    public function buyScore() {
        $userId = intval($this->request->param('userId'));
        $count = intval($this->request->param('count'));
        $roomId = intval($this->request->param('roomId'));
        $autoBuy = $this->request->param('autoBuy');
        $from = $this->request->param('from');
        $timestamp = intval($this->request->param('timestamp'));

        if ($count <= 0) {
            throw new FQException('数量错误', 500);
        }

        $goods = GameService::getInstance()->getGoods();
        if ($autoBuy){
            #如果是自动购买，计算需要购买商品数
            $countPerGoods = $goods->deliveryAsset->count;
            $goodsCount = intval(($count + ($countPerGoods - 1)) / $countPerGoods);
        }else{
            $goodsCount = $count;
        }

        $balance = MallService::getInstance()->buyGoodsByGoods($userId, $goods, $goodsCount, MallIds::$GAME, $from, $roomId);

        return $balance;
    }

    public function getAsset() {
        $userId = intval(Request::param('userId'));
        $assetId = Request::param('assetId');
        $timestamp = Request::param('timestamp');

        return AssetUtils::getAsset($userId, $assetId, $timestamp);
    }

    public function addAsset() {
        $userId = intval(Request::param('userId'));
        $assetId = Request::param('assetId');
        $count = (int)Request::param('count');
        $timestamp = Request::param('timestamp');
        $eventDict = Request::param('eventDict');

        $eventDict = json_decode($eventDict, true);
        $roomId = ArrayUtil::safeGet($eventDict, 'roomId', 0);
        $activityType = ArrayUtil::safeGet($eventDict, 'activityType', 'activity');
        $ext2=ArrayUtil::safeGet($eventDict, 'ext2', '');
        $ext3=ArrayUtil::safeGet($eventDict, 'ext3', '');
        $ext4=ArrayUtil::safeGet($eventDict, 'ext4', '');
        $ext5=ArrayUtil::safeGet($eventDict, 'ext5', '');

        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $ext2, $ext3, $ext4, $ext5);
        $ret = AssetUtils::addAsset($userId, $assetId, $count, $timestamp, $biEvent);
        return json_encode($ret);

    }

    public function addAssets() {
        $userId = intval(Request::param('userId'));
        $assets = Request::param('assets');
        $timestamp = Request::param('timestamp');
        $eventDict = Request::param('eventDict');

        $assets = json_decode($assets, true);
        $eventDict = json_decode($eventDict, true);
        $roomId = ArrayUtil::safeGet($eventDict, 'roomId', 0);
        $activityType = ArrayUtil::safeGet($eventDict, 'activityType', 'activity');
        $ext2=ArrayUtil::safeGet($eventDict, 'ext2', '');
        $ext3=ArrayUtil::safeGet($eventDict, 'ext3', '');
        $ext4=ArrayUtil::safeGet($eventDict, 'ext4', '');
        $ext5=ArrayUtil::safeGet($eventDict, 'ext5', '');

        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $ext2, $ext3, $ext4, $ext5);

        $assetList = [];
        foreach ($assets as $assetId => $count){
            $assetList[] = [$assetId, $count, $biEvent];
        }

        AssetUtils::addAssets($userId, $assetList, $timestamp);
    }

    public function consumeAsset() {
        try {
            $userId = intval(Request::param('userId'));
            $assetId = Request::param('assetId');
            $count = (int)Request::param('count');
            $timestamp = Request::param('timestamp');
            $eventDict = Request::param('eventDict');
            $eventDict = json_decode($eventDict, true);
            $roomId = ArrayUtil::safeGet($eventDict, 'roomId', 0);
            $activityType = ArrayUtil::safeGet($eventDict, 'activityType', 'activity');
            $ext2=ArrayUtil::safeGet($eventDict, 'ext2', '');
            $ext3=ArrayUtil::safeGet($eventDict, 'ext3', '');
            $ext4=ArrayUtil::safeGet($eventDict, 'ext4', '');
            $ext5=ArrayUtil::safeGet($eventDict, 'ext5', '');

            $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $ext2, $ext3, $ext4, $ext5);
            $ret = AssetUtils::consumeAsset($userId, $assetId, $count, $timestamp, $biEvent);
            return json_encode($ret);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::consumeAsset userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function consumeAssets() {
        try {
            $userId = intval(Request::param('userId'));
            $assets = Request::param('assets');
            $timestamp = Request::param('timestamp');
            $eventDict = Request::param('eventDict');

            $assets = json_decode($assets, true);
            $eventDict = json_decode($eventDict, true);
            $roomId = ArrayUtil::safeGet($eventDict, 'roomId', 0);
            $activityType = ArrayUtil::safeGet($eventDict, 'activityType', 'activity');
            $ext2=ArrayUtil::safeGet($eventDict, 'ext2', '');
            $ext3=ArrayUtil::safeGet($eventDict, 'ext3', '');
            $ext4=ArrayUtil::safeGet($eventDict, 'ext4', '');
            $ext5=ArrayUtil::safeGet($eventDict, 'ext5', '');
            $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $ext2, $ext3, $ext4, $ext5);

            $assetList = [];
            foreach ($assets as $assetId => $count){
                $assetList[] = [$assetId, $count, $biEvent];
            }
            AssetUtils::consumeAssets($userId, $assetList, $timestamp);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::consumeAsset userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function sendGopherKingLed() {
        try {
            $status = intval(Request::param('status'));
            $strFull = [
                'msgId'=>2071,
                'actionStr' => '地鼠王出现啦，全军出击！ 剿灭地鼠王！',
                'actionType' => 'gopher',
            ];
            $msgFullScreen['msg'] = json_encode($strFull);
            $msgFullScreen['roomId'] = 0;
            $msgFullScreen['toUserId'] = '0';
            RoomNotifyService::getInstance()->notifyRoomMsg(0, $msgFullScreen);

            LedService::getInstance()->sendGopherKingLedMsg($status);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendGopherKingLed status=%d ex=%d:%s file=%s:%d',
                $status, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function sendKOGopherKingLed() {
        try {
            $userId = intval(Request::param('userId'));
            $roomId = intval(Request::param('roomId'));
            $reward = Request::param('reward');
            $status = Request::param('status');

            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            $actionStr = "恭喜".$userModel->nickname."剿灭地鼠王，获得".$reward."积分";
            $strFull = [
                'msgId'=>2071,
                'userId' => $userId,
                'actionStr' => $actionStr,
                'actionType' => 'gopher',
            ];
            $msgFullScreen['msg'] = json_encode($strFull);
            $msgFullScreen['roomId'] = 0;
            $msgFullScreen['toUserId'] = '0';
            RoomNotifyService::getInstance()->notifyRoomMsg(0, $msgFullScreen);


            LedService::getInstance()->sendKOGopherKingLedMsg($userId, $roomId, $reward, $status);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendKOGopherKingLed userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }

    public function sendGopherPublicScreen() {
        try {
            $roomId = intval(Request::param('roomId'));
            $userId = intval(Request::param('userId'));
            $score = intval(Request::param('score'));

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($roomId, $userId);

            $roomName = RoomModelDao::getInstance()->getRoomName($roomId);
            $userInfo = UserModelDao::getInstance()->loadUserModel($userId);
            $bubble = PropQueryService::getInstance()->getWaredProp($userId, PropKindBubble::$TYPE_NAME);
            $socketFullScreenMsg[] = [
                'userIdentity' => $userIdentity,
                'userId' => $userId,
                'prettyId' => $userInfo->prettyId,
                'userLevel' => $userInfo->lvDengji,
                'nickName' => $userInfo->nickname,
                'roomName' => $roomName,
                'showType' => 1,
                'giftId' => 0,
                'giftName' => "积分",
                'giftUrl' => CommonUtil::buildImageUrl("resource/images/jifen2.png"),
                'count' => $score,
                'isVip' => $userInfo->vipLevel,
                'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                '_full' => '1',
            ];
            $strFull = [
                'msgId'=>2070,
                'actionName' => '参与打地鼠获得积分',
                'actionType' => 'gopher',
                'items'=>$socketFullScreenMsg
            ];
            $msgFullScreen['msg'] = json_encode($strFull);
            $msgFullScreen['roomId'] = 0;
            $msgFullScreen['toUserId'] = '0';
            RoomNotifyService::getInstance()->notifyRoomMsg($roomId, $msgFullScreen);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendGopherPublicScreen userId=%d score=%d roomId=%d ex=%d:%s file=%s:%d',
                $userId, $score, $roomId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function sendPublicScreen() {
        try {
            $roomId = intval(Request::param('roomId'));
            $userId = intval(Request::param('userId'));
            $actionName = Request::param('actionName');
            $actionType = Request::param('actionType');
            $results = Request::param('results');
            $results = json_decode($results, true);

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($roomId, $userId);

            $roomName = RoomModelDao::getInstance()->getRoomName($roomId);
            $userInfo = UserModelDao::getInstance()->loadUserModel($userId);
            $bubble = PropQueryService::getInstance()->getWaredProp($userId, PropKindBubble::$TYPE_NAME);
            foreach ($results as $key => $info){
                $socketFullScreenMsg[] = [
                    'userIdentity' => $userIdentity,
                    'userId' => $userId,
                    'prettyId' => $userInfo->prettyId,
                    'userLevel' => $userInfo->lvDengji,
                    'nickName' => $userInfo->nickname,
                    'roomName' => $roomName,
                    'showType' => 1,
                    'giftId' => $info['giftId'],
                    'giftName' => $info['giftName'],
                    'giftUrl' => CommonUtil::buildImageUrl($info['giftImage']),
                    'count' => $info['count'],
                    'isVip' => $userInfo->vipLevel,
                    'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                    'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                    '_full' => '1',
                ];
            }

            if (empty($socketFullScreenMsg)){
                return;
            }

            $strFull = [
                'msgId'=>2070,
                'actionName' => $actionName,
                'actionType' => $actionType,
                'items'=>$socketFullScreenMsg
            ];
            $msgFullScreen['msg'] = json_encode($strFull);
            $msgFullScreen['roomId'] = 0;
            $msgFullScreen['toUserId'] = '0';
            RoomNotifyService::getInstance()->notifyRoomMsg($roomId, $msgFullScreen);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendPublicScreen userId=%d roomId=%d ex=%d:%s file=%s:%d',
                $userId, $roomId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function sendCommonLedMsg() {
        try {
            $userId = intval(Request::param('userId'));
            $gameName = Request::param('gameName');
            $gameType = Request::param('gameType');
            $results = Request::param('results');
            $results = json_decode($results, true);

            LedService::getInstance()->sendCommonLedMsg($userId, $gameName, $gameType, $results);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendCommonLedMsg userId=%d, status=%s ex=%d:%s file=%s:%d',
                $userId, $gameName, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public function getGiftsInfo() {
        $giftIds = Request::param('giftIds');
        $giftIds = json_decode($giftIds, true);

        $giftMap = [];
        foreach ($giftIds as $giftId) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if (!$giftKind){
                Log::error(sprintf('getGiftsInfo giftId=%d', $giftId));
                continue;
//                throw new FQException('配置错误，礼物不存在', 500);
            }

            $giftInfo = [
                'kindId' => $giftKind->kindId,
                'name' => $giftKind->name,
                'image' => $giftKind->image,
                'animation' => $giftKind->animation,
                'giftAnimation' => $giftKind->giftAnimation,
                'price' => $giftKind->price ? $giftKind->price->count : 0,
            ];
            $giftMap[$giftId] = $giftInfo;
        }

        Log::info(sprintf('GMGameController::getGiftsInfo ok giftMap=%s', json_encode($giftMap)));

        return rjson($giftMap);
    }

    public function sendAssistantMsg() {
        try {
            $userId = intval(Request::param('userId'));
            $msg = Request::param('msg');
            YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
        }catch (Exception $e) {
            Log::error(sprintf('GMGameController::sendAssistantMsg userId=%d msg=%s ex=%d:%s file=%s:%d',
                $userId, $msg, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}