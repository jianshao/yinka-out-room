<?php
/**
 * 任务类
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\task\service\TaskService;
use app\domain\task\TaskKind;
use app\domain\user\dao\ActiveDegreeModelDao;
use app\domain\user\dao\CoinDao;
use app\event\RoomShareEvent;
use app\service\CommonCacheService;
use app\utils\CommonUtil;
use app\view\TaskView2;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use app\common\GetuiCommon;


class TaskController2 extends ApiBaseController
{

	//是否有任务
	public function ishavetask()
	{
        try {
            $num = TaskService::getInstance()->getRewardTaskCount($this->headUid);
            return rjson(['num'=>$num]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//分享回调
	public function setshare()
	{
        $roomId = CommonCacheService::getInstance()->getUserCurrentRoom($this->headUid);
        event(new RoomShareEvent($this->headUid, $roomId, time()));
		GetuiCommon::getInstance()->pushMessageToSingle($this->headUid,2);
		return rjson();
	}

	//签到弹窗
	public function weekSignPop()
	{
        try {
            $taskList = TaskService::getInstance()->weekSignPop($this->headUid);
            //判断每天弹一次
            if (empty($taskList)) {
                return rjson();
            }

            $weekday = date('w') == 0 ? 7 : date('w');
            $weekCheckinData = [];
            foreach ($taskList as $task){
                $weekCheckinData[] = TaskView2::encodeWeekCheckIn($task, $weekday);
            }

            return rjson($weekCheckinData);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//签到
	public function weekSign()
	{
        try {
            $rewardItems = TaskService::getInstance()->weekSign($this->headUid);
            $content = TaskView2::encodeRewardContent($rewardItems);
            return rjson(count($content) > 0 ? $content[0]:[]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//活跃度开宝箱
	public function activeBox()
	{
        $taskId = intval(Request::param('task_id'));
        try {
            $rewardItems = TaskService::getInstance()->activeBox($this->headUid, $taskId);
            $content = TaskView2::encodeRewardContent($rewardItems);
            return rjson(count($content) > 0 ? $content[0]:[]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//领取任务
	public function getTask()
	{
        /*
         * 为了兼容之前的协议taskid
         * 100-200是每日任务
         * 200-300是新手任务
        * */
		$taskId = intval(Request::param('task_id'));
        try {
            $rewardItems = TaskService::getInstance()->getTaskReward($this->headUid, $taskId);
            $content = TaskView2::encodeRewardContent($rewardItems);

            return rjson($content);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

    /**
     * @info 任务中心
     * @return \think\response\Json
     */
    public function taskCenter()
    {
        $data = [];
        $userId = $this->headUid;

        try {
            $timestamp = time();
            //我的金币数
            $data['gold_coin'] = CoinDao::getInstance()->loadCoin($userId);
            list($checkIn, $newerTasks, $dailyTasks, $activeBox) = TaskService::getInstance()->taskCenter($userId);
            // 签到任务
            $weekday = date('w') == 0 ? 7 : date('w');
            $todayTaskId = $checkIn->getTaskIdByWeekDay($weekday);
            $weekCheckinData = [];
            foreach ($checkIn->taskList as $task) {
                $weekCheckinData[] = TaskView2::encodeWeekCheckIn($task, $weekday);

                if ($task->taskId == $todayTaskId) {
                    $data['today_is_sign'] = $task->hasReward() ? 1 : 0;
                }
            }
            $data['sign'] = $weekCheckinData;

            // 新手任务
            $newerData = [];
            $newerRewardData = []; //可领取的
            foreach ($newerTasks->taskList as $task) {
                if (!$task->hasReward()) {
                    if ($task->isFinished()) {
                        $newerRewardData[] = TaskView2::encodeNewer($task);
                    } else {
                        $newerData[] = TaskView2::encodeNewer($task);
                    }
                }
            }
            $data['newbie'] = array_merge($newerRewardData, $newerData);

            // 每日任务
            $everydayData = [];
            $everydayRewardData = []; //可领取的
            $everydayFinishData = []; //已领取的
            foreach ($dailyTasks->taskList as $task) {
                if (!$task->hasReward()) {
                    if ($task->isFinished()) {
                        $everydayRewardData[] = TaskView2::encodeDaily($task);
                    } else {
                        $everydayData[] = TaskView2::encodeDaily($task);
                    }
                } else {
                    $everydayFinishData[] = TaskView2::encodeDaily($task);
                }
            }
            $data['everyday'] = array_merge($everydayRewardData, $everydayData, $everydayFinishData);

            // 活跃开包厢任务
            $model = ActiveDegreeModelDao::getInstance()->loadActiveDegree($userId);
            if ($model != null) {
                $model->adjust($timestamp);
            }
            $active_degree_day = $model ? intval($model->day) : 0;
            $active_degree_week = $model ? intval($model->week) : 0;

            $data['active_degree'] = [
                'describe' => $activeBox->getActiveInfo(),
                'dayDegree' => $active_degree_day,
                'weekDegree' => $active_degree_week,
                'dayTasks' => [],
                'weekTasks' => []
            ];
            foreach ($activeBox->taskList as $task) {
                if ($task->taskKind->cycle == TaskKind::$CYCLE_DAY_TYPE) {
                    $data['active_degree']['dayTasks'][] = [
                        'taskId' => $task->taskKind->taskId,
                        'task_image' =>  CommonUtil::buildImageUrl($task->taskKind->image),
                        'degree' => $task->taskKind->count,
                        'content' => TaskView2::encodeActiveBox($task),
                        'is_finish' => $task->hasReward() ? 2 : ($active_degree_day >= $task->taskKind->count ? 1 : 0),
                    ];
                } elseif ($task->taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE) {
                    $data['active_degree']['weekTasks'][] = [
                        'taskId' => $task->taskKind->taskId,
                        'task_image' =>  CommonUtil::buildImageUrl($task->taskKind->image),
                        'degree' => $task->taskKind->count,
                        'content' => TaskView2::encodeActiveBox($task),
                        'is_finish' => $task->hasReward() ? 2 : ($active_degree_week >= $task->taskKind->count ? 1 : 0),
                    ];
                }
            }

            $data['task_room_id'] = CommonCacheService::getInstance()->randomTaskRoomId();
            return rjson($data);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}