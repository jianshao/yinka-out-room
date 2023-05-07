<?php


namespace app\domain\task\inspector;

use app\domain\events\UserUpdateProfileDomainEvent;
use app\domain\user\dao\MemberDetailAuditDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use think\facade\Log;


class TaskInspectorCompleteInfo extends TaskInspector
{
    public static $TYPE_ID = 'user.complete.info';

    public function processEventImpl($task, $event){
        if($event instanceof UserUpdateProfileDomainEvent){
            $num = 1;
            if (!empty($event->user->getUserModel()->username)) {
                $num += 1;
            }
            if (!empty($event->user->getUserModel()->nickname)) {
                $num += 1;
            }
            if (!empty($event->user->getUserModel()->birthday)) {
                $num += 1;
            }
            if (!empty($event->user->getUserModel()->city)) {
                $num += 1;
            }
            if (!empty(MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($event->user->getUserId(), MemberDetailAuditActionModel::$intro)->content)) {
                $num += 1;
            }

            Log::info(sprintf('TaskInspectorCompleteInfo::processEventImpl userId=%d progress=%d',
                $event->user->getUserId(), $num));
            return $task->setProgress($num, $event->timestamp);
        }
        return array(false, 0);
    }

}