<?php


namespace app\api\controller\v1;


use app\api\controller\ApiBaseController;
use app\domain\bi\BIConfig;
use app\query\bi\service\BiOrderService;
use app\domain\exceptions\FQException;
use app\query\pay\service\ChargeService;
use app\domain\pay\ProductTypes;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;

class OrderController extends ApiBaseController
{
    protected $diamond = 1;    //钻石
    protected $bean = 2;    //LB
    protected $rmb = 3;    //人民币

    public function androidChargeList()
    {
        $userId = intval($this->headUid);
        $charges = ChargeService::getInstance()->getAndroidChargeList($userId);
        $ret = [];
        foreach ($charges as $charge) {
            $ret[] = [
                'id' => $charge->id,
                'rmb' => $charge->rmb,
                'diamond' => $charge->bean,
                'present' => $charge->present,
                'chargemsg' => $charge->chargeMsg,
                'coinimg' => CommonUtil::buildImageUrl($charge->image),
                'vipgift' => 0,
                'iosflag' => $charge->productId,
                'status' => $charge->status
            ];
        }
        return rjson([
            'charge_list' => $ret
        ]);
    }


    /**
     * 收入明细
     */
    public function incomeNewDetails()
    {
        throw new FQException("当前版本不支持查看，请更新版本～",500);
    }

    //todo 预废除
    public function getActionByCoindetailModel($coindetailModel)
    {
        $actionMap = [
            BIConfig::$SEND_GIFT_EVENTID => 'sendGift',
            BIConfig::$REDPACKETS_EVENTID => 'sendRedPackets',
            BIConfig::$BUY_EVENTID => 'attire',
            BIConfig::$REPLACE_CHARGE_EVENTID => 'guildCharge',
            BIConfig::$WITHDRAW_PRETAKEOFF_EVENTID => 'withdraw',
            BIConfig::$DIAMOND_EXCHANGE_EVENTID => 'diamondExchange',
        ];

        $activityActionMap = [
            'box:silver' => 'BuyHarmmer0',
            'box:gold' => 'BuyHarmmer1',
            'duobao3:1' => 'three_treasures',
            'duobao3:2' => 'three_treasures',
            'duobao3:3' => 'three_treasures',
        ];

        if ($coindetailModel->eventId == BIConfig::$ACTIVITY_EVENTID) {
            $key = $coindetailModel->ext1 . ':' . $coindetailModel->ext2;
            return ArrayUtil::safeGet($activityActionMap, $key, 'attire');
        }

        return ArrayUtil::safeGet($actionMap, $coindetailModel->eventId, 'attire');
    }

    /**
     * 消费明细
     */
    public function expendDetails()
    {
        throw new FQException("当前版本不支持查看，请更新版本～",500);
    }


    /**
     * @param $assetType string  资产类型 [charge bean coin diamond score gift]
     * @return \think\response\Json
     */
    public function walletDetails()
    {
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        $assetType = Request::param('assetType', 'charge');
        $timeStr = Request::param('timeStr', date("Y-m-01"));
        $unixTime = strtotime($timeStr);
        $queryStartDate = date('Y-m-01', $unixTime);   //2021-08-01
        $queryEndDate = date('Y-m-d', strtotime("$queryStartDate +1 month"));
        $queryStartTime = strtotime($queryStartDate);
        $queryEndTime = strtotime($queryEndDate);
        $userId = $this->headUid;
        $tableName = BiOrderService::getInstance()->buildTableName($queryStartTime);
        if ($assetType === 'charge') {
            list($total, $data) = ChargeService::getInstance()->walletDetails($page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime);
        } else {
            list($total, $data) = BiOrderService::getInstance()->newGetDetailList($tableName, $page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime);
        }
        $pageInfo = array('page' => (int)$page, 'pageNum' => (int)$pageNum, 'totalPage' => ceil($total / $pageNum));
        return rjson(['list' => $data, 'pageInfo' => $pageInfo]);
    }

    /**
     * @data vip购买账单信息
     * @return \think\response\Json
     */
    public function vipBuyDetails()
    {
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');

        // 仅展示最近三个月
        $queryEndDate = date('Y-m-d H:i:s', time());   //2021-08-01
        $queryStartDate = date('Y-m-d', strtotime("$queryEndDate -3 month"));

        $queryStartTime = strtotime($queryStartDate);
        $queryEndTime = strtotime($queryEndDate);
        $userId = $this->headUid;

        list($total, $data) = ChargeService::getInstance()->walletDetails($page, $pageNum, $userId, 'vip', $queryStartTime, $queryEndTime ,
            [ProductTypes::$VIP, ProductTypes::$SVIP]
        );

        $pageInfo = array('page' => (int)$page, 'pageNum' => (int)$pageNum, 'totalPage' => ceil($total / $pageNum));
        return rjson(['list' => $data, 'pageInfo' => $pageInfo]);
    }



}