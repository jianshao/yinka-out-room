<?php


namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\game\turntable\dao\TurntableUserDao;
use app\domain\game\turntable\TurntableService;
use app\domain\game\turntable\TurntableSystem;
use think\facade\Log;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;

class GMTurntableController extends Base2Controller
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
        $baolv = TurntableSystem::calcBaolv($price, $runningRewardPool->giftMap);
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
        $turntableId = intval($this->request->param('turntableId'));
        $poolId = intval($this->request->param('poolId'));
        $userId = intval($this->request->param('userId'));

        $box = TurntableSystem::getInstance()->findBox($turntableId);
        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        Log::info(sprintf('GMTurntableController::refreshPool operatorId=%d turntableId=%d userId=%d', $operatorId, $turntableId, $userId));

        $runningRewardPool = TurntableService::getInstance()->refreshRewardPool($turntableId, $poolId);

        $poolData = $this->encodeRunningRewardPool($runningRewardPool, $box->price);

        return rjson($poolData);
    }

    public function refreshAllPool() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('GMTurntableController::refreshAllPool operatorId=%d turntableId=%d userId=%d', $operatorId, $turntableId, $userId));

        $box = TurntableSystem::getInstance()->findBox($turntableId);
        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];
        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = TurntableService::getInstance()->refreshRewardPool($turntableId, $poolId);
            $pools[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }
        return rjson([
            'turntableId' => $turntableId,
            'pools' => $pools
        ]);
    }

    public function getBoxBaolv() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));
        $box = TurntableSystem::getInstance()->findBox($turntableId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];
        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $pools[] = $this->encodeRunningRewardPool($rewardPool, $box->price);
        }
        return rjson([
            'turntableId' => $turntableId,
            'pools' => $pools
        ]);
    }

    public function getTurntableUser() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('GMTurntableController::getTurntableUser operatorId=%d turntableId=%d userId=%d', $operatorId, $turntableId, $userId));

        $box2User = TurntableUserDao::getInstance()->loadBoxUser($userId, $turntableId);
        return rjson($box2User->toJsonWithBaolv());
    }

    public function setTurntableConf() {
        $operatorId = $this->checkAuth();
        $boxConfStr = $this->request->param('conf');

        if (empty($boxConfStr)) {
            return rjson([], 500, '配置参数错误');
        }
        Log::info(sprintf('GMTurntableController::setTurntableConf operatorId=%d conf=%s', $operatorId, $boxConfStr));
        try {
            $boxConf = json_decode($boxConfStr, true);
            TurntableSystem::setConf($boxConf);
            return rjson($boxConf);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getTurntableRunningPool() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));
        $poolId = intval($this->request->param('poolId'));

        $box = TurntableSystem::getInstance()->findBox($turntableId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        try {
            Log::info(sprintf('GMTurntableController::getTurntableRunningPool operatorId=%d turntableId=%d poolId=%d', $operatorId, $turntableId, $poolId));

            $runningRewardPool = TurntableService::getInstance()->loadRunningRewardPool($turntableId, $poolId);

            return rjson($this->encodeRunningRewardPool($runningRewardPool, $box->price));
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function getAllTurntableRunningPool() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));

        Log::info(sprintf('GMTurntableController::getAllTurntableRunningPool operatorId=%d turntableId=%d', $operatorId, $turntableId));

        $box = TurntableSystem::getInstance()->findBox($turntableId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $ret = [];

        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = TurntableService::getInstance()->loadRunningRewardPool($box->turntableId, $poolId);
            $ret[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }

        return rjson($ret);
    }

    public function getRunningBox() {
        $operatorId = $this->checkAuth();
        $turntableId = intval($this->request->param('turntableId'));

        Log::info(sprintf('GMTurntableController::getRunningBox operatorId=%d turntableId=%d', $operatorId, $turntableId));

        $box = TurntableSystem::getInstance()->findBox($turntableId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $pools = [];

        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = TurntableService::getInstance()->loadRunningRewardPool($box->turntableId, $poolId);
            $pools[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }

        return rjson([
            'pools' => $pools,
        ]);
    }
}