<?php


namespace app\domain\activity\duobao3;


use app\common\RedisCommon;

class OrderDao
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new OrderDao();
        }
        return self::$instance;
    }

    public function decodeSeatInfos($seatInfosStr) {
        $ret = [];
        if (!empty($seatInfosStr)) {
            $seatInfosJson = json_decode($seatInfosStr,true);
            foreach ($seatInfosJson as $seatInfoJson) {
                $seatInfo = new SeatInfo();
                $seatInfo->userId = intval($seatInfoJson['userId']);
                $seatInfo->grabTime = intval($seatInfoJson['grabTime']);
                $ret[] = $seatInfo;
            }
        }
        return $ret;
    }

    public function dataToOrder($issueNumber, $data) {
        $order = new Order();
        $order->issueNum = $issueNumber;
        $order->giftId = intval($data['giftId']);
        $order->giftCoin = intval($data['giftCoin']);
        $order->giftImage = $data['giftImage'];
        $order->price = intval($data['price']);
        $order->seatCount = intval($data['seatCount']);
        $order->status = intval($data['status']);
        $order->createTime = intval($data['createTime']);
        $order->winnerIndex = intval($data['winnerIndex']);
        $order->seatInfos = $this->decodeSeatInfos($data['seatInfos']);
        return $order;
    }

    public function loadOrder($tableName, $issueNum) {
        $orderKey = 'duobaoorder:' . $tableName . ':' . $issueNum;
        $redis = RedisCommon::getInstance()->getRedis();
        $orderData = $redis->hgetAll($orderKey);
        if (empty($orderData)) {
            return null;
        }
        return $this->dataToOrder($issueNum, $orderData);
    }
}