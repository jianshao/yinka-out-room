<?php


namespace app\domain\game\box2\baolv;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\game\box2\Box2Service;
use app\domain\game\box2\Box2System;
use app\domain\game\box2\Box2User;
use app\domain\game\box2\Box2UserDao;
use app\domain\gift\GiftSystem;
use app\domain\gift\GiftUtils;
use think\facade\Log;

class Box2BaolvService
{
    protected static $instance;
    public static $TASK_PUB_KEY = 'box2_baolv_task_sub';
    public static $TASK_KEY = 'box2_baolv_task';
    public static $USER_POOL = 'box_baolv_user_pool';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Box2BaolvService();
        }
        return self::$instance;
    }

    public function runTaskById($taskId) {
        $task = $this->loadTask($taskId);
        $this->runTask($task);
    }

    public function publishTask($boxId, $userCount, $loopCount, $breakCountPerLoop, $isUserBreakCount, $isSync=false) {
        assert($userCount > 0 && $userCount < 500);
        assert($loopCount > 0);
        assert($breakCountPerLoop >= $userCount);

        $box = Box2System::getInstance()->findBox($boxId);
        if ($box == null) {
            throw new FQException('boxId不存在', 500);
        }

        if ($loopCount <= 0) {
            throw new FQException('loopCount参数错误', 500);
        }

        if ($breakCountPerLoop <= 0) {
            throw new FQException('breakCountPerLoop参数错误', 500);
        }

        if ($userCount <= 0) {
            throw new FQException('userCount参数错误', 500);
        }

        $taskId = strftime('%Y%m%d%H%M%S') . getmypid();
        $task = new BaolvTask($taskId, $boxId, $userCount, $loopCount, $breakCountPerLoop, $isUserBreakCount);

        $redis = RedisCommon::getInstance()->getRedis();

        $taskData = json_encode($task->toJson());
        $redis->hSet(self::$TASK_KEY, $taskId, $taskData);

        if (!$isSync) {
            $redis->rPush(self::$TASK_PUB_KEY, $taskId);
        } else {
            $this->runTask($task);
        }
        Log::info(sprintf('Box2BaolvService::publishTask ok isSync=%d task=%s', $isSync, $taskData));

        return $taskId;
    }

    public function loadTask($taskId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $taskData = $redis->hGet(self::$TASK_KEY, $taskId);
        if (empty($taskData)) {
            throw new FQException('没有该任务', 500);
        }
        try {
            $task = new BaolvTask();
            return $task->fromJson(json_decode($taskData, true));
        } catch (Exception $e) {
            throw new FQException('解析任务失败', 500);
        }
    }

    public function saveTask($task) {
        $redis = RedisCommon::getInstance()->getRedis();
        $taskData = json_encode($task->toJson());
        $redis->hSet(self::$TASK_KEY, $task->taskId, $taskData);
        Log::info(sprintf('Box2BaolvService::saveTask taskId=%s task=%s', $task->taskId, json_encode($task->toJson())));
    }

    private function updateState($task, $state, $progress) {
        $task->state = $state;
        $task->progress = $progress;
        $this->saveTask($task);
    }

    public function runTask($task) {
        Log::info(sprintf('Box2BaolvService::runTask start task=%s', json_encode($task->toJson())));

        $this->updateState($task, BaolvTaskState::$RUNNING, 0);

        try {
            return $this->runTaskImpl($task);
        } catch (Exception $e) {
            $this->updateState($task, BaolvTaskState::$FAILED, $task->progress);
            Log::info(sprintf('Box2BaolvService::runTask failed task=%s ex=%d:%s trace=%s',
                json_encode($task->toJson()), $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    private function getBreakNum($breakNums, $maxBreakNumbs) {
        $index = random_int(0, count($breakNums) - 1);
        while ($index > 0 && $breakNums[$index] > $maxBreakNumbs) {
            $index -= 1;
        }
        return min($maxBreakNumbs, $breakNums[$index]);
    }

    public function calcRewardsValue($giftMap) {
        return GiftUtils::calcTotalValue($giftMap);
    }

    public function combinMap(&$toGiftMap, &$giftMap) {
        foreach ($giftMap as $giftId => $count) {
            if (array_key_exists($giftId, $toGiftMap)) {
                $toGiftMap[$giftId] += $count;
            } else {
                $toGiftMap[$giftId] = $count;
            }
        }
        return $toGiftMap;
    }

    private function runTaskImpl($task) {
        // 设置用户PoolValue
        $userInfoMap = [];
        for ($i = 0; $i < $task->userCount; $i++) {
            $userId = $i + 1;
            $userInfoMap[$userId] = [
                'consume' => 0,
                'reward' => 0,
                'breakCount' => 0,
                'giftMap' => []
            ];
            Box2UserDao::getInstance()->removeBoxUser($userId, $task->boxId);
        }

        $userIds = array_keys($userInfoMap);

        if ($task->isUserBreakCount) {
            $totalProgress = count($userInfoMap) * $task->breakCountPerLoop;
        } else {
            $totalProgress = $task->loopCount * $task->breakCountPerLoop;
        }
        $totalGiftMap = [];
        $progress = 0;
        // 用户分配任务
        for ($i = 0; $i < $task->loopCount; $i++) {
            // 保留次数，保证每个人都有任务
            if ($task->isUserBreakCount == 0) {
                $keepBreakNums = count($userInfoMap);
                $remBreakNums = $task->breakCountPerLoop - $keepBreakNums;
                $userIndex = 0;
                while ($remBreakNums + $keepBreakNums > 0) {
                    $userId = $userIds[$userIndex];
                    if ($remBreakNums > 0) {
                        $breakNums = $this->getBreakNum(Box2System::getInstance()->defaultCounts, $remBreakNums);
                        $remBreakNums -= $breakNums;
                    } else {
                        $breakNums = 1;
                        $keepBreakNums -= 1;
                    }

                    $progress += $breakNums;
                    $userIndex = ($userIndex + 1) % count($userIds);

                    list($totalPrice, $balance, $giftMap, $_, $specialGiftId) = Box2Service::getInstance()->breakBox($userId, 0, $task->boxId, $breakNums, false, true);
                    $userInfoMap[$userId]['breakCount'] += $breakNums;
                    $userInfoMap[$userId]['consume'] += $totalPrice;
                    $userInfoMap[$userId]['reward'] += $this->calcRewardsValue($giftMap);
                    $this->combinMap($userInfoMap[$userId]['giftMap'], $giftMap);
                    $this->combinMap($totalGiftMap, $giftMap);
                    $this->updateState($task, BaolvTaskState::$RUNNING, floatval($progress) / $totalProgress);
                }
            } else {
                $runningUsers = [];
                foreach ($userInfoMap as $userId => $userInfo) {
                    $runningUsers[] = [$userId, $task->breakCountPerLoop];
                }
                while (count($runningUsers) > 0) {
                    Log::info(sprintf('Box2BaolvService::runTaskImpl >>> runningUsers=%s progress=%d totalProgress=%d runningUserCount=%d', json_encode($runningUsers), $progress, $totalProgress, count($runningUsers)));
                    list($userId, $remBreakNums) = $runningUsers[0];
                    Log::info(sprintf('Box2BaolvService::runTaskImpl >>> userId=%d remBreakNums=%d progress=%d totalProgress=%d runningUserCount=%d', $userId, $remBreakNums, $progress, $totalProgress, count($runningUsers)));
                    $breakNums = $this->getBreakNum(Box2System::getInstance()->defaultCounts, $remBreakNums);
                    $progress += $breakNums;
                    list($totalPrice, $balance, $giftMap, $_, $specialGiftId) = Box2Service::getInstance()->breakBox($userId, 0, $task->boxId, $breakNums,  false, true);
                    $userInfoMap[$userId]['breakCount'] += $breakNums;
                    $userInfoMap[$userId]['consume'] += $totalPrice;
                    $userInfoMap[$userId]['reward'] += $this->calcRewardsValue($giftMap);
                    $this->combinMap($userInfoMap[$userId]['giftMap'], $giftMap);
                    $this->combinMap($totalGiftMap, $giftMap);
                    $this->updateState($task, BaolvTaskState::$RUNNING, floatval($progress) / $totalProgress);
                    $remBreakNums -= $breakNums;
                    array_splice($runningUsers, 0, 1);
                    if ($remBreakNums > 0) {
                        $runningUsers[] = [$userId, $remBreakNums];
                    }
                    Log::info(sprintf('Box2BaolvService::runTaskImpl <<< userId=%d remBreakNums=%d progress=%d totalProgress=%d runningUserCount=%d', $userId, $remBreakNums, $progress, $totalProgress, count($runningUsers)));
                }
            }

            Log::info(sprintf('Box2BaolvService::runTaskImpl running progress=%.2f task=%s', $progress, json_encode($task->toJson())));
        }

        $this->updateState($task, BaolvTaskState::$FINISH, 1);

        $this->saveResult($task, $userInfoMap, $totalGiftMap);

        Log::info(sprintf('Box2BaolvService::runTaskImpl finish task=%s', json_encode($task->toJson())));

        return $userInfoMap;
    }

    private function saveFile($fileName, $text)
    {
        if (!$fileName || !$text) {
            return false;
        }

        if ($fp = fopen($fileName, "w")) {
            if (@fwrite($fp, $text)) {
                fclose($fp);
                return true;
            } else {
                fclose($fp);
                return false;
            }
        }

        return false;
    }

    private function saveResult($task, $userInfoMap, $totalGiftMap) {
        $filename = '/www/wwwroot/mua/public/static/tasks/' . $task->taskId . '.csv';
        $saveRes = $this->saveResultImpl($task, $userInfoMap, $totalGiftMap, $filename);
        Log::info(sprintf('Box2BaolvService::saveResult taskId=%s filename=%s',
            $task->taskId, $filename));
    }

    private function printGiftMap($giftMap) {
        $s = [];
        foreach ($giftMap as $giftId => $count) {
            $giftPrice = 0;
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind != null) {
                $giftPrice = ($giftKind->price != null ? $giftKind->price->count : 0) * $count;
            }
            $s[] = "$giftId:$count:$giftPrice";
        }
        return implode('|', $s);
    }

    private function saveResultImpl($task, $userInfoMap, $totalGiftMap, $filename) {
        $totalConsume = 0;
        $totalReward = 0;
        $totalBreakCount = 0;
        if ($fp = fopen($filename, 'w')) {
            $taskInfoTitle = implode(",", ['任务Id', '宝箱Id', '用户数量', '循环次数', '砸蛋次数/循环', '次数类型']) . "\n";
            if (!@fwrite($fp, $taskInfoTitle)) {
                fclose($fp);
                return false;
            };

            if ($task->isUserBreakCount) {
                $breakCountType = '每人次数';
            } else {
                $breakCountType = '总次数';
            }
            $taskInfo = implode(",", [$task->taskId, $task->boxId, $task->userCount, $task->loopCount, $task->breakCountPerLoop, $breakCountType]) . "\n";
            if (!@fwrite($fp, $taskInfo)) {
                fclose($fp);
                return false;
            };
            $title = implode(",", ['用户Id', '消耗', '产出', '砸蛋次数', '爆率', '礼物ID:数量:总价']) . "\n";
            if (!@fwrite($fp, $title)) {
                fclose($fp);
                return false;
            };
            foreach ($userInfoMap as $userId => $userInfo) {
                $totalConsume += $userInfo['consume'];
                $totalReward += $userInfo['reward'];
                $totalBreakCount += $userInfo['breakCount'];

                $line = [
                    $userId,
                    $userInfo['consume'],
                    $userInfo['reward'],
                    $userInfo['breakCount'],
                    sprintf('%.2f%%', (float)$userInfo['reward'] * 100 / (float)$userInfo['consume']),
                    $this->printGiftMap($userInfo['giftMap'])
                ];
                $line = implode(",", $line) . "\n";
                if (!@fwrite($fp, $line)) {
                    fclose($fp);
                    return false;
                }
            }
            $line = [
                '总计',
                '',
                $totalConsume,
                $totalReward,
                $totalBreakCount,
                sprintf('%.2f%%', (float)$totalReward * 100 / (float)$totalConsume),
                $this->printGiftMap($totalGiftMap)
            ];
            $line = implode(",", $line) . "\n";
            if (!@fwrite($fp, $line)) {
                fclose($fp);
                return false;
            }

            fclose($fp);
            return true;
        }
        return false;
    }
}