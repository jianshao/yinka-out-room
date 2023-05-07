<?php


namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\activity\acrosspk\AcrossPKService;
use app\domain\forum\service\ForumService;
use app\domain\game\box2\Box2Service;
use app\domain\game\box2\Box2System;
use app\domain\game\box2\Box2UserDao;
use app\domain\game\box2\RunningRewardPoolDao;
use app\domain\user\service\UnderAgeService;
use app\event\ForumCheckPassEvent;
use think\facade\Log;
use think\facade\Request;
use app\common\RedisCommon;
use app\domain\asset\AssetSystem;
use app\domain\exceptions\FQException;
use app\domain\user\service\WalletService;

class GMController extends Base2Controller
{
    private function checkAuth() {
        $operatorId = $this->request->param('operatorId');
        $token = $this->request->param('token');

        $redis = RedisCommon::getInstance()->getRedis();
        $adminToken = $redis->get('admin_token_'.$operatorId);
        if ($token != $adminToken) {
            throw new FQException('鉴权失败', 500);
        }

        return $operatorId;
    }

    public function encodeRunningRewardPool($runningRewardPool, $price) {
        $gifts = [];
        foreach ($runningRewardPool->giftMap as $giftId => $count) {
            $gifts[] = [$giftId, $count];
        }
        $baolv = Box2System::calcBaolv($price, $runningRewardPool->giftMap);
        return [
            'poolId' => $runningRewardPool->poolId,
            'gifts' => $gifts,
            'baolv' => [
                'consume' => $baolv[0],
                'reward' => $baolv[1],
                'baolv' => round(floatval($baolv[1]) / floatval($baolv[0]), 6)
            ]
        ];
    }

    public function refreshPool() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));
        $poolId = intval($this->request->param('poolId'));
        $userId = intval($this->request->param('userId'));

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        Log::info(sprintf('GMController::refreshPool operatorId=%d boxId=%d userId=%d', $operatorId, $boxId, $userId));

        $runningRewardPool = Box2Service::getInstance()->refreshRewardPool($boxId, $poolId);

        $poolData = $this->encodeRunningRewardPool($runningRewardPool, $box->price);

        return rjson($poolData);
    }

    public function refreshAllPool() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('GMController::refreshAllPool operatorId=%d boxId=%d userId=%d', $operatorId, $boxId, $userId));

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];
        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = Box2Service::getInstance()->refreshRewardPool($boxId, $poolId);
            $pools[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }
        return rjson([
            'boxId' => $boxId,
            'pools' => $pools
        ]);
    }

    public function getBoxBaolv() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));
        $box = Box2System::getInstance()->findBox($boxId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];
        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $pools[] = $this->encodeRunningRewardPool($rewardPool, $box->price);
        }
        return rjson([
            'boxId' => $boxId,
            'pools' => $pools
        ]);
    }

    public function getBox2User() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('GMController::getBox2User operatorId=%d boxId=%d userId=%d', $operatorId, $boxId, $userId));

        $box2User = Box2UserDao::getInstance()->loadBoxUser($userId, $boxId);
        return rjson($box2User->toJsonWithBaolv());
    }

    public function setBox2Conf() {
        $operatorId = $this->checkAuth();
        $boxConfStr = $this->request->param('conf');

        if (empty($boxConfStr)) {
            return rjson([], 500, '配置参数错误');
        }
        Log::info(sprintf('GMController::setBox2Conf operatorId=%d conf=%s', $operatorId, $boxConfStr));
        try {
            $boxConf = json_decode($boxConfStr, true);
            Box2System::setConf($boxConf);
            return rjson($boxConf);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getBox2RunningPool() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));
        $poolId = intval($this->request->param('poolId'));

        $box = Box2System::getInstance()->findBox($boxId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        try {
            Log::info(sprintf('GMController::getBox2RunningPool operatorId=%d boxId=%d poolId=%d', $operatorId, $boxId, $poolId));

            $runningRewardPool = Box2Service::getInstance()->loadRunningRewardPool($boxId, $poolId);

            return rjson($this->encodeRunningRewardPool($runningRewardPool, $box->price));
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getAllBox2RunningPool() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));

        Log::info(sprintf('GMController::getAllBox2RunningPool operatorId=%d boxId=%d', $operatorId, $boxId));

        $box = Box2System::getInstance()->findBox($boxId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $ret = [];

        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = Box2Service::getInstance()->loadRunningRewardPool($box->boxId, $poolId);
            $ret[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }

        return rjson($ret);
    }

    public function getRunningBox() {
        $operatorId = $this->checkAuth();
        $boxId = intval($this->request->param('boxId'));

        Log::info(sprintf('GMController::getAllBox2RunningPool operatorId=%d boxId=%d', $operatorId, $boxId));

        $box = Box2System::getInstance()->findBox($boxId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];

        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = Box2Service::getInstance()->loadRunningRewardPool($box->boxId, $poolId);
            $pools[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }

        return rjson([
            'progress' => Box2Service::getInstance()->getSpecialProgressRate($box->boxId),
            'poolValue' => Box2Service::getInstance()->getSpecialPoolValue($box->boxId),
            'pools' => $pools,
        ]);
    }

    public function getUserAsset() {
        $userId = intval(Request::param('userId'));
        $assetId = Request::param('assetId');
        $operatorId = Request::param('operatorId');
        $token = Request::param('token');
        $redis = RedisCommon::getInstance()->getRedis();
        $adminToken = $redis->get('admin_token_'.$operatorId);
        if ($token != $adminToken) {
            return rjson([],500,'鉴权失败');
        }
        if (empty($userId)) {
            return rjson([], 500, '用户ID参数错误');
        }

        if (empty($operatorId)) {
            return rjson([], 500, '操作人参数错误');
        }

        if (empty($assetId)) {
            return rjson([], 500, '资产ID参数错误');
        }

        $assetKind = AssetSystem::getInstance()->findAssetKind($assetId);
        if ($assetKind == null) {
            return rjson([], 500, '资产不存在');
        }

        try {
            $balance = WalletService::getInstance()->getUserAsset($userId, $assetId, $operatorId);
            return rjson([
                'userId' => $userId,
                'assetId' => $assetId,
                'balance' => $balance,
                'operatorId' => $operatorId
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function adjustUserAsset() {
        $userId = intval(Request::param('userId'));
        $assetId = Request::param('assetId');
        $change = intval(Request::param('change'));
        $operatorId = intval(Request::param('operatorId'));
        $reason = Request::param('reason','');
        $activity = Request::param('activity', '');
        $token = Request::param('token');
        $redis = RedisCommon::getInstance()->getRedis();
        if (empty($activity)) {
            if (empty($operatorId)) {
                return rjson([], 500, '操作人参数错误');
            }
            $adminToken = $redis->get('admin_token_'.$operatorId);
            if ($token != $adminToken) {
                return rjson([],500,'鉴权失败');
            }
        }
        if (empty($userId)) {
            return rjson([], 500, '用户ID参数错误');
        }

        if (empty($assetId)) {
            return rjson([], 500, '资产ID参数错误');
        }
        $assetKind = AssetSystem::getInstance()->findAssetKind($assetId);
        if ($assetKind == null) {
            return rjson([], 500, '资产不存在');
        }

        if (empty($change)) {
            return rjson([], 500, '变化值参数错误');
        }

        try {
            $balance = WalletService::getInstance()->adjustAsset($userId, $assetId, $change, $operatorId, $reason, $activity,'admin');
            return rjson([
                'userId' => $userId,
                'change' => $change,
                'assetId' => $assetId,
                'balance' => $balance,
                'operatorId' => $operatorId
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function forumCheckPass() {
        try {
            $userId = intval(Request::param('userId'));
            $forumId = Request::param('forumId');
            $tid = Request::param('tid');
            if (empty($forumId) || empty($tid)){
                return rjson([], 500, '参数错误');
            }

            event(new ForumCheckPassEvent($userId, $forumId, $tid, time()));
            return rjsonFit();
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function delForumReply() {
        try {
            $operatorId = $this->checkAuth();
            $replyId = Request::param('replyId');
            if (empty($replyId)){
                throw new FQException("动态评论ID不能为空", 500);
            }

            ForumService::getInstance()->delReply($operatorId, $replyId);

            return rjson([], 200, '删除成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function setAcrossPKRank() {
        $rank = intval(Request::param('rank'));
        $roomIds = Request::param('roomIds');
        $roomIds = json_decode($roomIds, true);
        AcrossPKService::getInstance()->setAcrossPKRank($rank, $roomIds);
        return rjsonFit();
    }
}