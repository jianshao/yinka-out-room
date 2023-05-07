<?php
namespace app\api\controller\v1;
//砸蛋类
//
use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\qixi\Config;
use app\domain\activity\qixi\QixiService;
use app\domain\activity\qixi\QixiUser;
use app\domain\activity\qixi\QixiUserDao;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\model\UserModel;
use app\query\user\cache\UserModelCache;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;
//
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
//header('Access-Control-Allow-Methods: GET, POST, PUT');

class QixiController extends BaseController
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

    /**
     * 初始化
     * @return [type] [description]
     */
    public function init()
    {
        $userId = $this->checkMToken();

//        if (QixiService::getInstance()->isQixiExpire()){
//            throw new FQException("活动已过期",500);
//        }

        $timestamp = time();
        $qixiUser = QixiUserDao::getInstance()->loadQixiUser($userId, $timestamp);

        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        $selfUser = $this->encodeUser($userModel);

        $cpUser = null;
        if ($qixiUser->cpUserId){
            $userModel = UserModelCache::getInstance()->getUserInfo($qixiUser->cpUserId);
            $cpUser = $this->encodeUser($userModel);
        }

        #福袋
        $fudaiList = [];
        $config = Config::loadQiXiConf();
        foreach (ArrayUtil::safeGet($config, 'bags', []) as $bagConf){
            $giftKind = GiftSystem::getInstance()->findGiftKind($bagConf['giftId']);
            if (empty($giftKind))continue;
            $userBag = ArrayUtil::safeGet($qixiUser->luckyBag, $bagConf['giftId']);
            if ($userBag){
                if ($userBag[1] > 0){
                    $status = 2;
                }else{
                    $status = $userBag[0] >= $bagConf['count'] ? 1 : 0;
                }
            }else{
                $status = 0;
            }
            $fudaiList[] = [
                'giftId' => $giftKind->kindId,
                'giftName' => $giftKind->name,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
                'total' => $bagConf['count'],
                'cur' => $userBag ? $userBag[0] : 0,
                'status' => $status, # 2是已领取 1可领取 0不可领取
                'reward' => $this->encodeReward($bagConf['reward'])
            ];
        }

        # 鹊桥榜
        $queqiaoRank = $this->getQueQiaoRank($userId);

        return rjson([
            'startTime' => $config['startTime'],
            'stopTime' => $config['stopTime'],
            'missingValue' => $qixiUser->missingValue,
            'isPopRemoveCP' => $qixiUser->cpStatus == QixiUser::$CP_STATUS_REMOVED,
            'selfUser' => $selfUser,
            'cpUser' => $cpUser,
            'fudaiList' => $fudaiList,
            'queqiaoRank' => $queqiaoRank
        ]);
    }

    public function applyList()
    {
        $userId = $this->checkMToken();

        if (QixiService::getInstance()->isQixiExpire()){
            throw new FQException("活动已过期",500);
        }

        $timestamp = time();
        $qixiUser = QixiUserDao::getInstance()->loadQixiUser($userId, $timestamp);

        # 我申请别人的
        $applyList = [];
        foreach ($qixiUser->applyList as $apply){
            $userModel = UserModelCache::getInstance()->getUserInfo($apply->appliedUid);
            $applyList[] = $this->encodeUser($userModel);
        }

        return rjsonFit([
            'applyList' => $applyList,
        ]);
    }

    public function appliedList()
    {
        $userId = $this->checkMToken();

        if (QixiService::getInstance()->isQixiExpire()){
            throw new FQException("活动已过期",500);
        }

        $timestamp = time();
        $qixiUser = QixiUserDao::getInstance()->loadQixiUser($userId, $timestamp);

        # 别人申请我的
        $appliedList = [];
        $userIds = [];
        foreach ($qixiUser->appliedList as $apply){
            $userIds[] = $apply->applyUid;
        }
        $userMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);
        foreach ($qixiUser->appliedList as $apply){
            $appliedList[] = $this->encodeUser($userMap[$apply->applyUid]);
        }

        return rjsonFit([
            'appliedList' => $appliedList,
        ]);
    }

    private function encodeUser(UserModel $userModel){
        return [
            'userId' => $userModel->userId,
            'name' => $userModel->nickname,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar)
        ];
    }

    private function encodeReward($reward){
        $ret = [];
        foreach ($reward['randoms'] as $item){
            $assetKind = AssetSystem::getInstance()->findAssetKind($item['assetId']);
            $ret[] = [
                'name' => $assetKind->displayName,
                'image' => CommonUtil::buildImageUrl($assetKind->image),
                'count' => $item['count'],
            ];
        }

        return $ret;
    }

    public function getQueQiaoRank($selfUserId) {
        $ret = [];
        $selfInfo = null;
        $selfCpUserId = QixiUserDao::getInstance()->getCP($selfUserId);

        $rankList = QixiService::getInstance()->getQueQiaoRankList(0, 20);
        if (!empty($rankList)) {
            $userIdMap = [];
            foreach (array_keys($rankList) as $key => $userId){
                $cpUserId = QixiUserDao::getInstance()->getCP($userId);
                if (empty($cpUserId) || count($userIdMap) >= 10){
                    continue;
                }
                $userIdMap[$userId] = $cpUserId;
            }

            $userIds = array_keys($userIdMap);
            $userMap = UserModelCache::getInstance()->findUserModelMapByUserIds(array_merge($userIds,array_values($userIdMap)));

            for ($i = 0; $i < count($userIds); $i++) {
                $userRank = $i + 1;
                $userId = $userIds[$i];

                $data = [
                    'user' => $this->encodeUser($userMap[$userId]),
                    'cpUser' => $this->encodeUser($userMap[$userIdMap[$userId]]),
                    'rank' => $userRank,
                    'score' => $rankList[$userId]
                ];
                $ret[] = $data;

                if ($userId == $selfUserId || $userId==$selfCpUserId){
                    $selfInfo = $data;
                }
            }
        }

        if ($selfInfo == null){
            $userModel = UserModelCache::getInstance()->getUserInfo($selfUserId);
            $selfInfo = [
                'user' => $this->encodeUser($userModel),
                'rank' => -1,
                'score' => QixiUserDao::getInstance()->getMissingValue($selfUserId)
            ];

            if (!empty($selfCpUserId)){
                $userModel = UserModelCache::getInstance()->getUserInfo($selfCpUserId);
                $selfInfo['cpUser'] = $this->encodeUser($userModel);
            }
        }

        return [
            'rankList' => $ret,
            'selfRank' => $selfInfo
        ];
    }

    public function applyCP(){
        $userId = $this->checkMToken();
        $appliedUid = $this->request->param('appliedUid');
        try {
            if (QixiService::getInstance()->isQixiExpire()){
                throw new FQException("活动已过期",500);
            }

            if ($userId == $appliedUid){
                throw new FQException("不能邀请自己",500);
            }

            QixiService::getInstance()->applyCP($userId, $appliedUid);
            return rjson([
                'applyUid' => $userId,
                'appliedUid' => $appliedUid
            ]);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function replyApplyCP(){
        $userId = $this->checkMToken();
        $applyUid = intval($this->request->param('applyUid'));
        $reply = intval($this->request->param('reply'));
        try {
            if (QixiService::getInstance()->isQixiExpire()){
                throw new FQException("活动已过期",500);
            }

            QixiService::getInstance()->replyApplyCP($userId, $applyUid, $reply);
            return rjson([
                'userId' => $userId,
                'applyUid' => $applyUid,
                'reply' => $reply
            ]);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function applyRemoveCP(){
        $userId = $this->checkMToken();
        try {
            if (QixiService::getInstance()->isQixiExpire()){
                throw new FQException("活动已过期",500);
            }

            QixiService::getInstance()->applyRemoveCP($userId);
            return rjson([
                'userId' => $userId
            ]);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function replyRemoveCP(){
        $userId = $this->checkMToken();
        $reply = intval($this->request->param('reply'));
        try {
            if (QixiService::getInstance()->isQixiExpire()){
                throw new FQException("活动已过期",500);
            }

            QixiService::getInstance()->replyRemoveCP($userId, $reply);
            return rjson([
                'userId' => $userId,
                'reply' => $reply
            ]);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function openFuDaiGift(){
        $userId = $this->checkMToken();
        $giftId = intval($this->request->param('giftId'));
        try {
            if (QixiService::getInstance()->isQixiExpire()){
                throw new FQException("活动已过期",500);
            }

            $rewardItem = QixiService::getInstance()->openFuDaiGift($userId, $giftId);
            $assetKind = AssetSystem::getInstance()->findAssetKind($rewardItem->assetId);
            if (AssetUtils::isGiftAsset($assetKind->kindId)){
                $name = $assetKind->displayName . '*'. $rewardItem->count;
            }else{
                $name = $assetKind->displayName . '777*'. ($rewardItem->count/777);
            }

            $msg = "恭喜您，在七夕盲盒活动中获得".$assetKind->displayName;
            $msg = $msg.(QixiService::getInstance()->isLastDay() ? "，明年七夕再见，么么哒" : "，明天再来哦～");
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ["msg" => $msg]]);
            Log::info(sprintf("QixiService openFuDaiGift result userId=%d resMsg=%s", $userId, $resMsg));

            return rjson([
                'name' => $name ,
                'image' => CommonUtil::buildImageUrl($assetKind->image),
            ]);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}