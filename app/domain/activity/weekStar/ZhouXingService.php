<?php

namespace app\domain\activity\weekStar;

use app\common\RedisCommon;
use app\domain\gift\GiftSystem;
use app\domain\user\dao\UserModelDao;
use app\utils\CommonUtil;
use think\facade\Log;


class ZhouXingService
{

    public static $comtributeCachekey = 'weekstar_comtribute_ranking_%s_%s';
    public static $incomeCachekey = 'weekstar_income_ranking_%s_%s';

    protected static $instance;
    public $TheStartTime = '2021-02-01 00:00:00'; //活动开始时间
    protected $weekStarUid_ = 'weekStarUid_';
    protected $weekStarToUid_ = 'weekStarToUid_';
    protected $avatar = 'Album/103/1608631213332.jpg';
    protected $thisAvatar = '';
    public static $type = [1 => '万千宠爱', 2 => '君临天下', 3 => '荣誉星耀', 4 => '周星礼物'];

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhouXingService();
        }
        return self::$instance;
    }

    /**
     * 获取周星榜单
     * @param $uid
     * @return array
     */
    public function weekStarQuery($uid)
    {
        $week = date('oW',time()); //获取今天是今年的第几周
        $last_monday = date('Y-m-d', strtotime('monday last week')); //上周一
        $weekStarUid = $this->weekStarUid_ . $last_monday . ' 00:00:00'; // 财富榜key
        $weekStarToUid = $this->weekStarToUid_ . $last_monday . ' 00:00:00'; // 魅力榜key
        $gift_id = $this->getWeekGiftId(); //获取周星礼物
        $list = [];
        /* 上周贡献前三 */
        $redis = RedisCommon::getInstance()->getRedis();
        if ($redis->get($weekStarUid)) {
            $uidList = array_slice(json_decode($redis->get($weekStarUid), true), 0, 3); //周星：财富榜
            $weekStar = $this->userAvatar($uidList, '富豪');
            $list['上周豪气之王'] = $weekStar;
        }
        /* 上周收益前三 */
        if ($redis->get($weekStarToUid)) {
            $touidList = array_slice(json_decode($redis->get($weekStarToUid), true), 0, 3); //周星：魅力榜
            $weekStarToUid = $this->userAvatar($touidList, '闪耀');
            $list['上周闪耀之星'] = $weekStarToUid;
        }
        $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
        //用户信息
        $userModel = UserModelDao::getInstance()->loadUserModel($uid);
        if (!empty($userModel->avatar)) {
            $this->thisAvatar = CommonUtil::buildImageUrl($userModel->avatar);
        }
        /* 本周贡献前十 */
        $weekStarGxList = $redis->zRevRange(sprintf(self::$comtributeCachekey,$gift_id,$week),0,9,true);
        $list['本号君临天下'] = $this->uidListNew($userModel,$weekStarGxList);
        $list['豪气之王'] = $this->userWeekStarAvatar($weekStarGxList, '富豪');

        /* 本周收益前十 */
        $weekStarSyList = $redis->zRevRange(sprintf(self::$incomeCachekey,$gift_id,$week),0,9,true);
        $list['本号万千宠爱'] = $this->touidListNew($userModel,$weekStarSyList);
        $list['闪耀之星'] = $this->userWeekStarAvatar($weekStarSyList, '闪耀');

        /*  四周积分排行  */
        $monthList = array_slice($this->monthRank(time() - (7 * 86400), strtotime($this->TheStartTime)), 0, 10);
        $list['本号番茄星耀'] = $this->monthListNew($userModel, $monthList);
        $list['番茄星耀'] = $this->userAvatar($monthList, '闪耀');
        /* 各排行奖品 */
        $weekGiftInfo = $this->getWeekGift($gift_id);
        $list['周星礼物'] = $weekGiftInfo;
        $rewards = ZhouXingSystem::getInstance()->getRewards($weekGiftInfo);
        $list['闪耀之星奖励'] = $rewards['charm'];
        $list['豪气之王奖励'] = $rewards['rich'];
        $list['番茄星耀奖励'] = $rewards['honor'];
        return $list;
    }

    /**
     * 获取礼物信息
     * @param $gift_id
     * @return array
     */
    public function getWeekGift($gift_id)
    {
        $giftKind = GiftSystem::getInstance()->findGiftKind($gift_id);
        return empty($giftKind) ? ['image'=>'','name'=>'周星礼物','giftId'=>0] : ['image'=>CommonUtil::buildImageUrl($giftKind->image),'name'=>$giftKind->name,'giftId'=>$gift_id];
    }

    /*
     * 本号番茄星耀
     */
    protected function monthListNew($userModel, $monthList)
    {
        //月度榜
        $data = [
            'uid' => (int) $userModel->userId,
            '积分' => 0,
            'num' => '未上榜',
            'avatar' => $this->thisAvatar,
            'nickname' => $userModel->nickname,
        ];

        if ($monthList) {
            foreach ($monthList as $k => $v) {
                if ($v['uid'] == $userModel->userId) {
                    $data['积分'] = $v['integral'];
                    $data['num'] = $k + 1;
                }
                $data['uid'] = (int) $userModel->userId;
                $data['avatar'] = $this->thisAvatar;
                $data['nickname'] = $userModel->nickname;
            }
        }
        return $data;
    }

    public function monthRank($curTime, $activityStartTime)
    {
        $weekKeys = $this->calcWeekKeys($curTime, $activityStartTime);
        $redis = RedisCommon::getInstance()->getRedis();
        $userIdScoreList = [];
        $coin = [];
        foreach ($weekKeys as $weekKey) {
            $weekUserIdScoreList = $redis->zRevRange('weekStar_' . $weekKey, 0, 9, true);
            if (!empty($weekUserIdScoreList)) {
                foreach ($weekUserIdScoreList as $userId => $score) {
                    if (array_key_exists($userId, $userIdScoreList)) {
                        $userIdScoreList[$userId] = $userIdScoreList[$userId] + $score;
                    } else {
                        $userIdScoreList[$userId] = $score;
                    }
                }
            }
            $weekStar = $redis->get('weekStarToUid_' . $weekKey);
            $weekStar = json_decode($weekStar, true);
            if (!empty($weekStar)) {
                foreach ($weekStar as $key => $value) {
                    if (array_key_exists($value['uid'], $coin)) {
                        $coin[$value['uid']] = $coin[$value['uid']] + $value['coin'];
                    } else {
                        $coin[$value['uid']] = $value['coin'];
                    }
                }
            }
        }

        // 排序
        $ret = [];
        foreach ($userIdScoreList as $userId => $score) {
            $ret[] = [
                'uid' => (int) $userId,
                'integral' => (int) $score,
                'coin' => (int) array_key_exists($userId, $coin) ? $coin[$userId] : 0,
            ];
        }
        return $this->sortArray($ret, 'integral', 'coin', SORT_DESC);
    }

    public function sortArray($array, $key, $key2, $sort)
    {
        $paiKey = array_column($array, $key);
        $paiKey2 = array_column($array, $key2);
        array_multisort($paiKey, $sort, $paiKey2, $sort, $array);
        return $array;
    }

    public function calcWeekKeys($curTime, $activityStartTime)
    {
        $weekSeconds = 86400 * 7;
        $monthStartTime = $this->calcMonthStartTime($curTime, $activityStartTime);
        $weekKeys = [];
        for ($i = 0; $i < 4; $i++) {
            $weekKeys[] = date('Y-m-d 00:00:00', $monthStartTime + $weekSeconds * $i);
        }
        return $weekKeys;
    }

    //得到月的开始时间
    public function calcMonthStartTime($timestamp, $activityStartTime)
    {
        $cycle = 4 * 7 * 86400;
        $theStartMondayTime = $this->calcWeekStart($timestamp); //计算当前时间戳周一的开始时间
        $activityStartMondayTime = $this->calcWeekStart($activityStartTime); //计算活动的周开始时间
        $nCycle = floor(($theStartMondayTime - $activityStartMondayTime) / $cycle); //距离活动开始时间过去多少轮次
        return $activityStartMondayTime + ($nCycle * $cycle);
    }

    //获取指定时间的周一时间戳
    public function calcWeekStart($timestamp)
    {
        return strtotime('monday this week', $timestamp);
    }

    /**
     * 万千宠爱 收礼
     * @param $userModel
     * @param $userList
     * @return array
     */
    protected function touidListNew($userModel,$userList)
    {
        $data = [
            'uid' => (int) $userModel->userId,
            '闪耀' => 0,
            'num' => '未上榜',
            'avatar' => $this->thisAvatar,
            'nickname' => $userModel->nickname,
        ];
        if (!empty($userList)) {
            $ranking = 0;
            foreach ($userList as $k => $v) {
                $ranking++;
                if ($userModel->userId == $k) {
                    $data['num'] = $ranking;
                    $data['闪耀'] = $v;
                }
                $data['uid'] = (int) $userModel->userId;
                $data['avatar'] = $this->thisAvatar;
                $data['nickname'] = $userModel->nickname;
            }
        }
        return $data;
    }

    /**
     * @param $data
     * @param $type
     * @return mixed
     * 用户头像和昵称
     */
    public function userWeekStarAvatar($data,$type='')
    {
        $ret = [];
        if ($data) {
            $i = 0;
            foreach ($data as $k => $v) {
                $retInfo = [];
                $userModel = UserModelDao::getInstance()->loadUserModel($k);
                $avatar = empty($userModel->avatar) ? $this->avatar : $userModel->avatar;
                $retInfo['uid'] = $k;
                $retInfo['avatar'] = CommonUtil::buildImageUrl($avatar);
                $retInfo['nickname'] = empty($userModel->nickname) ? '没有昵称的用户' : $userModel->nickname;
                $retInfo['num'] = ++$i;
                $retInfo[$type] = (string)$v;
                $ret[] = $retInfo;
            }
        }
        return $ret;
    }

    protected function uidListNew($userModel, $userList)
    {
        $data = [
            'uid' => (int) $userModel->userId,
            '富豪' => 0,
            'num' => '未上榜',
            'avatar' => $this->thisAvatar,
            'nickname' => $userModel->nickname,
        ];

        if (!empty($userList)) {
            $ranking = 0;
            foreach ($userList as $k => $v) {
                $ranking++;
                if ($userModel->userId == $k) {
                    $data['num'] = $ranking;
                    $data['富豪'] = $v;
                }
                $data['uid'] = (int) $userModel->userId;
                $data['avatar'] = $this->thisAvatar;
                $data['nickname'] = $userModel->nickname;
            }
        }
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     * 用户头像和昵称
     */
    public function userAvatar($data, $type = '')
    {
        if ($data) {
            foreach ($data as $k => &$v) {
                if (isset($v['uid'])) {
                    $userModel = UserModelDao::getInstance()->loadUserModel($v['uid']);
                    $avatar = empty($userModel->avatar) ? $this->avatar : $userModel->avatar;
                    $v['avatar'] = CommonUtil::buildImageUrl($avatar);
                    $v['nickname'] = empty($userModel->nickname) ? '没有昵称的用户' : $userModel->nickname;
                    $v['num'] = "未上榜";
                    if ($k <= 10) {
                        $v['num'] = $k + 1;
                    }
                    if (isset($v['integral'])) {
                        $v['积分'] = $v['integral'];
                        unset($v['integral']);
                    }
                    if (isset($v['coin'])) {
                        $v[$type] = $v['coin'];
                        unset($v['coin']);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 处理豪气之王、闪耀之星 周榜单数据
     * @param $event
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addWeekStarInfo($event)
    {
        $gift_id = $this->getWeekGiftId(); //获取周星礼物
        if(empty($gift_id) || $event->giftKind->kindId != $gift_id){
            Log::info(sprintf('WeekStarService.WeekStarRanking sendGiftId=%d, weekStarGiftid=%s',
                $event->giftKind->kindId,$gift_id));
            return;
        }
        //获取今天是今年的周几（用于周榜）
        $week = date('oW',$event->timestamp);
        $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);

        //贡献周榜数据 金额=礼物金额*礼物数量*赠送人数
        $redis->zIncrBy(sprintf('weekstar_comtribute_ranking_%s_%s',$gift_id,$week),$event->giftKind->price->count*$event->count*count($event->receiveUsers),$event->fromUserId);

        //循环处理接收礼物的主播
        foreach ($event->receiveUsers as $receiveUser) {
            //收入周榜数据 金额=礼物金额*礼物数量
            $redis->zIncrBy(sprintf('weekstar_income_ranking_%s_%s',$gift_id,$week),$event->giftKind->price->count*$event->count,$receiveUser->userId);
        }
    }

    /**
     * 获取周星礼物ID
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWeekGiftId()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $frequency = $redis->get('frequency');
        $count = ZhouXingSystem::getInstance()->getGiftNumber();
        $gifts = ZhouXingSystem::getInstance()->gifts;
        if(empty($gifts)){
            return 0;
        }
        if($frequency){
            if(is_int($frequency / $count)){
                $giftId = end($gifts);
            }else{
                $frequency = $this->giftKey($frequency,$count);
                $giftId = $gifts[$frequency-1];
            }
            return $giftId;
        }else{
            $redis->set('frequency',1);
            $giftId = $gifts[0];
            return $giftId;
        }
    }

    public function giftKey($frequency,$count)
    {
        for ( $i = 0; $i <= $frequency; $i++){
            if($count * $i < $frequency){
                $key = $count * $i;
            }
        }
        return $frequency - $key;
    }

    public function getKey($key,$array)
    {
        foreach ($array as $k => $v){
            if($v == $key){
                return $k;
            }
        }
    }
}