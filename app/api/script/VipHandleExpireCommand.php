<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\vip\constant\VipConstant;
use app\domain\vip\service\VipService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @desc 处理vip过期
 * Class VipHandleExpireCommand
 * @package app\api\script
 * @command  php think VipHandleExpireCommand >> /tmp/VipHandleExpireCommand.log 2>&1
 */
class VipHandleExpireCommand extends Command
{
    protected $redis = null;

    private $limit = 10;

    private $level = 'vip';

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\VipHandleExpireCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->addArgument('level', Argument::OPTIONAL, "set level")
            ->addArgument('limit', Argument::OPTIONAL, "set limit") // 一次批量操作
            ->setDescription('handle vip expire');
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
        $this->level = $input->getArgument('level') ?? $this->level;
        $this->limit = $input->getArgument('limit') ?? $this->limit;
        $output->writeln(sprintf('app\command\VipHandleExpireCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\VipHandleExpireCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\VipHandleExpireCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @desc 判断用户是否充值过vip,并写入redis
     * @return int
     * @throws \Exception
     */
    private function handler()
    {
        $refreshNumber = 0;

        $key = '';
        if ($this->level == 'vip') {
            $key = VipConstant::USER_VIP_EXP_TIME;
        } else if ($this->level == 'svip') {
            $key = VipConstant::USER_SVIP_EXP_TIME;
        }
        if ($key) {
            $this->redis = RedisCommon::getInstance()->getRedis();
            $vipList = $this->redis->zRange($key, 0, $this->limit, true);
            if (empty($vipList)) {
                return $refreshNumber;
            }
            $time = time();
            foreach ($vipList as $userId => $expireTime) {
                if ($expireTime > $time) {
                    return $refreshNumber;
                }
                VipService::getInstance()->processVipCharge($userId, $time);
                $refreshNumber++;
            }
        }

        return $refreshNumber;
    }
}
