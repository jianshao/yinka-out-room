<?php
/**
 * 金币商城
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use app\domain\mall\dao\MallBuyRecordModelDao;
use app\domain\mall\MallIds;
use app\domain\mall\MallSystem;
use app\domain\mall\service\MallService;
use app\domain\user\dao\CoinDao;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use app\view\MallView;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class GoldMallController extends ApiBaseController
{

	//商城初始化
	//reward_type 1头像，气泡框 2金币 3礼物
	public function goldcoinMallInit()
	{
        throw new FQException("活动暂时关闭", 500);
        $type = Request::param('type', 'coin');
		$result = [];
        try {
            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$COIN);
            if ($mall != null) {
                $shelvesArea = $mall->findShelvesArea($type);
                if ($shelvesArea != null) {
                    foreach ($shelvesArea->shelvesList as $shelves) {
                        foreach ($shelves->goodsList as $goods) {
                            if (MallView::isShowInMall($goods)) {
                                $result['reward_list'][] = MallView::encodeGoodsWithCoin($goods, $mall, $shelvesArea);
                            }
                        }
                    }
                }
            }

            //我的金币数
            $count = CoinDao::getInstance()->loadCoin($this->headUid);
            $result['self_gold_num'] = $count != null ? $count : 0;

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

	//执行兑换
	public function goldcoinMall()
	{
        throw new FQException("活动暂时关闭", 500);
        $goodsId = (int)Request::param('reward_id');
        $userId = $this->headUid;
        $count = 1;

        $goods = MallSystem::getInstance()->findGoods($goodsId);
        if ($goods == null) {
            throw new FQException('此商品不存在', 500);
        }

        try {
            $balance = MallService::getInstance()->buyGoodsByGoods($userId, $goods, $count, MallIds::$COIN, MallBuyRecordModelDao::$COIN_EXCHANGE);
            $list['self_gold_num'] = $balance;
            return rjson($list);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}


	//兑换记录
	public function goldcoinMallLog()
	{
        throw new FQException("活动暂时关闭", 500);
		$page = Request::param('page');
		$pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $start = ($page-1) * $pageNum;

        $exchanges = [];
		$exchangeModels = MallBuyRecordModelDao::getInstance()->getCoinExchangeModels($this->headUid, $start, $pageNum);
		foreach ($exchangeModels as $model) {
            $asset = AssetSystem::getInstance()->findAssetKind($model->rewardId);
            if ($asset == null) {
                continue;
            }

            $exchanges[] = [
                'reward_image'=>CommonUtil::buildImageUrl($asset->image),
                'reward_name'=>$asset->displayName.'*'.$model->count.$asset->unit,
                'reward_price'=>$model->price,
                'reward_type' => AssetUtils::getAssetTypeFromAssetId($model->rewardId),
                'create_time'=> TimeUtil::timeToStr($model->createTime)
            ];
		}

        $count = MallBuyRecordModelDao::getInstance()->getCoinExchangeCount($this->headUid);
		$pageInfo = ['page' => $page, 'pageNum' => $pageNum, 'totalPage' => ceil($count/$pageNum)];
        $result = [
            "list" => $exchanges,
            "pageInfo" => $pageInfo,
        ];

		return rjson($result);
	}
}