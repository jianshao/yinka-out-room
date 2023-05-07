<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\core\model\BaseModel;
use app\core\mysql\Sharding;
use app\domain\dao\UserIdentityModelDao;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\guild\service\GuildService;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\service\RoomService;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserService;
use app\query\user\cache\CachePrefix;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  清理工会 超过15天没有登录的成员
 * Class ClearGuildMemberCommand
 * @package app\api\script
 * @replaceInfo:  testHandler 测试脚本是否能正常执行有没有逻辑和语法错误 handler 执行主任务发短信逻辑；reloadDeadqueue 重新发送死信队列
 * @command  php think ClearGuildMemberCommand >> /tmp/ClearGuildMemberCommand.log 2>&1
 * @command  php think ClearGuildMemberCommand testHandler >> /tmp/ClearGuildMemberCommand.log 2>&1
 * @command  php think ClearGuildMemberCommand handlerUnIdentity >> /tmp/ClearGuildMemberCommand.log 2>&1  处理未实名的用户和不满18周岁的用户
 */
class ClearGuildMemberCommand extends Command
{
    private $appDev;
    protected $redis = null;
    private $limitRow = 1000;
    protected $serviceName = 'userMaster';
    protected $table = 'zb_member';


    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\ClearGuildMemberCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('clear guild member 15day not login');
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
        $output->writeln(sprintf('app\command\ClearGuildMemberCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\ClearGuildMemberCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\ClearGuildMemberCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @info 默认逻辑，处理超过15天没有登录的用户，清理200人;
     * @param Output $output
     * @return int  执行成功的条数
     */
    private function handler()
    {
        $refreshNumber = 0;
//        查询线上有效的工会房间，关联zb_member_guild表获取有效的公会长数据
        $guildDataList = RoomService::getInstance()->getOnlineGuildDataList();
        $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handler roomIdForUid=%s', json_encode($guildDataList)));
        if (empty($guildDataList)) return 0;
        $guildUserIds = array_column($guildDataList, 'user_id');
        $guildNameData = array_column($guildDataList, 'nickname', 'guild_id');
        $loginTimeStart = $this->getLoginTimeStart();

        $dbModelList = Sharding::getInstance()->getServiceModels($this->serviceName, $this->table);
        foreach ($dbModelList as $dbModel) {
            $refreshNumber += $this->doHandler($dbModel, $guildUserIds, $loginTimeStart, $guildNameData);
        }

        return $refreshNumber;
    }


    /**
     * @param BaseModel $dbModel
     * @param $guildUserIds
     * @param $loginTimeStart
     * @param $guildNameData
     * @return int
     * @throws \Exception
     */
    private function doHandler($dbModel, $guildUserIds, $loginTimeStart, $guildNameData)
    {
        $refreshNumber = 0;
        //        查询 公会用户，超过15天没有登录的用户,过滤公会长id
        $itemData = UserModelDao::getInstance()->getCancelGuildMemberFilterUids($dbModel, $guildUserIds, $loginTimeStart, $this->limitRow);
        $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handler loginTimeStart=%s itemData=%s', $loginTimeStart, json_encode($itemData)));
        if (empty($itemData)) return 0;

        $redis = RedisCommon::getInstance()->getRedis();
        foreach ($itemData as $userId => $guildId) {
            $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handler for foreach userId=%d guild=%d', $userId, $guildId));
//            更新用户cache userinfo hash 的 guild_id socity 字段
            $cacheKey = sprintf("userinfo_%d", $userId);
            $redis->hMSet($cacheKey, array("guild_id" => 0, "socity" => 0));
//            清理用户的加入工会申请记录 ,操作用户退出 工会
            [$socityRe, $memberRe] = UserService::getInstance()->updateUserQuitGuild($userId);
            $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
            $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handler updateUserQuitGuild userId=%d socityRe=%s memberRe=%s', $userId, $socityRe, $memberRe));
//            成功后，发小秘书消息通知用户 (msg:您超过15天未登录，已被移除XXXX公会。)
            $guildName = isset($guildNameData[$guildId]) ? $guildNameData[$guildId] : "";
            $msg = sprintf("您超过15天未登录，已被移除%s公会。", $guildName);
            YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
            if ($this->appDev === 'dev') {
                return 1;
            }
            $refreshNumber += 1;
        }
        return $refreshNumber;
    }

    /**
     * @desc 处理未实名的用户和不满18周岁的用户
     * @return int
     * @throws \Exception
     */
    private function handlerUnIdentity()
    {
        $refreshNumber = 0;
        // 查询 0未审核 1通过 入会的用户
        $where[] = ['status', 'in', '0,1'];
        // 获取数据总条数，用于分页计算总数 防止内存溢出
        $count = MemberSocityModelDao::getInstance()->count($where);
        $pageSize = 1000;  //每页总条数（可根据数据量自行调控）
        $pageNum = ceil($count / $pageSize); //计算需要分几页读取

        // 查询公会数据
        $guildField = 'id as guild_id,nickname';
        $guildList = MemberGuildModelDao::getInstance()->getGuildList($guildField);
        $guildNameData = array_column($guildList, 'nickname', 'guild_id');

        $this->redis = RedisCommon::getInstance()->getRedis();
        for ($prePage = 1; $prePage <= $pageNum; $prePage++) {
            $guildMemberList = MemberSocityModelDao::getInstance()->getGuildMemberByPage(
                $prePage, $pageSize, 'id,user_id,guild_id,status', $where
            );
            if (!empty($guildMemberList)) {
                foreach ($guildMemberList as $guildMemberInfo) {
                    $userId = ArrayUtil::safeGet($guildMemberInfo, 'user_id');
                    $guildId = ArrayUtil::safeGet($guildMemberInfo, 'guild_id');
                    $guildName = $guildNameData[$guildId] ?? "";
                    $whereIdentity = [];
                    $whereIdentity['status'] = 1;  // 实名成功
                    $whereIdentity['uid'] = $userId;
                    $identityInfo = UserIdentityModelDao::getInstance()->loadByWhere($whereIdentity);
                    $identityInfo = $identityInfo ? UserIdentityModelDao::getInstance()->modelToData($identityInfo) : [];
                    // 已实名 并且 满18周岁 不做处理,跳出循环
                    if (!empty($identityInfo) && CommonUtil::getAge(ArrayUtil::safeGet($identityInfo, 'certno')) >= 18) {
                        continue;
                    }
                    $msg = "";

                    // 未实名并且已加入工会   需要移出当前公会
                    if (empty($identityInfo) && ArrayUtil::safeGet($guildMemberInfo, 'status') === 1) {
                        $this->userQuitGuild($userId);
                        $msg = sprintf("很抱歉！因您未进行实名认证，不符合加入公会条件，已被移出%s。", $guildName);
                    } else if (empty($identityInfo) && ArrayUtil::safeGet($guildMemberInfo, 'status') === 0) {
                        // 未实名并且申请加入工会  需要取消申请公会
                        $this->userCancelApplyGuild($guildId, ArrayUtil::safeGet($guildMemberInfo, 'id'), $userId);
                        $msg = "很抱歉！您未进行实名认证，不符合加入公会条件。";
                    } else if (!empty($identityInfo)
                        && CommonUtil::getAge(ArrayUtil::safeGet($identityInfo, 'certno')) < 18
                        && ArrayUtil::safeGet($guildMemberInfo, 'status') === 1
                    ) {
                        // 已实名、未满18周岁并且已加入工会  需要移出当前公会
                        $this->userQuitGuild($userId);
                        $msg = sprintf("很抱歉！因您未满18周岁，不符合加入公会条件，已被移出%s。", $guildName);
                    } else if (!empty($identityInfo)
                        && CommonUtil::getAge(ArrayUtil::safeGet($identityInfo, 'certno')) < 18
                        && ArrayUtil::safeGet($guildMemberInfo, 'status') === 0
                    ) {
                        // 已实名、未满18周岁并且申请加入工会  需要取消申请公会
                        $this->userCancelApplyGuild($guildId, ArrayUtil::safeGet($guildMemberInfo, 'id'), $userId);
                        $msg = "很抱歉！您未满18周岁，不符合加入公会条件。";
                    }

                    // 发送小秘书消息
                    if ($msg && $userId) {
                        YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
                        $refreshNumber += 1;
                        Log::info(sprintf('ClearGuildMemberCommand::handlerUnIdentity user_id=%s msg:%s', $userId, $msg));
                        $this->output->writeln(sprintf('user_id=%s msg:%s', $userId, $msg));
                    }
                }
            }
        }
        return $refreshNumber;
    }

    /**
     * @desc 用户移出当前公会操作
     * @param int $userId
     */
    private function userQuitGuild(int $userId)
    {
        // 更新用户cache userinfo hash 的 guild_id socity 字段
        $cacheKey = sprintf("userinfo_%d", $userId);
        $this->redis->hMSet($cacheKey, array("guild_id" => 0, "socity" => 0));
        // 清理用户的加入工会申请记录 ,操作用户退出 工会
        [$socityRe, $memberRe] = UserService::getInstance()->updateUserQuitGuild($userId);
        $this->redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
        $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handlerUnIdentity updateUserQuitGuild userId=%d socityRe=%s memberRe=%s', $userId, $socityRe, $memberRe));
    }

    /**
     * @desc 取消申请公会
     * @param int $guildId
     * @param int $userGuildId
     * @param int $userId
     */
    private function userCancelApplyGuild(int $guildId, int $userGuildId, int $userId)
    {
        // 更新用户cache userinfo hash 的 guild_id socity 字段
        $cacheKey = sprintf("userinfo_%d", $userId);
        $this->redis->hMSet($cacheKey, array("guild_id" => 0, "socity" => 0));
        // 取消申请公会
        [$socityRe, $socityMsg] = GuildService::getInstance()->cancelApplyGuild($guildId, $userGuildId, $userId);
        $this->output->writeln(sprintf('app\command\ClearGuildMemberCommand handlerUnIdentity cancelApplyGuild userId=%d socityRe=%s socityMsg=%s', $userId, $socityRe, $socityMsg));
    }

    private function getLoginTimeStart()
    {
        $unixTime = $this->getUnixTime();
        $unixTime = $unixTime - (86400 * 15);
        return date("Y-m-d H:i:s", $unixTime);
    }


    //testhandler
    private function testHandler()
    {
        $this->appDev = "dev";
        return $this->handler();
    }

}
