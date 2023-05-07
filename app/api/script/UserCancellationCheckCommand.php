<?php

namespace app\api\script;

use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\UserInfoMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\event\UserCancelEvent;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  检查注销用户 注销超过15天则修改为注销成功状态
 * Class UserCancellationCheckCommand
 * @package app\api\script
 * @command  php think UserCancellationCheckCommand exceed >> /tmp/UserCancellationCheckCommand.log 2>&1
 */
class UserCancellationCheckCommand extends Command
{
    private $unixMark = 0;
    private $offset = 0;
    protected $serviceName = 'userMaster';
    protected $table = 'zb_member';

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\UserCancellationCheckCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('user cancellation check');
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getDateTime()
    {
        return date("Y-m-d H:i:s", $this->getUnixTime());
    }

    protected function execute(Input $input, Output $output)
    {
        $func = $input->getArgument('func') ?? "exceed";
        $output->writeln(sprintf('app\command\userCancellationCheck entry func:%s date:%s', $func, $this->getDateTime()));
        $this->unixMark = time() - (86400 * 15);
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\userCancellationCheck execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
        }

        // 指令输出
        $output->writeln(sprintf('app\command\userCancellationCheck success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @info 每天执行注销用户reset 申请注销15天后确认注销
     * @return int
     */
    private function exceed()
    {
        $freshNum = 0;
        $dbModelList = Sharding::getInstance()->getServiceModels($this->serviceName, $this->table);
        foreach ($dbModelList as $dbModel) {
            $this->offset = 0;
            $freshNum += $this->doHandler($dbModel);
        }
        return $freshNum;
    }


    /**
     * @param $dbModel
     * @return int
     */
    private function doHandler($dbModel)
    {
        $freshNum = 0;
        //            获取申请注销时间为15天之前的用户
        $userIds = userModelDao::getInstance()->getCancellationUserIds($dbModel, $this->offset, $this->unixMark);
        if (empty($userIds)) {
            return $freshNum;
        }
        foreach ($userIds as $userId) {
            if (empty($userId)) {
                continue;
            }
            $this->offset = $userId;

            try {
                $user = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId) {
                    $user = UserRepository::getInstance()->loadUser($userId);
                    if ($user == null) {
                        throw new FQException('用户不存在', 500);
                    }
                    #15天内没有登陆->注销用户：用户表标记注销，更新注销申请表状态
                    $this->userExceed($user);
                    return $user;
                });

                AccountMapDao::getInstance()->delAccountMap($userId);
                UserInfoMapDao::getInstance()->delUserInfoMap($userId);
                event(new UserCancelEvent($userId, $this->getUnixTime()));
                $this->output->writeln(sprintf('app\command\UserCancellationCheckCommand exceed db info userId:%d', $userId));
                $freshNum++;
            } catch (Exception $e) {
                $this->output->writeln(sprintf('app\command\UserCancellationCheckCommand exceed db Exception userId:%d ex=%s strace:%s', $userId, $e->getMessage(), $e->getTraceAsString()));
                continue;
            }
//                更新云信
            try {
                $yunResult = YunxinCommon::getInstance()->getUinfos([$userId]);
                if (isset($yunResult['code']) && $yunResult['code'] === 200) {
                    $yunxinRe = YunxinCommon::getInstance()->updateUinfo($user->getUserModel()->accId, $user->getUserModel()->nickname);
                } else {
                    $yunxinRe = "getUinfos error";
                }
                $this->output->writeln(sprintf('app\command\UserCancellationCheckCommand exceed updateUinfo info yunxinResult:%s', json_encode($yunxinRe)));
            } catch (Exception $e) {
                $this->output->writeln(sprintf('app\command\UserCancellationCheckCommand exceed updateUinfo Exception info userId:%d ex=%s strace:%s', $userId, $e->getMessage(), $e->getTraceAsString()));
            }
        }
        $freshNum += $this->DoHandler($dbModel);
        return $freshNum;
    }

    /**
     * @Info 强制注销用户,不验证
     * @param null $confirm //是否强制注销 true是  null不是
     * @throws FQException
     */
    public function userExceed($user)
    {
//        直接注销不再检查，是否可以注销
//        $this->checkCancelUser($confirm);
//        $timeUnix = time();
//        更新用户数据
//        if (!empty($this->userModel->qopenid)) {
//            $this->userModel->qopenid = sprintf("%s_cancel", $this->userModel->qopenid);
//        }
//        if (!empty($this->userModel->wxopenid)) {
//            $this->userModel->wxopenid = sprintf("%s_cancel", $this->userModel->wxopenid);
//        }
//        if (!empty($this->userModel->appleid)) {
//            $this->userModel->appleid = sprintf("%s_cancel", $this->userModel->appleid);
//        }
//        $this->userModel->username = sprintf("%s_cancel", $this->userModel->username);
        $user->getUserModel()->nickname = sprintf("注销用户%d", $user->getUserModel()->userId);
        $user->getUserModel()->nicknameHash = md5($user->getUserModel()->nickname);
        $user->getUserModel()->cancelStatus = 1;
        $user->getUserModel()->prettyId = $user->getUserModel()->userId;
//        更新注销成功
        UserModelDao::getInstance()->updateCancellation($user->getUserModel());
    }
}
