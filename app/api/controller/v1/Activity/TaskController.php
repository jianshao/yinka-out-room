<?php
/**
 * 任务类
 * yond
 * 
 */

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\task\service\TaskService;
use app\domain\task\TaskKind;
use app\domain\user\dao\ActiveDegreeModelDao;
use app\domain\user\dao\CoinDao;
use app\event\RoomShareEvent;
use app\service\CommonCacheService;
use app\utils\ArrayUtil;
use app\view\TaskView;
use \app\facade\RequestAes as Request;
use app\common\GetuiCommon;


class TaskController extends BaseController
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
        return intval($userId);
    }

	//是否有任务
	public function ishavetask()
	{
        $userId = $this->checkMToken();
        try {
            $num = TaskService::getInstance()->getRewardTaskCount($userId);
            return rjson(['num'=>$num]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//分享回调
	public function setshare()
	{
        $userId = $this->checkMToken();
        $roomId = CommonCacheService::getInstance()->getUserCurrentRoom($userId);
        event(new RoomShareEvent($userId, $roomId, time()));
		GetuiCommon::getInstance()->pushMessageToSingle($userId,2);
		return rjson();
	}

	//签到弹窗
	public function weekSignPop()
	{
        $userId = $this->checkMToken();
        try {
            $taskList = TaskService::getInstance()->weekSignPop($userId);
            //判断每天弹一次
            if (empty($taskList)) {
                return rjson();
            }

            $weekCheckinData = [];
            foreach ($taskList as $task){
                $task->taskKind->taskId = $this->getNewTaskId( $task->taskKind->taskId);
                $weekCheckinData[] = TaskView::encodeWeekCheckIn($task);
            }

            return rjson($weekCheckinData);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//签到
	public function weekSign()
	{
        $userId = $this->checkMToken();
        try {
            $rewardItems = TaskService::getInstance()->weekSign($userId);
            $content = TaskView::encodeRewardContent($rewardItems);
            return rjson(count($content) > 0 ? $content[0]:[]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//活跃度开宝箱
	public function activeBox()
	{
        $userId = $this->checkMToken();
		$num = intval(Request::param('num'));
        try {
            $rewardItems = TaskService::getInstance()->activeBox($userId, null, $num);
            $content = TaskView::encodeRewardContent($rewardItems);
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
        $userId = $this->checkMToken();
		$taskId = Request::param('task_id');
        try {
            $taskId = $this->getNewTaskId($taskId);
            $rewardItems = TaskService::getInstance()->getTaskReward($userId, $taskId);
            $content = TaskView::encodeRewardContent($rewardItems);

            return rjson($content);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	//任务中心
	public function taskCenter()
	{
        $userId = $this->checkMToken();
        $data = [];
        $userId = intval($userId);

        try {
            $timestamp = time();

            //我的金币数
            $data['gold_coin'] = CoinDao::getInstance()->loadCoin($userId);

            list($checkIn, $newerTasks, $dailyTasks, $activeBox) = TaskService::getInstance()->taskCenter($userId);

            // 签到任务
            $weekday = date('w') == 0 ? 7 : date('w');
            $todayTaskId = $checkIn->getTaskIdByWeekDay($weekday);
            $weekCheckinData = [];
            foreach ($checkIn->taskList as $task){
                $task->taskKind->taskId = $this->getOldTaskId( $task->taskKind->taskId);
                $weekCheckinData[] = TaskView::encodeWeekCheckIn($task);

                if($task->taskId == $todayTaskId){
                    $data['today_is_sign'] = $task->hasReward() ? 1 : 0;
                }
            }
            $data['sign'] = $weekCheckinData;

            // 新手任务
            $newerData = [];
            $newerRewardData = []; //可领取的
            foreach ($newerTasks->taskList as $task){
                if(!$task->hasReward()){
                    $task->taskKind->taskId = $this->getOldTaskId( $task->taskKind->taskId);
                    if($task->isFinished()){
                        $newerRewardData[] = TaskView::encodeNewer($task);
                    }else{
                        $newerData[] = TaskView::encodeNewer($task);
                    }
                }
            }
            $data['newbie'] = array_merge($newerRewardData, $newerData);

            // 每日任务
            $everydayData = [];
            $everydayRewardData = []; //可领取的
            $everydayFinishData = []; //已领取的
            foreach ($dailyTasks->taskList as $task){
                $task->taskKind->taskId = $this->getOldTaskId($task->taskKind->taskId);
                if(!$task->hasReward()){
                    if($task->isFinished()){
                        $everydayRewardData[] = TaskView::encodeDaily($task);
                    }else{
                        $everydayData[] = TaskView::encodeDaily($task);
                    }
                }else{
                    $everydayFinishData[] = TaskView::encodeDaily($task);
                }
            }
            $data['everyday'] = array_merge($everydayRewardData, $everydayData, $everydayFinishData);

            // 活跃开包厢任务
            $data['active_degree_describe'] = $activeBox->getActiveInfo();

            $model = ActiveDegreeModelDao::getInstance()->loadActiveDegree($userId);
            if ($model != null) {
                $model->adjust($timestamp);
            }
            $data['active_degree_day'] = $model?intval($model->day):0;
            $data['active_degree_week'] = $model?intval($model->week):0;

            foreach ($activeBox->taskList as $task){
                $data['active_degree_desc_' . strval($task->taskKind->count)] = TaskView::encodeActiveBox($task);

                $progress = 0;
                if ($task->taskKind->cycle == TaskKind::$CYCLE_DAY_TYPE) {
                    $progress = $data['active_degree_day'];
                } elseif ($task->taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE){
                    $progress = $data['active_degree_week'];
                }

                $status = $task->hasReward() ? 2 : ($progress>=$task->taskKind->count ? 1 : 0);
                $data['active_degree_' . strval($task->taskKind->count)] = $status;
            }

            $data['task_room_id'] = CommonCacheService::getInstance()->randomTaskRoomId();

            return rjson($data);
        }catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

	private function getOldTaskId($taskId){
	    //去完成前端根据taskId，所以重构后的taskId必须跟重构前的taskId一致
        $taskIdMap = [
            201=>1,202=>2 ,203=>3, 204=>4,205=>5,206=>6,207=>7,208=>8,209=>29,
            101=>9,102=>10,103=>11,104=>12,105=>13,106=>14,107=>15,108=>16,
            1=>17,2=>18,3=>19,4=>20,5=>21,6=>22,7=>23,
            301=>24,302=>25,303=>26,304=>27,305=>28
        ];

        return ArrayUtil::safeGet($taskIdMap, $taskId, $taskId);
    }

    private function getNewTaskId($taskId){
        //去完成前端根据taskId，所以重构后的taskId必须跟重构前的taskId一致
        $taskIdMap = [
            1=>201,2=>202 ,3=>203, 4=>204,5=>205,6=>206,7=>207,8=>208,29=>209,
            9=>101,10=>102,11=>103,12=>104,13=>105,14=>106,15=>107,16=>108,
            17=>1,18=>2,19=>3,20=>4,21=>5,22=>6,23=>7,
            24=>301,25=>302,26=>303,27=>304,28=>305
        ];

        return ArrayUtil::safeGet($taskIdMap, $taskId, $taskId);
    }
}