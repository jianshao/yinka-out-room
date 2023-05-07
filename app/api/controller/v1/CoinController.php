<?php
/**
 * MQTT收发消息
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\domain\pay\ChargeService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\OrderStates;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class CoinController extends ApiBaseController
{
    /**充值列表
     * @param $token
     */
    public function chargeList()
    {
        $page = Request::param('page');          //分页
        if (!$page) {
            return rjson([],500, '参数错误');
        }
        $user_id = $this->headUid;
        $limit = 20;
        //查询对应的充值数据
        $endtime = '2020-04-28 19:33:43';
        $where[] = ['uid','=',$user_id];
        $where[] = ['addtime','>=',$endtime];
        $where[] = ['type', '=', 1];
        $offset = ($page - 1) * $limit;
        $count = OrderModelDao::getInstance()->where($where)->count();
        $totalPage = ceil($count / $limit);
        $list = OrderModelDao::getInstance()->getList($where, $offset, $limit);
        foreach($list as $key=>$value){
            if (in_array($list[$key]['status'], [OrderStates::$PAID, OrderStates::$DELIVERY])) {
                $list[$key]['status_content'] = "充值成功";
            }else{
                $list[$key]['status_content'] = "充值失败";
            }
        }
        //分页数据
        $pageInfo = array('page' => $page, 'pageNum' => $limit, 'totalPage' => $totalPage);
        //返回数据
        $result = [
            'list' => $list,
            'pageInfo' => $pageInfo,
        ];
        return rjson($result);
    }

    //豆兑换金币
    public function chargeListCoin()
    {
        $userId = intval($this->headUid);
        $products = ChargeService::getInstance()->getBeanCoinList();
        $defaultId = 0;
        $ret = [];
        foreach ($products as $product) {
            if ($defaultId === 0) {
                $defaultId = $product->productId;
            }
            $ret[] = [
                'id' => $product->productId,
                'price' => $product->price,
                'coin' => $product->bean,
                'chargemsg' => $product->chargeMsg,
                'coinimg' => CommonUtil::buildImageUrl($product->image),
                'status' => $product->status,
            ];
        }
        return rjson([
            'charge_list' => $ret,
            'defaultId' => $defaultId,
        ]);
    }


}