<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\vip\constant\VipConstant;
use app\domain\vip\service\VipService;
use app\service\ApplePayService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @desc 自动续费补偿机制
 * Class AutoSubscriptionCompensateCommand
 * @package app\api\script
 * @command  php think AutoSubscriptionCompensateCommand >> /tmp/AutoSubscriptionCompensateCommand.log 2>&1
 */
class AutoSubscriptionCompensateCommand extends Command
{
    protected $redis = null;

    private $limit = 1;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\AutoSubscriptionCompensateCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
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
        $this->limit = $input->getArgument('limit') ?? $this->limit;
        $output->writeln(sprintf('app\command\AutoSubscriptionCompensateCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\AutoSubscriptionCompensateCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\AutoSubscriptionCompensateCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @desc 自动续费补偿机制
     * @return int
     * @throws \Exception
     */
    private function handler()
    {
        $refreshNumber = 0;

        $this->redis = RedisCommon::getInstance()->getRedis();
        $subscriptingList = $this->redis->zRange('auto_subscription_apple_compensate', 0, $this->limit, true);
        if (empty($subscriptingList)) {
            return $refreshNumber;
        }
        $time = time();
        foreach ($subscriptingList as $jsonData => $expireTime) {
            //
            if ($expireTime > $time) {
                return $refreshNumber;
            }
            $this->redis->zRem('auto_subscription_apple_compensate', $jsonData);
            $data = json_decode($jsonData,true);
            AutoRenewalService::getInstance()->handleAppleSubscription($data);
            $refreshNumber++;
        }

        return $refreshNumber;
    }
}
