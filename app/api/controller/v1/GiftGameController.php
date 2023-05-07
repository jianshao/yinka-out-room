<?php
namespace app\api\controller\v1;
//游戏礼物类
//
use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\game\GameService;
use app\domain\game\taojin\dao\TaoJinRankModelDao;
use app\domain\game\taojin\dao\TaoJinRewardModelDao;
use app\domain\game\taojin\model\OreModel;
use app\domain\game\taojin\TaojinService;
use app\domain\game\taojin\TaojinSystem;
use app\domain\mall\dao\MallBuyRecordModelDao;
use app\domain\mall\MallIds;
use app\domain\mall\MallSystem;
use app\domain\mall\service\MallService;
use app\domain\user\dao\BeanModelDao;
use app\event\OreExchangeEvent;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use app\view\MallView;
use \app\facade\RequestAes as Request;
use app\api\controller\WebBaseController;
use app\common\RedisLock;

class GiftGameController extends WebBaseController
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

    //buchu
    public function selfNum()
    {
        try {
            $gameid = Request::param('gameid');
            $num = Request::param('num');
            $num = floor($num);

            TaojinService::getInstance()->setSelfDiceNum($this->headUid, $gameid, $num);
            return game_rjson();
        }catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

     /**
     * [大厅初始化]
     * @param string 
     */ 
    public function HallInfo()
    {
        try {
            if (!TaojinSystem::getInstance()->getGameStatus(time())){
                return game_rjson([], 500, '暂未开放');
            }

            $data = [];
            $gameList = [];
            $taojins = TaojinSystem::getInstance()->getTaojins();
            foreach ($taojins as $taojin) {
                $gameList[] = $this->encodeTaoJinGameInfo($taojin);
            }
            $data['gamelist'] = $gameList;

            $userinfo = $this->getUserTaoJinInfo($this->headUid);
            $data['user'] = $userinfo;

            $data['energy'] = ["commontoast" => TaojinSystem::getInstance()->getTaoJinCommonToast()];
            $data['rule'] = TaojinSystem::getInstance()->getTaoJinRule();
            $data['autoBuy'] = GameService::getInstance()->getAutoBuy($this->headUid);
            list($data['start_time'], $data['end_time']) = TaojinSystem::getInstance()->getGameTime();
            return game_rjson($data);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * 计算用户排行榜榜距离上一名的值
     * @param $uid
     * @param $rankData
     * @return int|mixed
     */
    protected function getUserRank($uid, $rankData) {
        if(!empty($rankData)) {
            $flag = array_key_exists($uid, $rankData);
            if($flag) { //用户在榜单中
                $differBeforeUidScore = 0;
            } else {
                $differBeforeUidScore = array_pop($rankData);
            }
        } else {
            $differBeforeUidScore = 1;
        }
        return $differBeforeUidScore;
    }

    /**
     * [排行榜]
     * @param string $value [description]
     */
    public function GameRankList()
    {
        try {
            $data = [];
            $timestamp = time();
            $modelDao = TaoJinRankModelDao::getInstance();

            $dayRes = $modelDao->getRankDataByRedisKey(TaoJinRankModelDao::getDayRedisKey($timestamp),0,49);
            $weekRes = $modelDao->getRankDataByRedisKey(TaoJinRankModelDao::getWeekRedisKey($timestamp),0,49);
            $monthRes = $modelDao->getRankDataByRedisKey(TaoJinRankModelDao::getMonthRedisKey(),0,49);
            $wUid = array_keys($weekRes);
            $mUid = array_keys($monthRes);

            $randId = 1;
            foreach ($dayRes as $userId => $value){
                $userModel = UserModelCache::getInstance()->getUserInfo($userId);
                if (empty($userId)){
                    continue;
                }
                $data['today'][] = [
                    'index'=>$randId++,
                    'uid' => $userId,
                    'avatar'=> CommonUtil::buildImageUrl($userModel->avatar),
                    'nickname'=>$userModel->nickname,
                    'totalcoin'=>$value,
                ];
            }

            $randId = 1;
            foreach ($weekRes as $userId => $value){
                $userModel = UserModelCache::getInstance()->getUserInfo($userId);
                if (empty($userId)){
                    continue;
                }
                $data['week'][] = [
                    'index'=>$randId,
                    'uid' => $userId,
                    'avatar'=> CommonUtil::buildImageUrl($userModel->avatar),
                    'nickname'=>$userModel->nickname,
                    'beforeDiff'=> $randId == 1 ? 0 : $weekRes[$wUid[array_search($userId, $wUid) - 1]] - $value,
                ];
                $randId++;
            }

            $randId = 1;
            foreach ($monthRes as $userId => $value){
                $userModel = UserModelCache::getInstance()->getUserInfo($userId);
                if (empty($userId)){
                    continue;
                }
                $data['month'][] = [
                    'index'=>$randId,
                    'uid' => $userId,
                    'avatar'=> CommonUtil::buildImageUrl($userModel->avatar),
                    'nickname'=>$userModel->nickname,
                    'beforeDiff'=> $randId == 1 ? 0 : @$monthRes[$mUid[array_search($userId, $mUid) - 1]] - $value,
                ];
                $randId++;
            }

            $data['user'] = $this->getUserTaoJinInfo($this->headUid);

            $dayDiffScore = $this->getUserRank($this->headUid, $dayRes);
            $weekDiffScore = $this->getUserRank($this->headUid, $weekRes);
            $monthDiffScore = $this->getUserRank($this->headUid, $monthRes);
            $data['user']['diffScore'] = ['today' => $dayDiffScore, 'week' => $weekDiffScore, 'month' => $monthDiffScore];

            return game_rjson($data);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }

    }

    /**
     * [游戏初始化]
     * @param string $value [description]
     */
    public function GameInfo()
    {
        try {
            $gameid = Request::param('gameid');
            $taojin = TaojinSystem::getInstance()->findTaojinByGameId($gameid);
            $res = $this->encodeTaoJinGameInfo($taojin);
            $res['dice'] = ['times'=>1,'energy'=>$taojin->energy];

            $res['user'] = $this->getUserTaoJinInfo($this->headUid);
            $res['energy'] = [
                'commontoast'=>TaojinSystem::getInstance()->getTaoJinCommonToast(),
                'lacktoast'=>TaojinSystem::getInstance()->getTaoJinLackToast()
            ];

            $exchanges = [];
            $mall = MallSystem::getInstance()->findMallByMallId(MallIds::$ORE);
            if ($mall != null) {
                $shelvesArea = $mall->findShelvesArea(MallIds::$ORE);
                if ($shelvesArea != null) {
                    foreach ($shelvesArea->shelvesList as $shelves) {
                        foreach ($shelves->goodsList as $goods) {
                            if (MallView::isShowInMall($goods)) {
                                $exchanges[] = MallView::encodeGoodsWithOre($goods, $mall, $shelvesArea);
                            }
                        }
                    }
                }
            }
            $res['exchange'] = $exchanges;

            $step = TaojinService::getInstance()->getStep($this->headUid, $gameid);
            $res['step'] = $step ? intval($step) : 0;

            $self_num = TaojinService::getInstance()->getSelfDiceNum($this->headUid, $gameid);
            $res['self_num'] = $self_num?$self_num:50;
            return game_rjson($res);
        }catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * [游戏滚动信息]
     * @param string $value [description]
     */
    public function gamebroCast()
    {
        try {
            $gameId = Request::param('gameid');
            $num = Request::param('num');
            $num = $num?$num:20;
            $count = $num == 200 ? 2000:1000;

            $rewardLogList = [];
            $taoJinModels = TaoJinRewardModelDao::getInstance()->loadGameRewards($gameId, $count, $num);

            foreach ($taoJinModels as $model) {
                $assetId = TaojinService::getInstance()->getAssetIdFromType($model->rewardType);
                $asset = AssetSystem::getInstance()->findAssetKind($assetId);
                if ($asset == null) {
                    continue;
                }

                $nickname = UserModelCache::getInstance()->findNicknameByUserId($model->userId);
                $rewardLogList[] = $this->encodeTaoJinRewardInfo($nickname, $model, $asset);
            }

            return game_rjson($rewardLogList);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * [游戏奖励列表]
     * @param string $value [description]
     */
    public function GameRewards()
    {
        try {
            $gameId = Request::param('gameid');
            $page = Request::param('page');
            $pageNum = 100;
            $page = empty($page) ? 1 : $page;
            $start = ($page-1) * $pageNum;

            $rewardLogList = [];
            $taoJinModels = TaoJinRewardModelDao::getInstance()->loadSelfRewards($this->headUid, $gameId, $start, $pageNum);

            $nickname = UserModelCache::getInstance()->findNicknameByUserId($this->headUid);
            foreach ($taoJinModels as $model) {
                $assetId = TaojinService::getInstance()->getAssetIdFromType($model->rewardType);
                $asset = AssetSystem::getInstance()->findAssetKind($assetId);
                if ($asset == null) {
                    continue;
                }

                $rewardLogList[] = $this->encodeTaoJinRewardInfo($nickname, $model, $asset);
            }

            return game_rjson($rewardLogList);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * [兑换列表]
     * @param string $value [description]
     */
    public function GameExchangeList()
    {
        try {
            $page = Request::param('page');
            $pageNum = 100;
            $page = empty($page) ? 1 : $page;
            $start = ($page-1) * $pageNum;

            $rewardLogList = [];
            $taoJinModels = MallBuyRecordModelDao::getInstance()->getTaoJinModels($this->headUid, $start, $pageNum);
            foreach ($taoJinModels as $model) {
                $asset = AssetSystem::getInstance()->findAssetKind($model->rewardId);
                if ($asset == null) {
                    continue;
                }
                $rewardLogList[] = $this->encodeTaoJinExchangeInfo($model, $asset);
            }

            return game_rjson($rewardLogList);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * [兑换]
     * @param string $value [description]
     */
    public function GameExchange()
    {
        try {
            $gameId = Request::param('gameid');
            $type = Request::param('type');

            $typeToAssetMap = [
                1 => AssetKindIds::$TAOJIN_ORE_FOSSIL,
                2 => AssetKindIds::$TAOJIN_ORE_GOLD,
                3 => AssetKindIds::$TAOJIN_ORE_SILVER,
                4 => AssetKindIds::$TAOJIN_ORE_IRON
            ];

            $assetId = ArrayUtil::safeGet($typeToAssetMap, $type);
            if ($assetId == null) {
                throw new FQException('此商品不存在', 500);
            }

            $goods = MallSystem::getInstance()->findGoodsByConsumeAssetIdInMall(MallIds::$ORE, $assetId);

            if ($goods == null) {
                throw new FQException('此商品不存在', 500);
            }

            $userId = intval($this->headUid);
            $count = 1;

            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $count, MallIds::$ORE, $gameId);

            event(new OreExchangeEvent($userId, $gameId, $goods->deliveryAsset->assetId, time()));

            $userInfo = $this->getUserTaoJinInfo($userId);
            return game_rjson([
                'user' => $userInfo
            ],200,'兑换成功');
        } catch (AssetNotEnoughException $e) {
            return game_rjson([], 500, '当前矿石不足,赶快去获得矿石吧');
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * [掷骰子]
     * @param string $gameid [游戏id]
     * @param string $num [次数]
     */
    public function GameAction()
    {
        try{
            $data = [];
            $gameid = Request::param('gameid');
            $num = (int) Request::param('num');
            if (!is_integer($num) || $num < 1 || $num > 50) {
                return game_rjson([],500,'次数错误');
            }
            if (!TaojinSystem::getInstance()->getGameStatus(time())) {
                return game_rjson([],500,'暂未开放');
            }
            $redisService = [[config('config.redis.host'),config('config.redis.port'),0.1]];
            $redisLock = new RedisLock($redisService);
            $lockKey = 'redis_game_lock_'.$this->headUid;
            $lockRes = $redisLock->lock($lockKey,3000);
            if (!$lockRes) {
                return game_rjson([],500,'操作过快,请重试');
            }

            //前端根据这个值算权重，不同权重特效不一样
            $orePrimary = [
                AssetKindIds::$BEAN => 1,
                AssetKindIds::$TAOJIN_ORE_IRON => 222,
                AssetKindIds::$TAOJIN_ORE_SILVER => 1733,
                AssetKindIds::$TAOJIN_ORE_GOLD => 4380,
                AssetKindIds::$TAOJIN_ORE_FOSSIL => 11147,
            ];

            $rewards = [];
            $autoBuy = GameService::getInstance()->getAutoBuy($this->headUid);
            $taojinRewards = TaojinService::getInstance()->doRollDice($this->headUid, $gameid, $num, $autoBuy);
            foreach ($taojinRewards as list($diceNum, $taojinReward)){
                $asset = AssetSystem::getInstance()->findAssetKind($taojinReward->reward->assetId);
                if ($asset == null) {
                    continue;
                }
                $rewards[] = [
                    'step' => $diceNum,
                    'giftid' => $taojinReward->reward->assetId,
                    'type' => $taojinReward->reward->assetId==AssetKindIds::$BEAN ? 5:0,
                    'gift_coin' => $orePrimary[$taojinReward->reward->assetId],
                    'giftnum' => $taojinReward->reward->count,
                    'gift_name' => $asset->displayName,
                    'gift_image' => CommonUtil::buildImageUrl($asset->image)
                ];
            }

            $userinfo = $this->getUserTaoJinInfo($this->headUid);
            $data['user'] = $userinfo;
            $data['gift'] = $rewards;
            $data['level'] = ['low'=>[0,500],'mid'=>[501, 2000],'high'=>[2001]];
            $step_now = TaojinService::getInstance()->getStep($this->headUid, $gameid);
            $data['step_now'] = (int)$step_now;
            $redisLock->unlock($lockRes);
            return game_rjson($data);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    private function encodeTaoJinRewardInfo($nickname, $model, $asset){
        return [
            'nickname' => $nickname,
            'id' => $model->id,
            'uid' => $model->userId,
            'game_id' => $model->gameId,
            'gift_num' => $model->num,
            'type' => $model->rewardType,
            'create_time'=>TimeUtil::timeToStr($model->createTime),
            'gift_name'=>$asset->displayName,
            'gift_iamge'=>CommonUtil::buildImageUrl($asset->image)
        ];
    }

    private function encodeTaoJinExchangeInfo($model, $asset){
        return [
            'id' => $model->id,
            'uid' => $model->userId,
            'ore_num' => $model->price,
            'gift_num' => $model->count,
            'gift_name' => $asset->displayName,
            'type' => TaojinService::getInstance()->getTypeFromAssetId($model->consumeId),
            'create_time' => TimeUtil::timeToStr($model->createTime),
            'gift_iamge' => CommonUtil::buildImageUrl($asset->image),
            'game_id' => intval($model->from)
        ];
    }

    private function encodeTaoJinGameInfo($taojin){
        return [
            'id' => $taojin->gameId,
            'game_name' => $taojin->name,
            'game_image' => CommonUtil::buildImageUrl($taojin->image),
            'game_energy' => $taojin->energy,
            'game_status' => 1,
            'game_cover' => CommonUtil::buildImageUrl($taojin->cover),
            'game_map' => CommonUtil::buildImageUrl($taojin->map),
            'game_covermap' => CommonUtil::buildImageUrl($taojin->covermap),
            'game_bgmap' => CommonUtil::buildImageUrl($taojin->bgmap),
        ];
    }

    private function getUserTaoJinInfo($userId){
        $userinfo = [];
        $bean = BeanModelDao::getInstance()->loadBean($userId);
        $userinfo['totalcoin'] = $bean == null ? 0 : $bean->balance();

        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        $userinfo['uid'] = $userModel->userId;
        $userinfo['nickname'] = $userModel->nickname;
        $userinfo['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);

        $userinfo['energy'] = BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$GAME_SCORE);

        $oreModel = new OreModel();
        $materials = $oreModel->adjust($userId, time());
        $userinfo['materials'] = $materials;

        return $userinfo;
    }
}