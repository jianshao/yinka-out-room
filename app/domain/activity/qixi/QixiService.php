<?php


namespace app\domain\activity\qixi;

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\UserModelDao;
use app\service\LockService;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class QixiService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QixiService();
        }
        return self::$instance;
    }

    public function buildQueQiaoRankKey(){
        return 'qixi_queqiao';
    }

    /*
     * 申请cp
     * $applyUid 申请人id
     * $appliedUserId 被申请人id
     * */
    public function applyCP($applyUid, $appliedUid){
        $timestamp = time();
        $appliedUser = QixiUserDao::getInstance()->loadQixiUser($appliedUid, $timestamp);
        if (!empty($appliedUser->cpUserId)){
            throw new FQException("此用户已配对成功，请重新配对",500);
        }

        $applyUser = QixiUserDao::getInstance()->loadQixiUser($applyUid, $timestamp);
        if (!empty($applyUser->cpUserId)){
            throw new FQException("您已有CP，不可以再发起配对",500);
        }

        $userMap = UserModelDao::getInstance()->findUserModelMapByUserIds([$applyUid, $appliedUid]);

        if (empty($userMap[$appliedUid])){
            throw new FQException("没有该用户，请重新配对~",500);
        }

        if ($applyUser->getApply($appliedUid) != null){
            throw new FQException("您已对".$userMap[$appliedUid]->nickname."发起CP配对，请等待",500);
        }

        if (count($applyUser->applyList) >= 3){
            throw new FQException("可以不这么渣么~",500);
        }

        $msg = $userMap[$applyUid]->nickname."对您发起CP配对，请回复";
        # 申请记录
        $cpApply = new CPApply();
        $cpApply->applyUid = $applyUid;
        $cpApply->appliedUid = $appliedUid;
        $cpApply->applyTime = $timestamp;

        # 申请人添加申请记录
        $applyUser->applyList[] = $cpApply;
        QixiUserDao::getInstance()->hMSetUser($applyUid, $applyUser->applyToJson());

        # 被申请人添加被申请记录
        $appliedUser->appliedList[] = $cpApply;
        QixiUserDao::getInstance()->hMSetUser($appliedUid, $appliedUser->applyToJson());

        Log::info(sprintf("QixiService applyCP applyUid=%d appliedUid=%d", $applyUid, $appliedUid));

        # 被申请人官方小秘书提示
        $msg = ["msg" => $msg];
        //queue YunXinMsg
        $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $appliedUid, 'type' => 0, 'msg' => $msg]);
        Log::info(sprintf("QixiService applyCP result userId=%d resMsg=%s", $applyUid, $resMsg));

        return $cpApply;
    }

    /*
     * 回复申请cp
     * $appliedUid 被申请人也就是回复人
     * $applyUid 申请人
     * $reply 1-同意 2-拒绝
     * */
    public function replyApplyCP($appliedUid, $applyUid, $reply){
        $timestamp = time();
        $appliedUser = QixiUserDao::getInstance()->loadQixiUser($appliedUid, $timestamp);
        if (!empty($appliedUser->cpUserId) && $reply == 1){
            throw new FQException("您已有cp, 不可以再同意别人的配对",500);
        }

        $applyUser = QixiUserDao::getInstance()->loadQixiUser($applyUid, $timestamp);
        if (!empty($applyUser->cpUserId)){
            throw new FQException("该用户已有CP，请配对其他用户",500);
        }

        $cpApply = $applyUser->getApply($appliedUid);
        if (empty($cpApply)){
            throw new FQException("没有该申请",500);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($appliedUid);

        // 用户加锁
        $lockKey1 = "qixi_user_apply".$applyUid;
        LockService::getInstance()->lock($lockKey1);

        // 用户加锁
        $lockKey2 = "qixi_user_apply".$appliedUid;
        LockService::getInstance()->lock($lockKey2);
        try {
            if ($reply == 1){
                $msg = ["msg" => $userModel->nickname."同意了您的CP配对"];
                # 同意申请之后 双方有cp 清除申请被申请记录

                $applyUser->cpUserId = $appliedUid;
                $applyUser->appliedList = [];
                $applyUser->applyList = [];

                $appliedUser->cpUserId = $applyUid;
                $appliedUser->appliedList = [];
                $appliedUser->applyList = [];

            }else{
                $msg = ["msg" => $userModel->nickname."拒绝了您的CP配对"];
                # 不同意申请之后 双方清除该条申请被申请记录
                $applyUser->removeApply($cpApply);
                $appliedUser->removeApplied($cpApply);
            }

            QixiUserDao::getInstance()->hMSetUser($applyUid, $applyUser->applyToJson());

            QixiUserDao::getInstance()->hMSetUser($appliedUid, $appliedUser->applyToJson());

            Log::info(sprintf("QixiService replyApplyCP applyUid=%d appliedUid=%d reply=%d", $applyUid, $appliedUid, $reply));
        }
        finally {
            LockService::getInstance()->unlock($lockKey1);
            LockService::getInstance()->unlock($lockKey2);
        }

        // # 申请人官方小秘书提示
        $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $applyUid, 'type' => 0, 'msg' => $msg]);
        Log::info(sprintf("QixiService replyApplyCP result userId=%d resMsg=%s", $appliedUid, $resMsg));

        return $cpApply;
    }

    /*
     * 申请解除cp
     * */
    public function applyRemoveCP($userId){
        $cpUserId = QixiUserDao::getInstance()->getCP($userId);
        if (empty($cpUserId)){
            throw new FQException("您还没有cp, 请配对其他用户",500);
        }

        QixiUserDao::getInstance()->hMSetUser($userId, ['cpStatus'=>QixiUser::$CP_STATUS_REMOVE]);
        QixiUserDao::getInstance()->hMSetUser($cpUserId, ['cpStatus'=>QixiUser::$CP_STATUS_REMOVED]);

        Log::info(sprintf("QixiService applyRemoveCP userId=%d", $userId));
    }

    /*
     * 回复解除cp申请
     * $reply 1-同意 2-拒绝
     * */
    public function replyRemoveCP($userId, $reply){
        $data = QixiUserDao::getInstance()->hMGetUser($userId, ['cpUserId', 'cpStatus']);
        $cpUserId = $data['cpUserId'];
        $cpStatus = $data['cpStatus'];
        if (empty($cpUserId)){
            throw new FQException("您还没有cp, 请配对其他用户",500);
        }

        if ($cpStatus != 2){
            throw new FQException("没有该解除CP申请或者已解除",500);
        }

        $nickname = UserModelDao::getInstance()->findNicknameByUserId($userId);
        if ($reply == 1){
            $msg = ["msg" => $nickname."已同意解除CP, 下一个会更好，奥利给"];

            QixiUserDao::getInstance()->hMSetUser($userId, ['cpUserId'=>0, 'cpStatus'=>0, 'missingValue'=>0]);
            QixiUserDao::getInstance()->hMSetUser($cpUserId, ['cpUserId'=>0, 'cpStatus'=>0, 'missingValue'=>0]);

            $redis = RedisCommon::getInstance()->getRedis();
            $key = $this->buildQueQiaoRankKey();
            $redis->zRem($key, $cpUserId);
            $redis->zRem($key, $userId);
        }else{
            $msg = ["msg" => $nickname."不同意解除CP, 爱真的会消失吗？"];

            QixiUserDao::getInstance()->hMSetUser($userId, ['cpStatus'=>0]);
            QixiUserDao::getInstance()->hMSetUser($cpUserId, ['cpStatus'=>0]);
        }

        Log::info(sprintf("QixiService replyRemoveCP userId=%d cpUserId=%d reply=%d", $userId, $cpUserId, $reply));

        //queue YunXinMsg
        $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $cpUserId, 'type' => 0, 'msg' => $msg]);
        Log::info(sprintf("QixiService replyRemoveCP result userId=%d resMsg=%s", $userId, $resMsg));
    }

    public function openFuDaiGift($userId, $giftId){
        $config = Config::loadQiXiConf();
        $bags = ArrayUtil::safeGet($config, 'bags', []);
        $fudaiGiftMap = [];
        foreach ($bags as $fudai){
            $fudaiGiftMap[$fudai['giftId']] = $fudai;
        }

        if (!array_key_exists($giftId, $fudaiGiftMap)){
            throw new FQException("没有该福袋礼物",500);
        }

        if (ArrayUtil::safeGet($fudaiGiftMap[$giftId], 'reward') == null) {
            throw new FQException("该福袋没有奖励",500);
        }

        $qixiUser = QixiUserDao::getInstance()->loadQixiUser($userId, time());
        if (array_key_exists($giftId, $qixiUser->luckyBag) && $qixiUser->luckyBag[$giftId][1] > 0){
            throw new FQException("已领取过，不可再次领取",500);
        }
        if (!array_key_exists($giftId, $qixiUser->luckyBag) || $qixiUser->luckyBag[$giftId][0] < $fudaiGiftMap[$giftId]['count']){
            throw new FQException("您未达到领取的条件",500);
        }

        // 用户加锁
        $lockKey = "qixi_user_fudai".$userId;
        LockService::getInstance()->lock($lockKey);
        try {
//            $qixiUser->luckyBag[$giftId][0] -= $fudaiGiftMap[$giftId]['count'];
            $qixiUser->luckyBag[$giftId][1] += 1;

            QixiUserDao::getInstance()->hMSetUser($userId, $qixiUser->luckyToJson());
            Log::info(sprintf('reomveFuDaiGift userId=%d luckyBag:%s',
                $userId, json_encode($qixiUser->luckyBag)));
        }finally {
            LockService::getInstance()->unlock($lockKey);
        }

//        Db::startTrans();
//        try {
//            $reward = ContentRegister::getInstance()->decodeFromJson($fudaiGiftMap[$giftId]['reward']);
//
//            $user = UserRepository::getInstance()->loadUser($userId);
//            $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'qixi', $giftId, 1);
//            $rewardItem = $reward->getItem();
//            $user->getAssets()->add($rewardItem->assetId, $rewardItem->count, time(), $biEvent);
//
//            Log::info(sprintf('QixiService.openFuDaiGift ok userId=%d giftId=%d rewardItem=%s',
//                $userId, $giftId, json_encode($rewardItem)));
//            Db::commit();
//
//            return $rewardItem;
//        } catch (FQException $e) {
//            Db::rollback();
//        }
    }

    public function cpAddMissingValue($userId, $sendDetails, $missingGifts){
        $cpUserId = QixiUserDao::getInstance()->getCP($userId);
        if (!empty($cpUserId)){
            #有cp, 找相思礼物的价值
            $count = 0;
            foreach ($sendDetails as list($receiveUser, $giftDetails)) {
                if ($cpUserId != $receiveUser->userId){
                    continue;
                }
                foreach ($giftDetails as $giftDetail) {
                    if ($giftDetail->consumeAsset && $giftDetail->consumeAsset->assetId == AssetKindIds::$BEAN) {
                        if (in_array($giftDetail->giftKind->kindId, $missingGifts)) {
                            $count += $giftDetail->consumeAsset->count;
                        }
                    }
                }
            }

            if ($count == 0){
                return;
            }

            # 给cp加相思值
            QixiUserDao::getInstance()->incrMissingValue($userId, $count);
            QixiUserDao::getInstance()->incrMissingValue($cpUserId, $count);

            Log::info(sprintf('cpAddMissingValue userId=%d cpUserId=%d count=%d sendDetails=%s',
                $userId, $cpUserId, $count, json_encode($sendDetails)));

            #鹊桥榜 cp只记录一个人的
            $redis = RedisCommon::getInstance()->getRedis();
            $key = $this->buildQueQiaoRankKey();
            $score = $redis->zScore($key, $userId);
            if ($score === false) {
                $redis->zIncrBy($key, $count, $cpUserId);
            }else{
                $redis->zIncrBy($key, $count, $userId);
            }
        }
    }

    public function addFuDaiGift($fromUserId, $sendDetails, $fudaiConf){
        $fudaiGifts = [];
        foreach ($fudaiConf as $fudai){
            $fudaiGifts[] = $fudai['giftId'];
        }

        # 需要添加福袋的人 <userId 福袋礼物id， 福袋数量>
        $addUserMap = [];
        foreach ($sendDetails as list($receiveUser, $giftDetails)) {
            foreach ($giftDetails as $giftDetail) {
                if ($giftDetail->giftKind && in_array($giftDetail->giftKind->kindId, $fudaiGifts)) {
                    $addUserMap[$receiveUser->userId] = [$giftDetail->giftKind->kindId, $giftDetail->count];
                }
            }
        }

        Log::info(sprintf('addFuDaiGift fromUserId=%d, addUserMap=%s fudaiGifts=%s, sendDetails=%s',
            $fromUserId, json_encode($addUserMap), json_encode($fudaiGifts), json_encode($sendDetails)));

        # 加福袋
        $timestamp = time();
        foreach ($addUserMap as $userId => $giftInfo){
            // 用户加锁
            $lockKey = "qixi_user_fudai".$userId;;
            LockService::getInstance()->lock($lockKey);
            try {
                $user = QixiUserDao::getInstance()->loadQixiUser($userId, $timestamp);
                if (array_key_exists($giftInfo[0], $user->luckyBag)) {
                    $user->luckyBag[$giftInfo[0]][0] += $giftInfo[1];
                }else{
                    $user->luckyBag[$giftInfo[0]] = [$giftInfo[1], 0];
                }

                QixiUserDao::getInstance()->hMSetUser($userId, $user->luckyToJson());
                Log::info(sprintf('addFuDaiGift userId=%d luckyBag:%s',
                    $userId, json_encode($user->luckyBag)));
            }finally {
                LockService::getInstance()->unlock($lockKey);
            }
        }
    }

    public function onSendGiftEvent($event){
        if ($this->isQixiExpire()){
            return;
        }

        $config = Config::loadQiXiConf();

        try {
            $missingGifts = ArrayUtil::safeGet($config, 'missingGifts', []);
            $this->cpAddMissingValue($event->fromUserId, $event->sendDetails, $missingGifts);
        }catch (Exception $e) {
            Log::error(sprintf('onSendGiftEvent cpAddMissingValue userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }

        try {
            $bags = ArrayUtil::safeGet($config, 'bags', []);
            $this->addFuDaiGift($event->fromUserId, $event->sendDetails, $bags);
        }catch (Exception $e) {
            Log::error(sprintf('onSendGiftEvent.addFuDaiGift Exception userId=%d ex=%d:%s trace=%s',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function getQueQiaoRankList($start, $end) {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->zRevRange($this->buildQueQiaoRankKey(), $start, $end,true);
    }

    public function isQixiExpire(){
        $config = Config::loadQiXiConf();
        $timestamp = time();
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

    public function isLastDay(){
        $config = Config::loadQiXiConf();
        $timestamp = time();
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        return TimeUtil::isSameDay($stopTime, $timestamp);
    }

}