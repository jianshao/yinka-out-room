<?php


namespace app\api\controller\v1\Activity;


use app\common\RedisCommon;
use app\domain\activity\duobao3\Config;
use app\domain\activity\duobao3\Duobao3Service;
use app\domain\exceptions\FQException;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;

class ThreeLootController
{
    public function tableInfos() {
        try {
            $result = [];
            $tableConfigMap = Config::getInstance()->getTableConfigMap();
            foreach ($tableConfigMap as $tableId => $tableConfig) {
                $info = $this->_getTableInfo($tableConfig);
                $result[$tableConfig->tableId] = $info;
            }
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function tableInfo() {
        $tableId = Request::param('tableid');
        $tableConfig = Config::getInstance()->getTableConfig($tableId);
        if ($tableConfig == null) {
            return rjson([],500,'奖池不存在');
        }
        try {
            $info = $this->_getTableInfo($tableConfig);
            $info['tableid'] = '' . $tableId;
            return rjson($info);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }



    public function grabSeat() {
        return rjson([], 500, '活动未开启');
        $token = Request::param('mtoken');
        $tableId = Request::param('type'); //奖池类型 1 小奖池 2中奖池 3大奖池
        $orderId = Request::param('orderId'); //礼物id

        if(!$tableId || !$orderId ) {
            return rjson([],500,'参数错误');
        }

        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }

        $tableId = intval($tableId);

        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }

        $userId = intval($userId);
        list($_, $issueNum) = Duobao3Service::getInstance()->parseOrderId($orderId);

        try {
            Duobao3Service::getInstance()->grabSeat($userId, $tableId, $issueNum);
            $res = [
                'code' => 200,
                'msg' => '抢占成功，稍后为您开奖'
            ];
        } catch (FQException $e) {
            $res = [
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }


        $order = Duobao3Service::getInstance()->getOrder($tableId, $issueNum);
        if ($order != null) {
            $result = $this->_buildSeatInfo($order, $userId);
        } else {
            $result = [];
        }
        $result['tableid'] = $tableId;
        $result['orderId'] = $orderId;
        return rjson($result, $res['code'], $res['msg']);
    }


    public function lootAsk() {
        $token = Request::param('mtoken');
        $orderId = Request::param('orderId');
        $tableId = intval(Request::param('tableid'));

        if (!$orderId) {
            return rjson([],500,'参数错误');
        }

        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }

        $userId = intval($userId);
        list($_, $issueNum) = Duobao3Service::getInstance()->parseOrderId($orderId);
        $order = Duobao3Service::getInstance()->getOrder($tableId, $issueNum);
        if ($order != null) {
            $result = $this->_buildSeatInfo($order, $userId);
        } else {
            $result = [];
        }

        $result['tableid'] = $tableId;
        $result['orderId'] = $orderId;
        return rjson($result, 200,'success');
    }

    private function _getTableInfo($tableConfig) {
        $tableInfo = Duobao3Service::getInstance()->getTableInfo($tableConfig->tableId);
        $lastWinner = null;
        if ($tableInfo->lastWinnerIssue > 0 && $tableInfo->lastWinnerUserId > 0) {
            $userModel = UserModelCache::getInstance()->getUserInfo($tableInfo->lastWinnerUserId);
            $lastWinner = [
                'id' => $userModel->userId,
                'pretty_id' => $userModel->prettyId,
                'nickname' => $userModel->nickname,
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            ];
        }
        return [
            'currentPoolInfo' => [
                'giftId' => '' . $tableInfo->order->giftId,
                'giftImage' => CommonUtil::buildImageUrl($tableInfo->order->giftImage),
                'giftCoin' => '' . $tableInfo->order->giftCoin,
                'giftName' => '' . $tableInfo->order->giftName,
                'count' => '' . $tableInfo->order->seatCount,
                'seatCount' => $tableInfo->order->seatCount,
                'pay_price' => '' . $tableInfo->order->price,
                'currentCount' => count($tableInfo->order->seatInfos),
                'orderId' => Duobao3Service::getInstance()->makeOrderId($tableConfig->tableName, $tableInfo->order->issueNum)
            ],
            'lastWinner' => $lastWinner
        ];
    }

    private function _buildSeatInfo($order, $userId) {
        $ret = [];
        $seatInfos = [];
        $userInfoMap = [];
        $flag = false;
        $bingo = false;
        for ($i = 0; $i < count($order->seatInfos); $i++) {
            $seatInfo = $order->seatInfos[$i];
            if ($seatInfo->userId == $userId) {
                $flag = true;
                if ($order->status == 1
                    && $order->winnerIndex == $i) {
                    $bingo = true;
                }
            }

            if (!array_key_exists($seatInfo->userId, $userInfoMap)) {
                $userModel = UserModelCache::getInstance()->getUserInfo($seatInfo->userId);
                $userInfo = [
                    'id' => $userModel->userId,
                    'nickname' => $userModel->nickname,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                ];

                $userInfoMap[$seatInfo->userId] = $userInfo;
            } else {
                $userInfo = $userInfoMap[$seatInfo->userId];
            }
            $seatInfos[] = $userInfo;
        }

        $ret['seatInfo'] = $seatInfos;
        if ($order->status != 0) {
            $ret['isBreak'] = true;
        } else {
            $ret['isBreak'] = false;
        }

        $ret['mine'] = null;
        if ($flag == true) {
            $ret['mine']['bingo'] = $bingo;
            $ret['mine']['giftName'] = $order->giftName;
            $ret['mine']['giftImage'] = CommonUtil::buildImageUrl($order->giftImage);
        }
        return $ret;
    }
}