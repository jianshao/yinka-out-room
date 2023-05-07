<?php


namespace app\domain\task;


use app\domain\asset\rewardcontent\ContentRegister;
use app\domain\task\inspector\TaskInspectorRegister;
use app\utils\ArrayUtil;


class TaskKind
{
    public static $CYCLE_DAY_TYPE = 'day';
    public static $CYCLE_WEEK_TYPE = 'week';
    // 任务ID
    public $taskId = 0;
    // 任务名称
    public $name = '';
    // 任务图片
    public $image = '';
    // 任务描述
    public $desc = '';
    // 任务需要进度 签到就是7天
    public $count = 0;
    // 任务可以完成多少次
    public $totalCount = 0;
    // 任务奖励
    public $reward = null;
    // 任务完成自动发奖
    public $autoSendReward = 0;
    // 是否保留
    public $retain = '';
    // 任务监听
    public $inspectors = null;
    // 任务去完成
    public $toComplete = '';
    // 循环时间 1天/1周
    public $cycle = 0;

    public function decodeFromJson($jsonObj) {
        $this->taskId = $jsonObj['id'];
        $this->name = $jsonObj['name'];
        $this->desc = $jsonObj['desc'];
        $this->count = $jsonObj['count'];
        $this->image = ArrayUtil::safeGet($jsonObj, 'image', '');
        $this->cycle = ArrayUtil::safeGet($jsonObj, 'cycle', '');
        $this->totalCount = ArrayUtil::safeGet($jsonObj, 'totalCount', 1);
        $this->retain = ArrayUtil::safeGet($jsonObj, 'retain', '');
        $this->autoSendReward = ArrayUtil::safeGet($jsonObj, 'autoSendReward', 0);

        if (ArrayUtil::safeGet($jsonObj, 'rewards') != null) {
            $this->reward = ContentRegister::getInstance()->decodeList($jsonObj['rewards']);
        }

        if (ArrayUtil::safeGet($jsonObj, 'inspectors') != null) {
            $this->inspectors = TaskInspectorRegister::getInstance()->decodeList($jsonObj['inspectors']);
        }

        $toComplete = ArrayUtil::safeGet($jsonObj, 'toComplete');
        if ($toComplete){
            $this->toComplete = $toComplete;
        }


        return $this;
    }
}