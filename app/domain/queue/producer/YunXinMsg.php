<?php

namespace app\domain\queue\producer;

use app\domain\asset\AssetKindIds;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftKind;
use app\domain\queue\Worker;
use app\utils\CommonUtil;
use think\facade\Log;

class YunXinMsg
{
    protected static $instance;
    protected $topic;
    protected $messageId;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new YunXinMsg();
        }
        return self::$instance;
    }

    public function sendMsg($data)
    {
        $consumer = 'app\domain\queue\consumer\YunXinMsg@sendMsg';  //消费者类
        return Worker::getInstance()->push($consumer, $data, 'default');
    }

    /**
     * @Info 发送礼物的云信通知消息
     * @param $fromUserId
     * @param $toUid
     * @param GiftKind $giftModel
     * @param $giftCount
     * @param $toName
     * @return string
     */
    public function sendGift($fromUserId, $toUid, GiftKind $giftModel, $giftCount, $toName)
    {
        if (empty($fromUserId) || empty($toUid)) {
            return " ";
        }
        $data['diamond'] = filter_money($giftModel->getReceiverAssetCount(AssetKindIds::$DIAMOND) / config('config.khd_scale'));
        $data['giftCount'] = strval($giftCount);
        $data['giftName'] = $giftModel->name;
        $data['giftUrl'] = CommonUtil::buildImageUrl($giftModel->image);
        $data['userName'] = $toName;
        $msg = ['data' => $data, 'type' => 1];
        return $this->sendMsg(['from' => $fromUserId, 'ope' => 0, 'toUid' => $toUid, 'type' => 100, 'msg' => $msg]);
    }

    /**
     * @Info 工会代充消息
     * @param $fromUserId
     * @param $toUid
     * @param $exchangeDiamond
     * @return mixed|string
     */
    public function sendTradeUnionAgent($fromUserId, $toUid, $toName, $bean)
    {
        if (empty($fromUserId) || empty($toUid)) {
            return " ";
        }
        $data['name'] = $toName;
        $data['bean'] = $bean;
        $msg = ['data' => $data, 'type' => "freeBeans"];
        return $this->sendMsg(['from' => $fromUserId, 'ope' => 0, 'toUid' => $toUid, 'type' => 100, 'msg' => $msg]);
    }


    /**
     * @Info 发送小秘书消息
     * @param $toUid int  送达人id
     * @param $msg string 消息内容数据
     * @return mixed|string
     */
    public function sendAssistantMsg($toUid, $msg)
    {
        try {
            if (empty($toUid) || empty($msg)) {
                throw new FQException(sprintf("YunXinMsg sendAssistantMsg param error touid:%d msg:%s", $toUid, $msg),500);
            }
            return YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $toUid, 'type' => 0, 'msg' => ['msg' => $msg]]);
        } catch (FQException $e) {
            Log::info(sprintf("YunXinMsg sendAssistantMsg fatal error msg=%s strace=%s", $e->getMessage(), $e->getTraceAsString()));
            return "";
        }
    }


}