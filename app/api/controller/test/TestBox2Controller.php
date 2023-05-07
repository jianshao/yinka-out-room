<?php


namespace app\api\controller\test;


use app\BaseController;
use app\domain\game\box2\baolv\BaolvTaskState;
use app\domain\game\box2\baolv\Box2BaolvService;
use app\domain\game\box2\Box2Service;
use app\domain\game\box2\Box2System;
use app\domain\game\box2\Box2UserDao;
use app\domain\game\box2\RunningRewardPoolDao;
use think\facade\Log;

class TestBox2Controller extends BaseController
{
    public function baolvTaskInfo() {
        $taskId = $this->request->param('taskId');
        $task = Box2BaolvService::getInstance()->loadTask($taskId);
        if ($task == null) {
            return rjson([], 500, '没有该任务');
        }
        $data = $task->toJson();
        if ($task->state == BaolvTaskState::$FINISH) {
            $taskId = $task->taskId;
            return header("Location:http://recodetest.fqparty.com/static/tasks/$taskId.csv");
        }
        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public function publishBaolvTask() {
        $userCount = intval($this->request->param('userCount'));
        $loopCount = intval($this->request->param('loopCount'));
        $breakCountPerLoop = intval($this->request->param('breakCountPerLoop'));
        $boxId = intval($this->request->param('boxId'));
        $isUserBreakCount = intval($this->request->param('isUserBreakCount'));
        $isSync = intval($this->request->param('sync'));

        if (empty($boxId)) {
            return rjson([], 500, 'boxId参数错误');
        }

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            return rjson([], 500, 'boxId参数错误');
        }

        if ($loopCount <= 0) {
            $loopCount = 1;
        }

        if ($breakCountPerLoop <= 0) {
            return rjson([], 500, 'breakCountPerLoop参数错误');
        }

        if ($userCount <= 0) {
            return rjson([], 500, 'userCount参数错误');
        }

        $taskId = Box2BaolvService::getInstance()->publishTask($boxId, $userCount, $loopCount, $breakCountPerLoop, $isUserBreakCount, $isSync);

        return header("Location:http://recodetest.fqparty.com/api/test/baoLvTaskInfo?taskId=$taskId");
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
                'baolv' => $baolv[0] != 0 ? round(floatval($baolv[1]) / floatval($baolv[0]), 6) : 0
            ]
        ];
    }

    public function refreshPool() {
        $boxId = intval($this->request->param('boxId'));
        $poolId = intval($this->request->param('poolId'));
        $userId = intval($this->request->param('userId'));

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        Log::info(sprintf('TestBox2Controller::refreshPool boxId=%d userId=%d', $boxId, $userId));

        $runningRewardPool = Box2Service::getInstance()->refreshRewardPool($boxId, $poolId);

        $poolData = $this->encodeRunningRewardPool($runningRewardPool, $box->price);

        return rjson($poolData);
    }

    public function refreshAllPool() {
        $boxId = intval($this->request->param('boxId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('TestBox2Controller::refreshAllPool boxId=%d userId=%d', $boxId, $userId));

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
            'pools' => $pools,
            'progress' => Box2Service::getInstance()->getSpecialProgressRate($box->boxId),
            'poolValue' => Box2Service::getInstance()->getSpecialPoolValue($box->boxId),
        ]);
    }

    public function getBox2User() {
        $boxId = intval($this->request->param('boxId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('TestBox2Controller::getBox2User boxId=%d userId=%d', $boxId, $userId));

        $box2User = Box2UserDao::getInstance()->loadBoxUser($userId, $boxId);
        return rjson($box2User->toJsonWithBaolv());
    }

    public function clearBox2User() {
        $boxId = intval($this->request->param('boxId'));
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('TestBox2Controller::clearBox2User boxId=%d userId=%d', $boxId, $userId));

        Box2UserDao::getInstance()->removeBoxUser($userId, $boxId);
        return rjson([]);
    }

    public function getBox2RunningPool() {
        $boxId = intval($this->request->param('boxId'));
        $poolId = intval($this->request->param('poolId'));

        Log::info(sprintf('TestBox2Controller::getBox2RunningPool boxId=%d poolId=%d', $boxId, $poolId));

        $runningRewardPool = RunningRewardPoolDao::getInstance()->loadRewardPool($boxId, $poolId);

        return rjson($this->encodeRunningRewardPool($runningRewardPool));
    }

    public function getAllBox2RunningPool() {
        $boxId = intval($this->request->param('boxId'));

        Log::info(sprintf('TestBox2Controller::getAllBox2RunningPool boxId=%d', $boxId));


        $box = Box2System::getInstance()->findBox($boxId);

        if ($box == null) {
            return rjson([], 500, '宝箱参数错误');
        }

        $ret = [];

        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            $runningRewardPool = RunningRewardPoolDao::getInstance()->loadRewardPool($boxId, $poolId);
            $ret[] = $this->encodeRunningRewardPool($runningRewardPool, $box->price);
        }

        return rjson($ret);
    }
}