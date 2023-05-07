<?php

namespace app\api\script;

use app\domain\exceptions\FQException;
use app\domain\recall\model\PushRecallType;
use app\domain\sms\dao\RongtongdaReportModelDao;
use app\domain\sms\model\RongtongdaReportModel;
use app\domain\sms\service\RongtongdaService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  短信回调上报相关
 * Class SmsCallbackCommand
 * @package app\api\script
 * @command  php think SmsCallbackCommand handler >> /tmp/SmsCallbackCommandHandler.log 2>&1
 */
class SmsCallbackCommand extends Command
{
    private $offset = 0;
    private $endOffset = 0;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\SmsCallbackCommand')
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
        $func = $input->getArgument('func') ?: "handler";
        $output->writeln(sprintf('app\command\SmsCallbackCommand entry func:%s offset:%d endOffset:%d date:%s', $func, $this->offset, $this->endOffset, $this->getDateTime()));
        try {
            $result = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\SmsCallbackCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\SmsCallbackCommand success end func:%s date:%s exec number:%d result:%s', $func, $this->getDateTime(), count($result), json_encode($result)));
    }

    /**
     * @info 启动召回任务解析消费者
     * @param Output $output
     * @return int
     */
    private function handler()
    {
        echo 'handler';
    }


    /**
     * @info 拉取蓉通达短信上报数据
     */
    private function rongtongdaPull()
    {
//        拉取数据
        $strdata = RongtongdaService::getInstance()->pullReport();
        dd($strdata);
        $originList = explode("\n", $strdata);
        if (empty($originList)) {
            throw new FQException("SmsCallbackCommand::rongtongdaPull fatal error originList is empty", 500);
        }
//        遍历store数据
        $result = [];
        foreach ($originList as $itemStr) {
            $result[] = $this->storeItemRongtongdaData($itemStr);
        }
        return $result;
    }

    /**
     * @param $itemStr
     * @return int|string
     */
    private function storeItemRongtongdaData($itemStr)
    {
        if (empty($itemStr)) {
            return 0;
        }
        parse_str($itemStr, $parr);
        $model = new RongtongdaReportModel();
        $model->uid = isset($parr['uid']) ? (int)$parr['uid'] : 0;
        $model->uname = isset($parr['uname']) ? (string)$parr['uname'] : "";
        $model->seq = isset($parr['seq']) ? (int)$parr['seq'] : 0;
        $model->pn = isset($parr['pn']) ? (int)$parr['pn'] : 0;
        $model->stm = isset($parr['stm']) ? (string)$parr['stm'] : 0;
        $model->sc = isset($parr['sc']) ? (string)$parr['sc'] : 0;
        $model->st = isset($parr['st']) ? (string)$parr['st'] : 0;
        $model->bid = isset($parr['bid']) ? (string)$parr['bid'] : 0;
        $model->str_date = date("Ymd");
        $model->platform = PushRecallType::$RTDSMS;
        $model->create_time = $this->getUnixTime();
        $model->origin_data = $itemStr;
        return RongtongdaReportModelDao::getInstance()->storeData($model);
    }


}




















