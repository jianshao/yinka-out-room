<?php

namespace app\domain\reddot\event\handler;

use app\domain\events\FocusFriendDomainEvent;
use app\domain\events\TaskFinishedDomianEvent;
use app\domain\events\TaskRewardDomianEvent;
use app\query\notice\dao\NoticeModelDao;
use app\domain\reddot\model\RedDotTypes;
use app\domain\reddot\RedDotItem;
use app\domain\task\service\TaskService;
use app\domain\user\dao\UserModelDao;
use app\event\CallListEvent;
use app\event\CareUserListEvent;
use app\event\EnjoyForumEvent;
use app\event\ForumDetailEvent;
use app\event\ImReplyListEvent;
use app\event\ReplyForumEvent;
use app\event\TaskCenterEvent;
use app\event\UserLoginEvent;
use app\event\VisitEvent;
use app\event\VisitUserInfoEvent;
use app\utils\CommonUtil;
use think\facade\Log;
use Exception;

// 红点 处理行为
class RedDotHandler
{
    /**
     * @Info 用户登陆的小红点数据初始化
     * @param UserLoginEvent $event
     * @throws \app\domain\exceptions\FQException
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
//            我的-任务中心
            $this->fitCenter($event);
//            版本更新消息
//            $this->fitVersionCompare($event);
//            官方消息
            $this->fitOffical($event);
//            打招呼消息
//            $this->fitHi($event);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onUserLoginEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


//    private function fitHi(UserLoginEvent $event)
//    {
//        $number = AttentionModelDao::getInstance()->getUnreadMsgCount($event->userId);
//        $reddotItem = new RedDotItem($event->userId, RedDotTypes::$HI);
//        $result = $reddotItem->hset($number, "count");
//        Log::info(sprintf('RedDotHandler::fitHi userId=%d field:%s exResult=%d',
//            $event->userId, "hi", $result));
//    }

    /**
     * @Info 官方消息
     * @param UserLoginEvent $event
     */
    private function fitOffical(UserLoginEvent $event)
    {
        $registerTime = UserModelDao::getInstance()->findRegisterTimeByUserId($event->userId);
        list($noticeModels, $count) = NoticeModelDao::getInstance()->loadNoticeModelsByLimit(1);
        $endtime = NoticeModelDao::getInstance()->getLastNoticeTime($event->userId);
        $notice_msg_num = 0;
        if (count($noticeModels) > 0) {
            $notice = $noticeModels[0];
            $notice_msg_num = $this->hasNewNotice($endtime, $notice->createTime, $registerTime);
        }
        $reddotItem = new RedDotItem($event->userId, RedDotTypes::$OFFICIAL);
        $reddotItem->hset($notice_msg_num, "count");
    }


    //是否有新消息
    private function hasNewNotice($endtime, $createTime, $userRegisterTime)
    {
        if ($endtime) {
            if ($endtime >= $createTime || $userRegisterTime > $createTime) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }

    /**
     *
     * @param UserLoginEvent $event
     * @throws \app\domain\exceptions\FQException
     */
    private function fitCenter(UserLoginEvent $event)
    {
        $number = TaskService::getInstance()->getRewardTaskCount($event->userId);
        $reddotItem = new RedDotItem($event->userId, RedDotTypes::$TASKCENTER);
        $reddotItem->hset($number, "count");
    }


    /**
     * @info 回复帖子  新增小红点
     *   铁子所有人增加1 小红点， replyAtuid 增加1 小红点
     * @param ReplyForumEvent $event
     */
    public function onReplyForumEvent(ReplyForumEvent $event)
    {
        try {
            CommonUtil::redHeadIncr($event->forumUid, RedDotTypes::$TIMELINES, 1);
            if (!empty($event->replyAtuid)) {
                CommonUtil::redHeadIncr($event->replyAtuid, RedDotTypes::$TIMELINES, 1);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onReplyForumEvent userId=%d ex=%d:%s',
                $event->replyAtuid, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 点赞 type 1点赞 取消点赞  新增或减少1 小红点
     * @param EnjoyForumEvent $event
     */
    public function onEnjoyForumEvent(EnjoyForumEvent $event)
    {
        try {
            if ($event->type == 1) {
                CommonUtil::redHeadIncr($event->forumUid, RedDotTypes::$TIMELINES, 1);
            } else {
                CommonUtil::redHeadDecr($event->forumUid, RedDotTypes::$TIMELINES, 1);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onEnjoyForumEvent userId=%d ex=%d:%s',
                $event->forumUid, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 查看动态详情 减少小红点
     * @param ForumDetailEvent $event
     */
    public function onForumDetailEvent(ForumDetailEvent $event)
    {
        try {
            if ($event->forumUid == $event->userId) {
                CommonUtil::redHeadDecr($event->forumUid, RedDotTypes::$TIMELINES, 1);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onForumDetailEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info im查看动态评论列表  清空用户动态小红点
     * @param ImReplyListEvent $event
     */
    public function onImReplyListEvent(ImReplyListEvent $event)
    {
        try {
            CommonUtil::redHeadSet($event->userId, RedDotTypes::$TIMELINES, 0);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onImReplyListEvent error userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

//    /**
//     * @info 给指定的人打招呼 新增小红点
//     * @param UserGreetEvent $event
//     */
//    public function onUserGreetEvent(UserGreetEvent $event)
//    {
//        try {
//            CommonUtil::redHeadIncr($event->greetUserId, RedDotTypes::$HI, 1);
//        } catch (Exception $e) {
//            Log::warning(sprintf('RedDotHandler::onUserGreetEvent userId=%d ex=%d:%s',
//                $event->greetUserId, $e->getCode(), $e->getMessage()));
//        }
//    }

    /**
     * @Info 查看打招呼详情列表 清空小红点
     * @param CallListEvent $event
     */
    public function onCallListEvent(CallListEvent $event)
    {
        try {
            CommonUtil::redHeadSet($event->userId, RedDotTypes::$HI, 0);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onCallListEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 关注好友 新增好友小红点 1  新增关注小红点1
     * @param FocusFriendDomainEvent $event
     */
    public function onFocusFriendDomainEvent(FocusFriendDomainEvent $event)
    {
        try {
            if ($event->isFocus == 1) {
                CommonUtil::redHeadIncr($event->friendId, RedDotTypes::$FRIEND, 1);
                CommonUtil::redHeadIncr($event->friendId, RedDotTypes::$HI, 1);
            } else {
                CommonUtil::redHeadDecr($event->friendId, RedDotTypes::$FRIEND, 1);
                CommonUtil::redHeadDecr($event->friendId, RedDotTypes::$HI, 1);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onFocusFriendDomainEvent userId=%d ex=%d:%s',
                $event->friendId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 查看关注列表 清空小红点
     * @param CareUserListEvent $event
     */
    public function onCareUserListEvent(CareUserListEvent $event)
    {
        try {
            CommonUtil::redHeadSet($event->userId, RedDotTypes::$FRIEND, 0);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onCareUserListEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 来访 新增小红点 1
     */
    public function onVisitUserInfoEvent(VisitUserInfoEvent $event)
    {
        try {
            if ($event->isVisit) {
                CommonUtil::redHeadIncr($event->visitUserId, RedDotTypes::$VISIT, 1);
            }
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onVisitUserInfoEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 我的-来访列表  清空来访小红点
     * @param VisitEvent $event
     */
    public function onVisitEvent(VisitEvent $event)
    {
        try {
            CommonUtil::redHeadSet($event->userId, RedDotTypes::$VISIT, 0);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onVisitEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 完成任务领奖励 减少小红点 1
     * @param TaskCenterEvent $event
     */
    public function onTaskRewardDomianEvent(TaskRewardDomianEvent $event)
    {
        try {
            $number = TaskService::getInstance()->getRewardTaskCount($event->user->getUserid());
            CommonUtil::redHeadSet($event->user->getUserid(), RedDotTypes::$TASKCENTER, $number);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onTaskRewardDomianEvent error userId=%d ex=%d:%s',
                $event->user->getUserid(), $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 完成任务领奖励 增加小红点 1
     * @param TaskFinishedDomianEvent $event
     */
    public function onTaskFinishedDomianEvent(TaskFinishedDomianEvent $event)
    {
        try {
            $number = TaskService::getInstance()->getRewardTaskCount($event->user->getUserid());
            CommonUtil::redHeadSet($event->user->getUserid(), RedDotTypes::$TASKCENTER, $number);
        } catch (Exception $e) {
            Log::warning(sprintf('RedDotHandler::onTaskFinishedDomianEvent error userId=%d ex=%d:%s',
                $event->user->getUserid(), $e->getCode(), $e->getMessage()));
        }
    }


}