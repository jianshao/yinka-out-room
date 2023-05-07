<?php
namespace app\api\controller\v1;
//砸蛋类
//
use app\BaseController;
use app\common\RedisCommon;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\domain\exceptions\FQException;
use app\domain\game\GameService;
use app\domain\game\turntable\PoolTypes;
use app\domain\game\turntable\TurntableService;
use app\domain\game\turntable\TurntableSystem;
use app\domain\gift\GiftSystem;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\BeanModelDao;
use app\query\room\dao\QueryRoomDao;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
//
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//header('Access-Control-Allow-Methods: GET, POST, PUT');

class TurntableController extends BaseController
{
    public static $RANK_HEAD_FRAME_MAP = [
    ];

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

    /**
     * 初始化
     * @return [type] [description]
     */
    public function init()
    {
        // 宝箱数据
        $turntables = [];
        // 所有宝箱礼物ID
        $giftIdMap = [];

        $userId = $this->checkMToken();

        $sortedBoxes = [];
        foreach (TurntableSystem::getInstance()->boxMap as $turntableId => $box) {
            $sortedBoxes[] = $box;
        }
        usort($sortedBoxes, function($a, $b) {
            if ($a->price < $b->price) {
                return -1;
            } else if ($a->price > $b->price) {
                return 1;
            }
            return 0;
        });
        foreach ($sortedBoxes as $box) {
            $turntables[] = $this->encodeBox($box);
            foreach ($box->rewardPoolMap as $_ => $rewardPool) {
                foreach ($rewardPool->giftMap as $giftId => $_) {
                    $giftIdMap[$giftId] = 1;
                }

                $runningRewardPool = TurntableService::getInstance()->loadRunningRewardPool($box->turntableId, $rewardPool->poolId);
                if ($runningRewardPool) {
                    foreach ($runningRewardPool->giftMap as $giftId => $_) {
                        $giftIdMap[$giftId] = 1;
                    }
                }
            }
        }

        $giftMap = [];
        foreach ($giftIdMap as $giftId => $_) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind != null) {
                $giftMap[$giftId] = $this->encodeGift($giftKind);
            }
        }

        $bean = BeanModelDao::getInstance()->loadBean($userId);
        $autoBuy = GameService::getInstance()->getAutoBuy($userId);
        return rjson([
            'autoBuy' => $autoBuy,
            'score' => BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$GAME_SCORE),
            'bean' => $bean != null ? $bean->balance() : 0,
            'gifts' => $giftMap,
            'turntables' => $turntables
        ]);
    }

    /**
     * 转盘
     */
    public function turnTable()
    {
        $turntableId = intval($this->request->param('turntableId'));
        $count = intval($this->request->param('count'));
        $roomId = intval($this->request->param('roomId'));
        $autoBuy = $this->request->param('autoBuy');
        $userId = $this->checkMToken();
        try {
            if($autoBuy == null){
                $autoBuy = GameService::getInstance()->getAutoBuy($userId);
            }else{
                $autoBuy = intval($autoBuy);
            }

            list($totalPrice, $balance, $giftMap) = TurntableService::getInstance()->turnTable($userId, $roomId, $turntableId, $count, $autoBuy);

            $gifts = $this->sortGifts($giftMap);
            $bean = BeanModelDao::getInstance()->loadBean($userId);
            return rjson([
                'bean' => $bean != null ? $bean->balance() : 0,
                'score' => $balance,
                'rewards' => [
                    'gifts' => $gifts,
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    private function sortGifts($giftMap){
        # 价值最大的放数组末尾
        $maxPrice = 0;
        $maxPriceGiftId = 0;
        foreach ($giftMap as $giftId => $count) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            $price = $giftKind->price ? $giftKind->price->count : 0;
            if($price > $maxPrice){
                $maxPrice = $price;
                $maxPriceGiftId = $giftId;
            }
        }

        $gifts = [];
        foreach ($giftMap as $giftId => $count) {
            if ($giftId == $maxPriceGiftId){
                continue;
            }
            $gifts[] = [
                'id' => $giftId,
                'count' => $count
            ];
        }

        if($maxPriceGiftId > 0){
            $gifts[] = [
                'id' => $maxPriceGiftId,
                'count' => $giftMap[$maxPriceGiftId]
            ];
        }

        return $gifts;
    }

    public function getFuxinRank($timestamp) {
        $ret = [];
        $fuxingRankList = TurntableService::getInstance()->getFuxingRankList(0, 10, $timestamp);
        if (!empty($fuxingRankList)) {
            $userIds = array_keys($fuxingRankList);
            $userInfoMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);
            if (!empty($userInfoMap)) {
                for ($i = 0; $i < count($userIds); $i++) {
                    $userId = $userIds[$i];
                    $userInfo = ArrayUtil::safeGet($userInfoMap, $userId);
                    if (!empty($userInfo)) {
                        $userRank = $i + 1;
                        $headFrame = ArrayUtil::safeGet(self::$RANK_HEAD_FRAME_MAP, $userRank, '');
                        $ret[] = [
                            'userId' => $userInfo->userId,
                            'name' => $userInfo->nickname,
                            'prettyId' => $userInfo->prettyId,
                            'avatar' => CommonUtil::buildImageUrl($userInfo->avatar),
                            'score' => $fuxingRankList[$userId],
                            'rank' => $userRank,
                            'headFrame' => !empty($headFrame) ? CommonUtil::buildImageUrl($headFrame) : ''
                        ];
                    }
                }
            }
        }
        return $ret;
    }

    public function getFudiRankList($timestamp) {
        $ret = [];
        $fudiRankList = TurntableService::getInstance()->getFudiRankList(0, 50, $timestamp);
        if (!empty($fudiRankList)) {
            $roomIds = array_keys($fudiRankList);
            $roomInfos = QueryRoomDao::getInstance()->loadModelForRoomIds($roomIds);
            if (!empty($roomInfos)) {
                $roomInfoMap = [];
                foreach ($roomInfos as $roomInfo) {
                    $avatar = UserModelCache::getInstance()->findAvatarByUserId($roomInfo->userId);
                    $roomInfoMap[$roomInfo->roomId] = [$roomInfo, $avatar];
                }
                for ($i = 0; $i < count($roomIds); $i++) {
                    $roomId = $roomIds[$i];
                    $item = ArrayUtil::safeGet($roomInfoMap, $roomId);
                    if ($item) {
                        $roomInfo = $item[0];
                        $avatar = $item[1];
                        $ret[] = [
                            'roomId' => $roomInfo->roomId,
                            'prettyId' => $roomInfo->prettyRoomId,
                            'userId' => $roomInfo->userId,
                            'name' => $roomInfo->name,
                            'avatar' => !empty($avatar) ? CommonUtil::buildImageUrl($avatar) : '',
                            'score' => $fudiRankList[$roomId],
                            'rank' => $i + 1
                        ];
                    }
                }
            }
        }
        return $ret;
    }

	//砸蛋榜单
	public function rankList()
	{
		$timestamp = time();
        $userId = $this->checkMToken();

		$fuxinRankList = $this->getFuxinRank($timestamp);
//		$fudiRankList = $this->getFudiRankList($timestamp);

		list($rank, $score) = TurntableService::getInstance()->getFuxingRankScore($userId, $timestamp);

		$userInfo = UserModelCache::getInstance()->getUserInfo($userId);

		$userRank = $rank + 1;
        $headFrame = ArrayUtil::safeGet(self::$RANK_HEAD_FRAME_MAP, $userRank, '');

		return rjson([
		    'userRank' => $fuxinRankList,
//            'roomRank' => $fudiRankList,
            'my' => [
                'userId' => $userId,
                'prettyId' => !empty($userInfo) ? $userInfo->prettyId: $userId,
                'name' => !empty($userInfo) ? $userInfo->nickname : '',
                'rank' => $userRank,
                'score' => $score,
                'avatar' => !empty($userInfo) ? CommonUtil::buildImageUrl($userInfo->avatar) : '',
                'headFrame' => !empty($headFrame) ? CommonUtil::buildImageUrl($headFrame) : ''
            ]
        ]);
	}

	public function jinliRankList() {
        $pageNo = $this->request->param('pageNo', 0);
        $pageSize = $this->request->param('pageSize', 50);
        $turntableId = $this->request->param('turntableId', 0);

        list($total, $rankList) = TurntableService::getInstance()->getJinliRankList($turntableId, $pageNo * $pageSize, $pageSize);
        $rankDatas = [];

        if (!empty($rankList)) {
            foreach ($rankList as $rankData) {
                $userId = $rankData['userId'];
                $giftKind = GiftSystem::getInstance()->findGiftKind($rankData['giftId']);
                if ($giftKind != null) {
                    $rankDatas[] = [
                        'name' => UserModelCache::getInstance()->findNicknameByUserId($userId),
                        'gift' => [
                            'id' => $rankData['giftId'],
                            'name' => $giftKind->name,
                            'image' => CommonUtil::buildImageUrl($giftKind->image)
                        ],
                        'count' => $rankData['count'],
                        'time' => $rankData['time']
                    ];
                }
            }
        }

        return rjson([
            'total' => $total,
            'list' => $rankDatas
        ]);
    }

    public function encodeGiftBaolv($rewardPool) {
	    $ret = [];
	    foreach ($rewardPool->giftMap as $giftId => $weight) {
	        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
	        if ($giftKind) {
                $propb = (float)$weight / $rewardPool->totalWeight * 10000;
                $ret[] = [
                    $giftId,
                    round($propb, 2)
                ];
            }
        }
//        usort($ret, function($a, $b) {
//            if ($a[1] < [1]) {
//                return 1;
//            } else if ($a[1] > $b[1]) {
//                return -1;
//            }
//            return 0;
//        });
	    return $ret;
    }

    public function encodeBox($box) {
	    // 取最后一个奖池类型的第一个奖池
        $rewardPool = null;
        $typedPool = $box->findTypedPool(PoolTypes::$DAILY);
        if (!empty($typedPool)) {
            $rewardPool = $typedPool->rewardPools[0];
        }
	    return [
	        'turntableId' => $box->turntableId,
            'name' => $box->name,
            'price' => $box->price,
            'gifts' => $rewardPool != null ? $this->encodeGiftBaolv($rewardPool) : []
        ];
    }

    public function encodeGift($giftKind) {
        return [
            'name' => $giftKind->name,
            'image' => CommonUtil::buildImageUrl($giftKind->image),
            'value' => $giftKind->price ? $giftKind->price->count : 0,
        ];
    }
}