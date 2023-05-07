<?php


namespace app\domain\activity\confessionLove;

use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\service\UserInfoService;
use app\domain\user\UserRepository;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use Exception;
use think\facade\Db;
use think\facade\Log;

class ConfessionLoveService
{
    protected static $instance;
    protected $config_key = '520_activity_config';
    protected $redis = null;

    //单例
    public static function getInstance(): ConfessionLoveService
    {
        if (!isset(self::$instance)) {
            self::$instance = new ConfessionLoveService();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    /**
     * 获取页面内容
     * @param $userId
     */
    public function getPageInfo($userId): array
    {
        try {
            $config_key = $this->config_key;
            $config = $this->redis->get($config_key);
            $config = json_decode($config, true);
            if (time() < $config['start_time']) {
                throw new FQException('活动未开始', 500);
            }
            $res['is_end'] = time() > $config['end_time'];
            $res['heart_pool'] = $this->getPoolValue($config);
            $res['love_wall'] = $config['activity_gift'];
            $res['rank_list'] = $this->rankList($config, $userId);
            $res['timestamp'] = time();
            return $res;
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('SweetJourneyService getPageInfo userId=%d ex=%d:%s trace=%s',
                    $userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    public function getLoveWall($giftId)
    {
        try {
            $config_key = $this->config_key;
            $config = $this->redis->get($config_key);
            $config = json_decode($config, true);
            if (time() < $config['start_time']) {
                throw new FQException('活动未开始', 500);
            }
            $loveWallData = $this->loveWall($config, $giftId);
            return $this->dealLoveWallData($loveWallData, $config);
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('SweetJourneyService getLoveWall ex=%d:%s trace=%s',
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }

    }

    /**
     * 获取告白墙数据
     * @param $config
     */
    public function LoveWall($config, $giftId): array
    {
        $res = [];
        $time = time();
        $arr = [];
        switch ($giftId) {
            case 453:
                $res['gift_name'] = '浪漫花船';
                break;
            case 454:
                $res['gift_name'] = '比翼双飞';
                break;
            case 455:
                $res['gift_name'] = '带你私奔';
                break;
            default:
                break;
        }
        $listKey = $config['list_key'][$giftId];
        $allWallJson = $this->redis->lRange($listKey, 0, -1);
        if ($allWallJson) {
            foreach ($allWallJson as $val) {
                $listData = json_decode($val, true);
                if ($listData['endTime'] < $time) {
                    $this->redis->lPop($listKey);
                    $res['wallInfo'] = [];
                } else {
                    $res['wallInfo'] = $listData;
                    break;
                }
            }
        } else {
            $res['wallInfo'] = [];
        }
        return $res;
    }

    /**
     * 处理告白墙数据
     * @param $data
     * @param $config
     * @return array
     */
    public function dealLoveWallData($data, $config): array
    {
        $wall['gift_name'] = $data['gift_name'];
        if (!empty($data['wallInfo'])) {
            $userModel = UserModelCache::getInstance()->getUserInfo($data['wallInfo']['userId']);
            $wall['userId'] = $data['wallInfo']['userId'];
            $wall['userName'] = $userModel->nickname;
            $wall['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);
            $wall['startTime'] = $data['wallInfo']['startTime'];
            $wall['endTime'] = $data['wallInfo']['endTime'];
            $receive_key = sprintf('%s%s', date('Y'), $config['receive_list_key']);
            $wall['score'] = $this->redis->zScore($receive_key, $data['wallInfo']['userId']);
            $currentRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($data['wallInfo']['userId']);
            $wall['roomId'] = $currentRoomId == 0 ? $data['wallInfo']['roomId'] : $currentRoomId;
        } else {
            $wall['gift_name'] = $data['gift_name'];
            $wall['userId'] = 0;
            $wall['userName'] = '虚位以待';
            $wall['avatar'] = CommonUtil::buildImageUrl('');
            $wall['startTime'] = 0;
            $wall['endTime'] = 0;
            $wall['score'] = 0;
            $wall['roomId'] = 0;
        }
        $wall['timestamp'] = time();
        return $wall;
    }

    /**
     * 榜单内容
     */
    public function rankList($config, $userId): array
    {
        $receive_key = sprintf('%s%s', date('Y'), $config['receive_list_key']);
        $receive_list = $this->redis->zRevRange($receive_key, 0, 9, true);
        return $this->dealCurrentRanking($receive_list, $config, $userId); //当日实时排名数据
    }

    public function dealCurrentRanking($listUser, $config, $userId): array
    {
        $data = [];
        if (!empty($listUser)) {
            $randId = 1;
            foreach ($listUser as $user_id => $value) {
                $arr = [];
                $leftUserModel = UserModelCache::getInstance()->getUserInfo($user_id);
                $arr['left'] = [
                    'index' => $randId++,
                    'uid' => $user_id,
                    'avatar' => CommonUtil::buildImageUrl($leftUserModel->avatar),
                    'nickname' => $leftUserModel->nickname,
                    'totalcoin' => $value,
                ];
                $rightUserId = $this->redis->ZREVRANGE(sprintf('%s%s', date('Y'), sprintf($config['send_list_key'], $user_id)), 0, 0, true);
                $rightUserId = array_keys($rightUserId);
                $rightUserModel = UserModelCache::getInstance()->getUserInfo($rightUserId[0]);
                $arr['right'] = [
                    'uid' => (int)$rightUserId[0],
                    'avatar' => CommonUtil::buildImageUrl($rightUserModel->avatar),
                    'nickname' => $rightUserModel->nickname,
                ];
                $data['list'][] = $arr;
            }
        } else {
            $data['list'] = [];
        }
        //获取自己的排名
        $data['user'] = $this->queryUserInfo($userId, $config);

        return $data;
    }

    /**
     * 获取用户自己排名
     */
    public function queryUserInfo($userId, $config)
    {
        $receive_key = sprintf('%s%s', date('Y'), $config['receive_list_key']);
        $userinfo = [];
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        $num = $this->redis->ZREVRANK($receive_key, $userId);
        $userinfo['left']['index'] = $num === false ? 0 : $num + 1;
        $userinfo['left']['uid'] = $userModel->userId;
        $userinfo['left']['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);
        $userinfo['left']['nickname'] = $userModel->nickname;
        $totalcoin = $this->redis->zScore($receive_key, $userId);
        $userinfo['left']['totalcoin'] = $totalcoin === false ? 0 : (int)$totalcoin;
        return $userinfo;
    }


    /**
     * 生成榜单
     * @param $event
     * @param $config
     */
    public function generateRankList($event, $config)
    {
        $giftArr = $config['activity_gift'];
        if (in_array($event->giftKind->kindId, $giftArr) && $event->roomId > 0) {
            foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                $value = 0;
                foreach ($giftDetails as $giftDetail) {
                    $value += abs($giftDetail->deliveryGiftKind->deliveryCharm * $giftDetail->count);
                    //为爱告白榜单队列
                    $this->setLoveList($config, $event->giftKind->kindId, $receiveUser->userId, $giftDetail->count, $event->roomId, $event->timestamp);
                }
                $this->incrPool($config, $value);
                $this->onRankListChange($receiveUser->userId, $value, $config['receive_list_key']);
                $this->onRankListChange($event->fromUserId, $value, sprintf($config['send_list_key'], $receiveUser->userId));
            }
        }
    }

    /**
     * 奖池incr
     * @param $config
     * @param $value
     * @return void
     */
    private function incrPool($config, $value): void
    {
        $poolKey = sprintf('%s_%s', date('Y'), $config['pool_key']);
        $luck_star_pool_value = $this->redis->incr($poolKey, $value);
        Log::info('奖池增加后-----' . $luck_star_pool_value);
    }

    /**
     * 设置告白墙
     * @param $config
     * @param $giftId
     * @param $userId
     * @param $count
     */
    private function setLoveList($config, $giftId, $userId, $count, $roomId, $timestamp)
    {
        // rpush lpop
        $onRankTime = $config['on_rank_time'][$giftId];
        $listKey = $config['list_key'][$giftId];

        for ($i = 0; $i < $count; $i++) {
            if ($this->redis->llen($listKey) == 0) {
                $data = [
                    'userId' => $userId,
                    'roomId' => $roomId,
                    'startTime' => $timestamp,
                    'endTime' => ($onRankTime * 60 * 1) + $timestamp,
                ];
            } else {
                $lastWallJson = $this->redis->lRange($listKey, -1, -1);
                $lastWallData = json_decode($lastWallJson[0], true);
                $starTime = $lastWallData['endTime'] < $timestamp ? $timestamp : $lastWallData['endTime'];
                $data = [
                    'userId' => $userId,
                    'roomId' => $roomId,
                    'startTime' => $starTime,
                    'endTime' => ($onRankTime * 60 * 1) + $starTime,
                ];
            }
            $this->redis->rPush($listKey, json_encode($data));
        }
    }

    /**
     * 榜单值变化
     * @param $userId
     * @param $value
     */
    private function onRankListChange($userId, $value, $key)
    {
        $this->redis->zIncrBy(sprintf('%s%s', date('Y'), $key), $value, $userId);
    }


    /**
     * 瓜分任务
     */
    public function partitionTmpl()
    {
        $config_key = $this->config_key;
        $config = $this->redis->get($config_key);
        $config = json_decode($config, true);
//        $config = [
//            'end_time' => '1652716800',
//            'partition_time' => '2022-05-17',
//            'receive_list_key' => '520_receive_list',
//            'send_list_key' => '520_sendto_%d_list',
//            'pool_key' => 'heart_prize_pool',
//            'partition_rank' => '520_partition_list'
//        ];
        $time = time();
        if ($time >= $config['end_time'] && date('Y-m-d', $time) == $config['partition_time']) {
            $receive_key = sprintf('%s%s', date('Y'), $config['receive_list_key']);
            $receive_list = $this->redis->zRevRange($receive_key, 0, 9, true);

            foreach ($receive_list as $user_id => $value) {
                $rightUserId = $this->redis->ZREVRANGE(sprintf(sprintf('%s%s', date('Y'), $config['send_list_key']), $user_id), 0, 0, true);
                $rightUserId = array_keys($rightUserId);
                $arr = [$user_id, $rightUserId[0]];
                $data[] = $arr;
            }
            $poolRealValue = $this->getPoolValue($config);
            if (!empty($data)) {
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, sprintf('%s_%s', date('Y'),'520_love'), 'reward');
                foreach ($data as $k => $v) {
                    $addCoin = $this->partitionCoin($k, $poolRealValue);
                    foreach ($v as $value) {
                        if (!$this->redis->hExists(sprintf('%s%s', date('Y'), $config['partition_rank']), $value . '-' . $k)) {
                            Db::startTrans();
                            try {
                                $user = UserRepository::getInstance()->loadUser($value);
                                $bean = $user->getAssets()->getBean($time);
                                $bean->add($addCoin, $time, $biEvent);
                                $assetLists = $this->getAssetLists($k, $user);
                                if (!empty($assetLists)) {
                                    foreach ($assetLists as $assetList) {
                                        $user->getAssets()->add($assetList['assetId'], $assetList['count'], $time, $biEvent);
                                    }
                                }
                                $this->redis->hSet(sprintf('%s%s', date('Y'), $config['partition_rank']), $value. '-' . $k, $addCoin);
                                Db::commit();
                                //sendMsg
                                $this->sendMsg($k, $user, $addCoin, $value);
                            } catch (\Exception $e) {
                                Db::rollback();
                                Log::error(sprintf('ConfessionLoveService partitionTmpl error userId:%d ed:%d es:%s', $value, $e->getCode(), $e->getMessage()));
                            }
                        }
                    }
                }
            }
        }
    }

    private function partitionCoin($index, $poolValue)
    {
        $arr = [0.15, 0.1, 0.075, 0.04, 0.035, 0.03, 0.025, 0.02, 0.015, 0.01];
        $rate = $arr[$index];
        return floor($poolValue * $rate);
    }

    private function getAssetLists($index, $user) {
        $arr = [
            [
                'sex1' =>
                    [
                        [
                            "assetId" => "prop:207",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:240",
                            "count" => 1,
                        ]
                    ],
                'sex2' =>
                    [
                        [
                            "assetId" => "prop:208",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:240",
                            "count" => 1,
                        ]
                    ]
            ],
            [
                'sex1' =>
                    [
                        [
                            "assetId" => "prop:209",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:234",
                            "count" => 1,
                        ]
                    ],
                'sex2' =>
                    [
                        [
                            "assetId" => "prop:210",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:234",
                            "count" => 1,
                        ]
                    ]
            ],
            [
                'sex1' =>
                    [
                        [
                            "assetId" => "prop:213",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:437",
                            "count" => 1,
                        ]
                    ],
                'sex2' =>
                    [
                        [
                            "assetId" => "prop:214",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:437",
                            "count" => 1,
                        ]
                    ]
            ],
            [
                'sex1' =>
                    [
                        [
                            "assetId" => "prop:211",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:425",
                            "count" => 1,
                        ]
                    ],
                'sex2' =>
                    [
                        [
                            "assetId" => "prop:212",
                            "count" => 30,
                        ],
                        [
                            "assetId" => "gift:425",
                            "count" => 1,
                        ]
                    ]
            ]
        ];
        $sex  = $user->getUserModel()->sex;
        $sexIndex = $sex == 1 ? 'sex1' : 'sex2';
        if ($index >= 4) {
            $index = 3;
        }
        $arrIndex = ArrayUtil::safeGet($arr, $index);
        if (!empty($arrIndex)) {
            return ArrayUtil::safeGet($arrIndex, $sexIndex);
        }
        return null;
    }

    public function getPoolValue($config): int
    {
        $poolKey = sprintf('%s_%s', date('Y'), $config['pool_key']);
        $allValue = (int) $this->redis->get($poolKey);
        if ($allValue >= 0 && $allValue < 666667) {
            $poolValue = 50000;
        } elseif ($allValue >= 666667 && $allValue < 3333333) {
            $poolValue = 100000;
        } elseif ($allValue >= 3333333 && $allValue < 6666667) {
            $poolValue = 500000;
        } elseif($allValue >= 6666667 && $allValue < 33333333) {
            $poolValue = 1000000;
        } else {
            $poolValue = 5000000;
        }
        return $poolValue;
    }

    private function sendMsg($index, $user, $addCoin, $userId) {
        if ($index == 0) {
            $msg = ['msg' => sprintf('Hi！%s，恭喜你在520活动中成功进入榜单前%d，并获得了%d音豆的奖励及对应礼品，相关奖励已发放至您的账户，请查验～靓号将由客服人工发放，届时请注意接听来电～', $user->getUserModel()->nickname, $index+1, $addCoin)];
        } else {
            $msg = ['msg' => sprintf('Hi！%s，恭喜你在520活动中成功进入榜单前%d，并获得了%d音豆的奖励及对应礼品，相关奖励已发放至您的账户，请查验～', $user->getUserModel()->nickname, $index+1, $addCoin)];
        }
        YunxinCommon::getInstance()->sendMsg(config('config.fq_assistant'), 0, $userId, 0, $msg);
    }
}