<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\service\RoomService;
use app\domain\user\service\UserService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  申请退出公会 管理员15日未操作 则自动退出
 * Class AutoQuitGuildCommand
 * @package app\api\script
 */
class AutoQuitGuildCommand extends Command
{
    private $appDev;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\AutoQuitGuildCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('clear quit guild member 15day admin no handle');
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
        $func = $input->getArgument('func');
        if (is_null($func)) $func = 'handler';
        $output->writeln(sprintf('app\command\AutoQuitGuildCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\AutoQuitGuildCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\AutoQuitGuildCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @info 申请退出公会 超过15日 管理员未处理 自动退出
     * @return int
     * @throws \Exception
     */
    private function handler()
    {
        $refreshNumber = 0;
        //查询线上有效的工会房间，关联zb_member_guild表获取有效的公会长数据
        $roomDataList = RoomService::getInstance()->getOnlineGuildDataList();
        $this->output->writeln(sprintf('app\command\AutoQuitGuildCommand handler roomIdForUid=%s', json_encode($roomDataList)));
        if (empty($roomDataList)) return 0;
        $guildIds = array_column($roomDataList, 'guild_id');
        $guildUserIds = array_column($roomDataList, 'user_id');
        $guildNameData = array_column($roomDataList, 'nickname', 'guild_id');
        $overTime = time() - (86400 * 15);
        //查询申请退出公会 15天未处理
        $itemData = MemberSocityModelDao::getInstance()->getApplyQuitGuildMemberUid($guildUserIds,$guildIds, $overTime, 200);
        $this->output->writeln(sprintf('app\command\AutoQuitGuildCommand handler loginTimeStart=%s itemData=%s', $overTime, json_encode($itemData)));
        if (empty($itemData)) return 0;
        $redis = RedisCommon::getInstance()->getRedis();
        foreach ($itemData as $userId => $guildId) {
            $this->output->writeln(sprintf('app\command\AutoQuitGuildCommand handler for foreach userId=%d guild=%d', $userId, $guildId));
            // 更新用户cache userinfo hash 的 guild_id socity 字段
            $cacheKey = sprintf("userinfo_%d", $userId);
           $userGuildId = $redis->hget($cacheKey,'guild_id');
           if($userGuildId == $guildId){
               $redis->hMSet($cacheKey, array("guild_id" => 0, "socity" => 0));
               // 清理用户的加入工会申请记录 ,操作用户退出 工会
               [$socityRe, $memberRe] = UserService::getInstance()->updateUserQuitGuild($userId);
               $this->output->writeln(sprintf('app\command\AutoQuitGuildCommand handler updateUserQuitGuild userId=%d socityRe=%s memberRe=%s', $userId, $socityRe, $memberRe));
               // 成功后，发小秘书消息通知用户 (msg:您已成功退出**公会。)
               $guildName = isset($guildNameData[$guildId]) ? $guildNameData[$guildId] : "";
               $msg = sprintf("您已成功退出%s公会。", $guildName);
               YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
               if ($this->appDev === 'dev') {
                   return 1;
               }
               $refreshNumber += 1;
           }else{
               $this->output->writeln(sprintf('app\command\AutoQuitGuildCommand handler userGuild NotEqualTo userId=%d  quitGuildId=%d memberGuildId=%d', $userId,$guildId,$userGuildId));
           }
        }
        return $refreshNumber;
    }


}
