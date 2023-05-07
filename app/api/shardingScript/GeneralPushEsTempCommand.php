<?php

namespace app\api\shardingScript;

use app\core\elasticSearch\ElasticSearchBase;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);


/**
 * @desc mysql中的 im 数据写入es (如果表中字段与es一致可以用这个)
 * Class GeneralPushEsCommand
 * @package app\api\script
 *
 * @command  sudo php think GeneralPushEsTempCommand handler zb_languageroom 100000 500 100000 db3
 */
class GeneralPushEsTempCommand extends BaseCommand
{
    private $offset = 0;
    private $endOffset = 0;
    private $limit = 1000;
    private $table = '';
    private $count = 1000000;
    private $db = '';  // 指定哪个链接

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\GeneralPushEsTempCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->addArgument('table', Argument::OPTIONAL, "set table")  // 表名
            ->addArgument('offset', Argument::OPTIONAL, "set offset")  // 从哪开始
            ->addArgument('limit', Argument::OPTIONAL, "set limit") // 一次批量操作
            ->addArgument('count', Argument::OPTIONAL, "set count") // 一次执行命令最大
            ->addArgument('db', Argument::OPTIONAL, "set db") // 指定哪个链接
            ->setDescription('mysql push to es');
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
        $this->offset = $input->getArgument('offset') ? intval($input->getArgument('offset')) : 0;
        $func = $input->getArgument('func');
        $this->table = $input->getArgument('table') ?? '';
        $this->limit = $input->getArgument('limit') ?? 1000;
        $this->count = $input->getArgument('count') ?? 1000000;
        $this->db = $input->getArgument('db') ?? $this->baseDb;
        if (!$this->table) {
            $output->writeln(sprintf('app\command\GeneralPushEsCommand error %s', 'table 不能为空'));
            return false;
        }
        $output->writeln(sprintf('app\command\GeneralPushEsCommand entry func:%s offset:%d endOffset:%d date:%s', $func, $this->offset, $this->endOffset, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\GeneralPushEsCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
        }
        // 指令输出
        $output->writeln(sprintf('app\command\GeneralPushEsCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @info push 到es中
     * @param Output $output
     * @return int
     */
    private function handler()
    {
        $refreshNumber = 0;
        $es = new ElasticSearchBase();
        $totalNumber = ceil($this->count / $this->limit);
        for ($number = 1; $number <= $totalNumber; $number++) {
            $where[] = ['id', '>=', $this->offset];
            // 只执行一次命令。特例，读取MySQL数据，不需要改成分库形式。
            $list = Db::connect($this->db)->table($this->table)->where($where)->order("id asc")->limit($this->limit)->select()->toArray();
            // log记录起始偏移量，和结束偏移量
            $this->output->writeln(sprintf('app\command\GeneralPushEsCommand handler getMessageLimit offset:%d', $this->offset));
            if (!empty($list)) {
                $es->setIndex($this->table)->bulkAdd($list);
                $this->offset += $this->limit;
                $refreshNumber += $this->limit;
            } else {
                break;
            }
        }
        return $refreshNumber;
    }

}
