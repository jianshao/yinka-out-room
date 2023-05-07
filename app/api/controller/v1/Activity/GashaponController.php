<?php
/**
 * 金币抽奖
 * yond
 * 
 */

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\asset\AssetUtils;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\query\dao\GashaponRewardModelDao;
use app\domain\game\gashapon\GashaponService;
use app\domain\game\gashapon\GashaponSystem;
use app\domain\asset\AssetSystem;
use app\domain\exceptions\FQException;
use app\domain\mall\dao\MallBuyRecordModelDao;
use app\domain\mall\MallIds;
use app\domain\mall\MallSystem;
use app\domain\mall\service\MallService;
use app\domain\prop\PropSystem;
use app\domain\queue\producer\YunXinMsg;
use app\query\user\dao\AttentionModelDao;
use app\domain\user\dao\CoinDao;
use app\query\user\dao\FansModelDao;
use app\query\user\dao\FriendModelDao;
use app\query\user\QueryUserService;
use app\query\user\service\AttentionService;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;
use app\view\MallView;
use \app\facade\RequestAes as Request;
use think\facade\Log;


class GashaponController extends BaseController
{

    private function checkMToken() {
        $token = $this->request->param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            throw new FQException('用户信息错误', 500);
        }
        $userId = $redis->get($token);
        if (!$userId) {
            throw new FQException('用户信息错误', 500);
        }
        return $userId;
    }


    //抽奖初始化
	//reward_type 1头像，气泡框 2金币 3礼物
	public function init()
	{
        try {
            $userId = $this->checkMToken();

            $result = $this->encodeReward();

            $result['selfCoin'] = CoinDao::getInstance()->loadCoin($userId);
            $result['selfGashapon'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$GAME_GASHAPON);

            $result['price'] =  GashaponSystem::getInstance()->price->count;
            $result['counts'] =  GashaponSystem::getInstance()->counts;

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }

	}

	//执行抽奖
	public function doLottery()
	{
        try {

            $count = (int)Request::param('count');
            $roomId = (int)Request::param('roomId');

            $userId = $this->checkMToken();
            $resMap = GashaponService::getInstance()->doLottery($userId, $roomId, $count, time());

            $list['propMap'] = $resMap;
            $list['selfCoin'] = CoinDao::getInstance()->loadCoin($userId);
            $list['selfGashapon'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$GAME_GASHAPON);

            return rjson($list);
        }catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

	//抽奖记录
	public function rewardList()
	{
        $page = Request::param('page');
        try {
            $userId = $this->checkMToken();
            $pageNum = 100;
            $page = empty($page) ? 1 : $page;
            $start = ($page-1) * $pageNum;

            $models = GashaponRewardModelDao::getInstance()->loadSelfRewards($userId, $start, $pageNum);
            $rewardList = [];
            foreach ($models as $model) {
                $assetKind = AssetSystem::getInstance()->findAssetKind($model->rewardId);
                if ($assetKind == null) {
                    continue;
                }

                $rewardList[] = [
                    'userId' => $model->userId,
                    'reward_name' => $assetKind->displayName,
                    'reward_image' => CommonUtil::buildImageUrl($assetKind->image),
                    'reward_count' => $model->rewardCount,
                    'time' => $model->createTime
                ];
            }

            $total = GashaponRewardModelDao::getInstance()->getGashaponCount($userId);
            $pageInfo = ["page" => $page, "pageNum" => $pageNum, "totalPage" => ceil($total/$pageNum)];
            $result = [
                "list" => $rewardList,
                "pageInfo" => $pageInfo,
            ];

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
	}

    /**
     * [滚动信息]
     * @param string $value [description]
     */
    public function scrolling()
    {
        try {
            $userId = $this->checkMToken();

            $rewardList = [];
            $key = 'rank_gashapon_scroll';
            $redis = RedisCommon::getInstance()->getRedis();
            $datas = $redis->lRange($key, 0, -1);
            if (!empty($datas)) {
                foreach ($datas as $data) {
                    $jsonObj = json_decode($data, true);
                    $model = GashaponRewardModelDao::getInstance()->dataToModel($jsonObj);

                    $assetKind = AssetSystem::getInstance()->findAssetKind($model->rewardId);
                    if ($assetKind == null) {
                        continue;
                    }


                    $rewardList[] = [
                        'userId' => $model->userId,
                        'nickname'=> UserModelCache::getInstance()->findNicknameByUserId($model->userId),
                        'reward_name' => $assetKind->displayName,
                        'reward_image' => CommonUtil::buildImageUrl($assetKind->image),
                        'reward_count' => $model->rewardCount,
                        'time' => $model->createTime
                    ];
                }
            }

            return rjson($rewardList);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    private function encodeReward() {
        $res = [
            "propMap" => [],
            "avatar" => [],
            "bubble" => []

        ];

        $lotteryTotalWeight = GashaponSystem::getInstance()->lotteryTotalWeight;
        foreach (GashaponSystem::getInstance()->lotteryMap as $lottery){
            $assetKind = AssetSystem::getInstance()->findAssetKind($lottery->reward->assetId);
            $image = $assetKind->propKind->type != "bubble" ? $assetKind->image : $assetKind->propKind->bubbleWordImage;
            $res["propMap"][$lottery->reward->assetId] = [
                'name' => $assetKind->displayName,
                'multiple' => $assetKind->propKind->multiple,
                'image' => CommonUtil::buildImageUrl($image),
                'count' => $lottery->reward->count,
                'baolv' => round(floatval($lottery->weight) / floatval($lotteryTotalWeight)*10000, 2)
            ];
            if (array_key_exists($assetKind->propKind->type, $res)){
                $res[$assetKind->propKind->type][] = $lottery->reward->assetId;
            }

        }
        return $res;
    }

    //商城初始化
    public function mallInit()
    {
        $result = [];
        try {
            $userId = $this->checkMToken();
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);

            $mallList = [];
            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$GASHAPON);
            if ($mall != null) {
                $shelvesAreas = $mall->getShelvesAreaList();
                foreach ($shelvesAreas as $shelvesArea) {
                    foreach ($shelvesArea->shelvesList as $shelves) {
                        $goodsList = [];
                        foreach ($shelves->goodsList as $goods) {
                            if (MallView::isShowInMall($goods)) {
                                $asset = $goods->getFirstPriceAsset();
                                $propKindId = AssetUtils::getPropKindIdFromAssetId($goods->deliveryAsset->assetId);
                                $propKind = PropSystem::getInstance()->findPropKind($propKindId);
                                $goodsList[] = [
                                    'goodsId' => $goods->goodsId,
                                    'name' => $goods->name,
                                    'imageSvga' => CommonUtil::buildImageUrl($goods->animation),
                                    'image' => CommonUtil::buildImageUrl($goods->image),
                                    'priceAssetId' => $asset->assetId,
                                    'price' => $asset->count,
                                    'count' => $goods->deliveryAsset->count,
                                    'multiple' => $propKind?$propKind->multiple:1,
                                ];
                            }
                        }

                        $mallList[] = [
                            'type' => $shelves->type,
                            'typeName' => $shelves->displayName,
                            'goodsList' => $goodsList
                        ];
                    }
                }
            }

            $result['user'] = [
                'userId' => $userModel->userId,
                'prettyId' => $userModel->prettyId,
                'name' => $userModel->nickname,
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            ];
            $result['mallList'] = $mallList;
            $result['selfSilver'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_SILVER);
            $result['selfGold'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_GOLD);

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }

    //执行兑换
    public function mallExchange()
    {
        $goodsId = (int)Request::param('goodsId');
        $count = Request::param('count', 1, 'intval');

        try {
            $userId = $this->checkMToken();

            $goods = MallSystem::getInstance()->findGoods($goodsId);
            if ($goods == null) {
                throw new FQException('此商品不存在', 500);
            }

            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $count, MallIds::$GASHAPON, MallBuyRecordModelDao::$GASHAPON_EXCHANGE);
            $result['selfSilver'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_SILVER);
            $result['selfGold'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_GOLD);
            return rjson($result, 200, '兑换成功');
        } catch (FQException $e) {
            if ($e->getCode() == 211){
                return rjson([], $e->getCode(), '碎片不足');
            }
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //执行赠送
    public function mallSend()
    {
        $receivedId = Request::param('receivedId', 0, 'intval');
        $goodsId = (int)Request::param('goodsId');
        $count = Request::param('count', 1, 'intval');

        if (empty($receivedId) || empty($goodsId) || empty($count)) {
            throw new FQException('参数错误', 500);
        }

        try {
            $userId = $this->checkMToken();

            # receivedId不是我关注的人 不是我的粉丝 不是我的好友就赠送失败
            if (empty(AttentionModelDao::getInstance()->loadAttention($userId, $receivedId))){
                if (empty(FansModelDao::getInstance()->loadFansModel($userId, $receivedId))){
                    if (empty(FriendModelDao::getInstance()->loadFriendModel($userId, $receivedId))){
                        throw new FQException('赠送失败', 500);
                    }
                }
            }

            $goods = MallSystem::getInstance()->findGoods($goodsId);
            if ($goods == null) {
                throw new FQException('此商品不存在', 500);
            }

            MallService::getInstance()->sendGoodsByGoods($userId, $receivedId, $goods, $count,
                MallIds::$GASHAPON, MallBuyRecordModelDao::$GASHAPON_SEND);
            $result['selfSilver'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_SILVER);
            $result['selfGold'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$CHIP_GOLD);

            $userName = UserModelCache::getInstance()->findNicknameByUserId($userId);
            $msg = sprintf("%s赠送您%s*%s天，快去装扮查看吧。", $userName, $goods->name, $goods->deliveryAsset->count);
            YunXinMsg::getInstance()->sendAssistantMsg($receivedId, $msg);

            return rjson($result, 200, '赠送成功，已放入您赠送人的背包');
        } catch (FQException $e) {
            if ($e->getCode() == 211){
                return rjson([], $e->getCode(), '碎片不足');
            }
            return rjson([], $e->getCode(), "赠送失败");
        }
    }

    /*
     * 关注列表
     * @param $token   token值
     * @param $page    分页
     * @param $type    1关注列表 2粉丝列表 3好友
     */
    public function careUserList()
    {
        //获取数据
        $page = (int)Request::param('page');
        $type = (int)Request::param('type');

        if (!$page || !$type) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->checkMToken();
        $pageNum = 20;
        $offset = ($page - 1) * $pageNum;

        if ($type == 2) { // 粉丝列表
            list($attentions, $total) = AttentionService::getInstance()->listFans($userId, $offset, $pageNum);
        } elseif ($type == 3) { // 好友列表
            list($attentions, $total) = AttentionService::getInstance()->listFriend($userId, $offset, $pageNum);
        } else { // 关注
            list($attentions, $total) = AttentionService::getInstance()->listAttention($userId, $offset, $pageNum);
        }

        $attentionList = [];
        if (count($attentions) > 0) {
            foreach ($attentions as $attention) {
                $attentionList[] = $this->viewFriend($attention, $type);
            }
        }
        return rjson([
            'list' => $attentionList,
            'pageInfo' => [
                'page' => $page,
                'pageNum' => $pageNum,
                'totalPage' => ceil($total / $pageNum)
            ],
        ]);
    }

    /**好友搜索 在好友、关注、粉丝里搜索
     * @param $search   搜索昵称及用户Id
     */
    public function searchFriend()
    {
        try {
            $userId = $this->checkMToken();
            $search = Request::param('search');

            $friendList = [];
            list($queryUsers, $total) = QueryUserService::getInstance()->matchUsers($search, [$userId]);
            if (!empty($queryUsers)){
                $queryUserModel = $queryUsers[0];
                $status = 3;
                $model = FriendModelDao::getInstance()->loadFriendModel($userId, $queryUserModel->userId);
                if(empty($model)){
                    $status = 1;
                    $model = AttentionModelDao::getInstance()->loadAttention($userId, $queryUserModel->userId);
                    if(empty($model)){
                        $status = 2;
                        $model = FansModelDao::getInstance()->loadFansModel($userId, $queryUserModel->userId);
                    }
                }

                # model不为空说明有关系
                if (!empty($model)){
                    $quertAttention = AttentionService::getInstance()->dataToModel($queryUserModel, $model->createTime);
                    $friendList[] = $this->viewFriend($quertAttention, $status);
                }
            }

            return rjson($friendList);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    private function viewFriend($attention, $status){
        return [
            'userId' => $attention->userId,
            'nickname' => $attention->nickname,
            'avatar' => CommonUtil::buildImageUrl($attention->avatar),
            'status' => $status,
            'sex' => $attention->sex,
            'userLevel' => $attention->lvDengji,
            'vipLevel' => $attention->vipLevel,
            'dukeLevel' => $attention->dukeLevel
        ];
    }

}