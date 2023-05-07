<?php


namespace app\api\script;

use app\domain\specialcare\queue\AmpQueue;
use app\domain\specialcare\service\UserSpecialCareService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class UserSpecialCareCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\UserSpecialCareCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('特别关心推送提醒');
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
        $func = $input->getArgument('func') ?: "handler";
        $output->writeln(sprintf('app\command\UserSpecialCareCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\UserSpecialCareCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
        }
        // 指令输出
        $output->writeln(sprintf('app\command\UserSpecialCareCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    private function handler()
    {
        $output = $this->output;
        $callback = function ($msg) use ($output) {
            $logmsg = sprintf("UserSpecialCareCommand handler taskConsumer msgBody:%s", $msg->body);
            $output->info($logmsg);
            Log::info($logmsg);
            if ($msg->body) {
                UserSpecialCareService::getInstance()->pushConsumer($msg);
            }
        };
        AmpQueue::getInstance()->consumer($callback);
        return 1;
    }
}