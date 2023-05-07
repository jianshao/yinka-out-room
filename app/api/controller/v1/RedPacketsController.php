<?php
/**
 * 红包
 * yond
 *
 */

namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\pay\ProductAreaNames;
use app\domain\pay\ProductShelvesNames;
use app\domain\pay\ProductSystem;
use app\domain\redpacket\RedPacketService;
use app\domain\redpacket\RedPacketSystem;
use app\domain\user\service\UnderAgeService;
use app\query\user\cache\UserModelCache;
use app\query\user\service\AttentionService;
use app\service\LockService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use think\facade\Log;


class RedPacketsController extends ApiBaseController
{
    //红包初始化
    public function redPacketsInit()
    {
        try {
            $userId = (int)$this->headUid;
            $coinIos = [];
            $coinIosPlice = [];
            $iosRedPackets = RedPacketSystem::getInstance()->getArea('ios');
            foreach ($iosRedPackets as $redPacket) {
                $product = ProductSystem::getInstance()->findProduct($redPacket->productId);
                if ($product == null) {
                    Log::warning(sprintf('RedPacketsController::redPacketsInit NotFoundProduct productId=%d', $redPacket->productId));
                    throw new FQException('红包配置错误', 500);
                }
                $coinIos[] = $product->appStoreProductId;
                $coinIosPlice[] = $redPacket->value;
            }


            $coinAndroid = [];
            $androidRedPackets = RedPacketSystem::getInstance()->getArea('android');
            foreach ($androidRedPackets as $redPacket) {
                $coinAndroid[] = $redPacket->value;
            }
            $shelvesNames = ProductShelvesNames::$RED_PACKET;
            $iosShelves = ProductSystem::getInstance()->getShelves(ProductAreaNames::$IOS, $shelvesNames);
            $redPackProductIds = array_keys($iosShelves->productMap);
            $result = [
                'describe' => [
                    ['title' => '1、直播间发红包的好处', 'content' => '有红包的房间会优先展示在首页，吸引更多用户进入。'],
                    ['title' => '2、谁能抢红包', 'content' => '红包未抢光时，在房间内的所有用户都可以参与抢红包。'],
                    ['title' => '3、没抢光的红包怎么办', 'content' => '红包需被全部抢光后才会消失。'],
                    ['title' => '4、红包的可塞入的钱数，最少300音豆，最多10000音豆。', 'content' => ''],
                    ['title' => '5、单个红包可抢个数，最少3个最多100个', 'content' => ''],
                ],
                'coin_ios' => $coinIos,
                'coin_android' => $coinAndroid,
                'time' => RedPacketSystem::getInstance()->getTimes(),
                'coin_ios_plice' => $coinIosPlice,
                'coin_ios_productId' => $redPackProductIds,
            ];

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function makePayResult($payResult, $payChannel)
    {
        if ($payChannel == 1) {
            return ['appPayRequest' => $payResult];
        } elseif ($payChannel == 3) {
            return $payResult;
        }
        return [];
    }

    //发红包
    public function sendPackets()
    {
        $roomId = Request::param('room_id');
        $time = Request::param('time');
        $coin = Request::param('coin');
        $num = Request::param('num');
        $type = Request::param('type'); //1支付宝2微信

        if (empty($roomId) || empty($num) || empty($type)) {
            return rjson([], 500, '发红包数据错误,请重试');
        }

        $type = intval($type);
        $roomId = intval($roomId);
        $userId = intval($this->headUid);
        $totalBean = intval($coin);
        $count = intval($num);

        if (!in_array($type, [1, 2])) {
            return rjson([], 500, '支付通道错误,请重试');
        }
        $payChannel = $type == 1 ? 1 : 3;

        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }

        $lockKey = 'room_lock' . $roomId;
        try {
            LockService::getInstance()->lock($lockKey);
        } catch (FQException $e) {
            return rjson([], 500, '房间加锁不能发红包');
        }

        try {
            list($payResult, $orderId) = RedPacketService::getInstance()->makeAndBuyRedPacket($userId, $roomId, $type, $payChannel, $time, $totalBean, $count, $this->config);
            return rjson($this->makePayResult($payResult, $payChannel));
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    //领红包
    public function getRedPackets()
    {
        $redId = Request::param('red_id');
        $roomId = Request::param('room_id');
        if (empty($roomId) || empty($redId)) {
            return rjson([], 500, '查看红包不存在,请重试');
        }

        $redId = intval($redId);
        $roomId = intval($roomId);
        $userId = intval($this->headUid);

        try {
            $beanCount = RedPacketService::getInstance()->grabRedPacket($userId, $redId);

            return rjson([
                'count' => 0,
                'get_coin' => $beanCount
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //红包个数
    public function PacketsNum()
    {
        $roomId = Request::param('room_id');
        if (empty($roomId)) {
            return rjson([], 500, '查看红包不存在,请重试');
        }

        $roomId = intval($roomId);
        $userId = intval($this->headUid);
        try {
            list($count, $end) = \app\query\redpacket\service\RedPacketService::getInstance()->getRoomRedPacketCount($userId, $roomId);
            return rjson([
                'count' => $count,
                'time' => $end
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function encodeGetDetail($getDetail)
    {
        return [
            'get_time' => TimeUtil::timeToStr($getDetail->getTime),
            'avatar' => CommonUtil::buildImageUrl($getDetail->getUserAvatar),
            'nickname' => $getDetail->getUserNickname,
            'get_uid' => $getDetail->getUserId,
            'get_coin' => $getDetail->beanCount,
            'is_get' => $getDetail->isGet,
            'created_time' => $getDetail->createTime,
            'updated_time' => $getDetail->updateTime,
        ];
    }

    //红包详情
    public function PacketsDetail()
    {
        $redPacketId = Request::param('red_id');
        if (empty($redPacketId)) {
            return rjson([], 500, '查看红包错误,请重试');
        }

        $redPacketId = intval($redPacketId);
        try {
            $redPacketInfo = \app\query\redpacket\service\RedPacketService::getInstance()->getRedPacketDetailInfo($redPacketId);
            if ($redPacketInfo == null) {
                return rjson();
            }
            $getDetailList = [];
            foreach ($redPacketInfo->getDetailList as $getDetail) {
                $getDetailList[] = $this->encodeGetDetail($getDetail);
            }
            return rjson([
                'count' => $redPacketInfo->count,
                'getNum' => $redPacketInfo->getCount,
                'list' => $getDetailList
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function viewRedPacketInfo($userId, $redPacketInfo)
    {
        $countdown = $redPacketInfo->sendTime + $redPacketInfo->countdownTime - time();
        $countdown = max($countdown, 0);
        $isFocus = $userId == $redPacketInfo->sendUserId || AttentionService::getInstance()->isFocus($userId, $redPacketInfo->sendUserId);
        $userInfo = UserModelCache::getInstance()->getUserInfo($redPacketInfo->sendUserId);
        $nickname = '';
        $avatar = '';
        if (!empty($userInfo)) {
            $nickname = $userInfo->nickname;
            $avatar = $userInfo->avatar;
        }

        if ($redPacketInfo->isGet != 0) {
            $status = 3; //已领取
        } else {
            if ($redPacketInfo->remCount == 0) {
                $status = 4; //已领完
            } else {
                if ($countdown > 0) {
                    $status = 1;
                } else {
                    $status = 2;
                }
            }
        }

        return [
            'red_id' => $redPacketInfo->id,
            'count_down' => $countdown,
            'status' => $status,
            'send_uid' => $redPacketInfo->sendUserId,
            'avatar' => CommonUtil::buildImageUrl($avatar),
            'is_atten' => $isFocus ? 1 : 0,
            'red_countcoin' => $redPacketInfo->totalBean,
            'nickname' => $nickname,
            'backgroundImage' => CommonUtil::buildImageUrl('/image/open_bg%402x.png'),
            'openIvImage' => CommonUtil::buildImageUrl('/image/normalred.png')
        ];
    }

    //红包预览
    public function packetsNew()
    {
        $roomId = Request::param('room_id');
        $redPacketId = Request::param('red_id');
        if (empty($roomId)) {
            return rjson([], 500, '查看红包错误,请重试');
        }

        $userId = intval($this->headUid);
        $roomId = intval($roomId);
        $redPacketId = intval($redPacketId);

        try {
            if ($redPacketId) {
                $redPacketInfo = RedPacketService::getInstance()->getRedPacketInfo($userId, $redPacketId);
                if ($redPacketInfo == null) {
                    return rjson([
                        'red_id' => $redPacketId
                    ], 500, '当前房间红包不存在,请重试');
                }
            } else {
                $redPacketInfo = RedPacketService::getInstance()->getRoomRedPacketInfo($userId, $roomId);
                if ($redPacketInfo == null) {
                    return rjson([], 500, '已经被抢光了');
                }
            }

            $ret = $this->viewRedPacketInfo($userId, $redPacketInfo);
            return rjson($ret);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}