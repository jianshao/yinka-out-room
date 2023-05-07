<?php
/**
 * 金币抽奖
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use app\domain\lottery\CoinLotteryRewardModelDao;
use app\domain\lottery\CoinLotteryService;
use app\domain\lottery\CoinLotterySystem;
use app\domain\user\dao\CoinDao;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class GoldcoinBoxController extends ApiBaseController
{

	//抽奖初始化
	//reward_type 1头像，气泡框 2金币 3礼物
	public function goldcoinBoxInit()
	{
        throw new FQException("活动期间金币转盘暂时关闭", 500);
		$result = [];
        try {
            $result['self_gold_num'] = CoinDao::getInstance()->loadCoin($this->headUid);

            $lotterys = CoinLotterySystem::getInstance()->getLotterys();
            $result['reward_list'] = $this->encodeReward($lotterys);

            //抽奖滚动
            $rewardLogList = [];
            $coinLotteryModels = CoinLotteryRewardModelDao::getInstance()->loadCoinLotteryModels($this->headUid,10);
            foreach ($coinLotteryModels as $model) {
                $assetId = CoinLotteryService::getInstance()->getAssetId($model->rewardType, $model->rewardId);
                $asset = AssetSystem::getInstance()->findAssetKind($assetId);
                if ($asset == null) {
                    continue;
                }

                $nickname = UserModelCache::getInstance()->findNicknameByUserId($this->headUid);
                $rewardLogList[] = [
                    'reward_name'=>$asset->displayName,
                    'reward_num'=>$model->num,
                    'nickname'=>$nickname
                ];
            }
            $result['log_list'] = $rewardLogList;

            $lottryModels = CoinLotterySystem::getInstance()->getLotteryPrices();
            $result['conf'] = $this->encodelottryConf($lottryModels);

            $rules = CoinLotterySystem::getInstance()->getRules();
            $result['explain'] = $this->encodeRules($rules);
            $result['room_id'] = CommonCacheService::getInstance()->randomTaskRoomId();
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }

	}

	//执行抽奖
	public function goldcoinBox()
    {
        $num = Request::param('num');
        throw new FQException("活动期间金币转盘暂时关闭", 500);
        try {
            list($lotteryBalance, $lotterys) = CoinLotteryService::getInstance()->coinLottery($this->headUid, $num);

            $res = [];
            foreach ($lotterys as $lottery){
                $res[] = [
                    'reward_id' => $lottery->id,
                    'reward_name' => $lottery->name,
                    'reward_image' => CommonUtil::buildImageUrl($lottery->image),
                    'reward_type' => AssetUtils::getAssetTypeFromAssetId($lottery->reward->assetId),
                    'reward_num' => $lottery->reward->count,
                    'reward_time' => $lottery->reward->count
                ];
            }

            $list['list'] = $res;
            $list['self_gold_num'] = $lotteryBalance;

            return rjson($list);
        }catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

	//抽奖记录
	public function goldcoinBoxLog()
	{
        throw new FQException("活动期间金币转盘暂时关闭", 500);
        $page = Request::param('page');
        try {
            $pageNum = 20;
            $page = empty($page) ? 1 : $page;
            $start = ($page-1) * $pageNum;

            $rewardLogList = [];
            $pageInfo = ["page" => $page, "pageNum" => $pageNum, "totalPage" => 0];
            $result = [
                "list" => $rewardLogList,
                "pageInfo" => $pageInfo,
            ];

            return rjson($result);
            $lottryModels = CoinLotteryRewardModelDao::getInstance()->loadCoinLotteryModels($this->headUid, $start, $pageNum);
            foreach ($lottryModels as $model) {
                $assetId = CoinLotteryService::getInstance()->getAssetId($model->rewardType, $model->rewardId);
                $asset = AssetSystem::getInstance()->findAssetKind($assetId);
                if ($asset == null) {
                    continue;
                }

                $rewardLogList[] = [
                    'reward_name'=>$asset->displayName.'*'.$model->num.$asset->unit,
                    'create_time'=>TimeUtil::timeToStr($model->createTime),
                    'reward'=>CommonUtil::buildImageUrl($asset->image)
                ];
            }

            $count = CoinLotteryRewardModelDao::getInstance()->getCoinLotteryCount($this->headUid);
            $pageInfo = ["page" => $page, "pageNum" => $pageNum, "totalPage" => ceil($count/$pageNum)];
            $result = [
                "list" => $rewardLogList,
                "pageInfo" => $pageInfo,
            ];

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

    private function encodeReward($lotterys) {
        $res = [];
        foreach ($lotterys as $lottery){
            $res[] = [
                'reward_id' => $lottery->id,
                'reward_name' => $lottery->name,
                'reward_image' => CommonUtil::buildImageUrl($lottery->image),
                'reward_num' => $lottery->reward->count,
                "reward_time" => $lottery->reward->count,
                'reward_type' => AssetUtils::getAssetTypeFromAssetId($lottery->reward->assetId),
                'goldbox_order' => $lottery->id,
            ];
        }
        return $res;
    }

    private function encodelottryConf($lottryModels){
        $lottryConf = [];
        foreach ($lottryModels as $model){
            $lottryConf[] = [
                'num' => $model->num,
                'gold_coin' => $model->num*$model->price->count
            ];
        }
        return $lottryConf;
    }

    private function encodeRules($rules){
        $rulesConf = [];
        foreach ($rules as $rule){
            $rulesConf[] = [
                'content' => $rule
            ];
        }

        if($this->channel == 'appStore') {
            array_push($rulesConf, ['content'=>'6、本活动与苹果公司无关']);
        }
        return $rulesConf;
    }

}