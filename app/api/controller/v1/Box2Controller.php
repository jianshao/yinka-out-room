<?php
namespace app\api\controller\v1;
//砸蛋类
//
use app\BaseController;
use app\common\RedisCommon;
use app\domain\bank\BankAccountTypeIds;
use app\domain\bank\dao\BankAccountDao;
use app\domain\exceptions\FQException;
use app\domain\game\box2\Box2Service;
use app\domain\game\box2\Box2System;
use app\domain\game\box2\RunningRewardPoolDao;
use app\domain\game\GameService;
use app\domain\gift\GiftSystem;
use app\domain\user\dao\BeanModelDao;
use app\query\room\dao\QueryRoomDao;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
//
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//header('Access-Control-Allow-Methods: GET, POST, PUT');

class Box2Controller extends BaseController
{
    public static $RANK_HEAD_FRAME_MAP = [
        1 => '/banner/20200618/4396d4185f2d7ca9faee46f0afaa3bcc.png',
        2 => '/banner/20200618/04f441a7053554b0a272441b83161158.png',
        3 => '/banner/20200618/4ecbc2a980d547653253a66954b8c327.png'
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

    public function autoBuy() {
        $userId = $this->checkMToken();
        $autoBuy = intval($this->request->param('autoBuy'));
        try {
            GameService::getInstance()->setAutoBuy($userId, $autoBuy);
            return rjson(['autoBuy'=>$autoBuy]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function buyGoods() {
        $userId = $this->checkMToken();
        $count = intval($this->request->param('count'));
        $roomId = intval($this->request->param('roomId'));

        if ($count <= 0) {
            throw new FQException('数量错误', 500);
        }
        try {
            $bean = GameService::getInstance()->buyGoods($userId, $count, $roomId);
            return rjson([
                'score' => BankAccountDao::getInstance()->loadBankAccount($userId, BankAccountTypeIds::$GAME_SCORE),
                'bean' => $bean,
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 初始化
     * @return [type] [description]
     */
    public function init()
    {
        // 宝箱数据
        $boxes = [];
        // 所有宝箱礼物ID
        $giftIdMap = [];

        $userId = $this->checkMToken();

        $sortedBoxes = [];
        foreach (Box2System::getInstance()->boxMap as $boxId => $box) {
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
            $boxes[] = $this->encodeBox($box);
            foreach ($box->rewardPoolMap as $_ => $rewardPool) {
                foreach ($rewardPool->giftMap as $giftId => $_) {
                    $giftIdMap[$giftId] = 1;
                }
                if (!empty($box->specialConf)) {
                    foreach ($box->specialConf->giftIds as $giftId) {
                        $giftIdMap[$giftId] = 1;
                    }
                }

                $runningRewardPool = RunningRewardPoolDao::getInstance()->loadRewardPool($box->boxId, $rewardPool->poolId);
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
            'boxes' => $boxes
        ]);
    }

    /**
     * 计算进度
     *
     * @param $specialProgress
     * @param $box
     * @return float|int
     */
    public function calcSpecialProgress($box, $specialProgress) {
        $progress = 0;
        if (!empty($box->specialConf) && $box->specialConf->maxProgress > 0) {
            $progress = (float)$specialProgress * 100 / $box->specialConf->maxProgress;
            if ($progress > 100) {
                $progress = 100;
            }
        }
        return round($progress, 2);
    }

    /**
     * 开宝箱
     * @param string $num [次数]
     * @param string $type [1金宝箱 2银宝箱]
     */
    public function breakBox()
    {
        $boxId = intval($this->request->param('boxId'));
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
            list($totalPrice, $balance, $giftMap, $specialProgress, $specialGiftId) = Box2Service::getInstance()->breakBox($userId, $roomId, $boxId, $count, $autoBuy);

            if ($specialGiftId != null) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($specialGiftId);
                if ($giftKind) {
                    if (array_key_exists($specialGiftId, $giftMap)) {
                        $giftMap[$specialGiftId] += 1;
                    } else {
                        $giftMap[$specialGiftId] = 1;
                    }
                }
            }

            $gifts = [];
            foreach ($giftMap as $giftId => $count) {
                $gifts[] = [
                    'id' => $giftId,
                    'count' => $count
                ];
            }

            $specialGifts = [];
            if ($specialGiftId != null) {
                $specialGifts[] = ['id' => $specialGiftId, 'count' => 1];
            }

            $bean = BeanModelDao::getInstance()->loadBean($userId);

            return rjson([
                'bean' => $bean != null ? $bean->balance() : 0,
                'score' => $balance,
                'progress' => $this->calcSpecialProgress(Box2System::getInstance()->findBox($boxId), $specialProgress),
                'rewards' => [
                    'gifts' => $gifts,
//                    'special' => $specialGifts
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getFuxinRank($timestamp) {
        $ret = [];
        $fuxingRankList = Box2Service::getInstance()->getFuxingRankList(0, 10, $timestamp);
        if (!empty($fuxingRankList)) {
            $userIds = array_keys($fuxingRankList);
            $userInfoMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);
            if (!empty($userInfoMap)) {
                for ($i = 0; $i < count($userIds); $i++) {
                    $userId = $userIds[$i];
                    $userModel = ArrayUtil::safeGet($userInfoMap, $userId);
                    if (!empty($userModel)) {
                        $userRank = $i + 1;
                        $headFrame = ArrayUtil::safeGet(self::$RANK_HEAD_FRAME_MAP, $userRank, '');
                        $ret[] = [
                            'userId' => $userModel->userId,
                            'name' => $userModel->nickname,
                            'prettyId' => $userModel->prettyId,
                            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
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
        $fudiRankList = Box2Service::getInstance()->getFudiRankList(0, 10, $timestamp);
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
                        $roomModel = $item[0];
                        $avatar = $item[1];
                        $ret[] = [
                            'roomId' => $roomModel->roomId,
                            'prettyId' => $roomModel->prettyRoomId,
                            'userId' => $roomModel->userId,
                            'name' => $roomModel->name,
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
        $fudiRankList = $this->getFudiRankList($timestamp);

        list($rank, $score) = Box2Service::getInstance()->getFuxingRankScore($userId, $timestamp);

        $userModel = UserModelCache::getInstance()->getUserInfo($userId);

		$userRank = $rank + 1;
        $headFrame = ArrayUtil::safeGet(self::$RANK_HEAD_FRAME_MAP, $userRank, '');

		return rjson([
		    'userRank' => $fuxinRankList,
            'roomRank' => $fudiRankList,
            'my' => [
                'userId' => $userId,
                'prettyId' => !empty($userModel) ? $userModel->prettyId : $userId,
                'name' => !empty($userModel) ? $userModel->nickname : '',
                'rank' => $userRank,
                'score' => $score,
                'avatar' => !empty($userModel) ? CommonUtil::buildImageUrl($userModel->avatar) : '',
                'headFrame' => !empty($headFrame) ? CommonUtil::buildImageUrl($headFrame) : ''
            ]
        ]);
    }

    public function jinliRankList() {
        $pageNo = $this->request->param('pageNo', 0);
        $pageSize = $this->request->param('pageSize', 20);
        $boxId = $this->request->param('boxId', 0);

        list($total, $rankList) = Box2Service::getInstance()->getJinliRankList($boxId, $pageNo * $pageSize, $pageSize);
        $rankDatas = [];

        if (!empty($rankList)) {
            $userIdMap = [];
            foreach ($rankList as $rankData) {
                $userIdMap[$rankData['userId']] = 1;
            }
            $userIds = array_keys($userIdMap);
            $userInfoMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            foreach ($rankList as $rankData) {
                $userId = $rankData['userId'];
                $giftKind = GiftSystem::getInstance()->findGiftKind($rankData['giftId']);
                if ($giftKind != null && !empty(ArrayUtil::safeGet($userInfoMap, $userId))) {
                    $rankDatas[] = [
                        'name' => $userInfoMap[$userId]->nickname,
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

    //宝箱进度
    public function boxInfo()
    {
        $boxId = Request::param('boxId');
        try {
            $progress = Box2Service::getInstance()->getSpecialProgressRate($boxId);
            return rjson([
                'progress' => $progress
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
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
        usort($ret, function($a, $b) {
            if ($a[1] < $b[1]) {
                return 1;
            } else if ($a[1] > $b[1]) {
                return -1;
            }
            return 0;
        });
        return $ret;
    }

    public function encodeBox($box) {
        // 取最后一个奖池类型的第一个奖池
        $rewardPool = null;
        if (count($box->typedPoolList) > 0) {
            $rewardPool = $box->typedPoolList[count($box->typedPoolList)-1]->rewardPools[0];
        }
        return [
            'boxId' => $box->boxId,
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