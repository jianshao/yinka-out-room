<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\domain\vip\constant\VipConstant;
use app\query\pay\dao\OrderModelDao;
use app\query\pay\dao\UserChargeStaticsModelDao;
use constant\FirstChargeConstant;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @desc 检测用户是否充值过vip 并写入redis  (只执行一次)
 * Class VipIsPayTempCommand
 * @package app\api\script
 * @command  php think VipIsPayTempCommand >> /tmp/VipIsPayTempCommand.log 2>&1
 */
class VipIsPayTempCommand extends Command
{
    protected $redis = null;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\VipIsPayTempCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('vip is pay  set redis');
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
        $output->writeln(sprintf('app\command\VipIsPayTempCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\VipIsPayTempCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\VipIsPayTempCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @desc 判断用户是否充值过vip,并写入redis
     * @return int
     * @throws \Exception
     */
    private function handler()
    {
        $refreshNumber = 0;
        // 查询 充值成功的vip svip用户
        $where[] = ['type', 'in', '2,3'];
        $where[] = ['status', '=', 2];
        // 获取数据总条数，用于分页计算总数 防止内存溢出
        $count = OrderModelDao::getInstance()->getModel()->where($where)->count();
        $pageSize = 1000;  //每页总条数（可根据数据量自行调控）
        $pageNum = ceil($count / $pageSize); //计算需要分几页读取

        $field = 'uid';

        $this->redis = RedisCommon::getInstance()->getRedis();
        for ($prePage = 1; $prePage <= $pageNum; $prePage++) {
            $list = OrderModelDao::getInstance()->getModel()->field($field)->where($where)->page($prePage, $pageSize)->select();
            if (!empty($list)) {
                $list = $list->toArray();
                $uids = array_values(array_unique(array_column($list,'uid')));
                $res = $this->redis->sAdd(VipConstant::USER_VIP_PAY, ...$uids);
                $refreshNumber += $res;
            }
        }
        return $refreshNumber;
    }

    /**
     * @desc 充值过的用户id，不包含vip、svip（首充需要）
     * @return int
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handlerPay()
    {
        $refreshNumber = 0;
        // 获取数据总条数，用于分页计算总数 防止内存溢出
        $count = UserChargeStaticsModelDao::getInstance()->getModel()->count();

        $pageSize = 1000;  //每页总条数（可根据数据量自行调控）
        $pageNum = ceil($count / $pageSize); //计算需要分几页读取

        $field = 'uid';

        $this->redis = RedisCommon::getInstance()->getRedis();
        for ($prePage = 1; $prePage <= $pageNum; $prePage++) {
            $list = UserChargeStaticsModelDao::getInstance()->getModel()->field($field)->page($prePage, $pageSize)->select();
            if (!empty($list)) {
                $list = $list->toArray();
                $uids = array_column($list,'uid');
                $this->redis->sAdd(FirstChargeConstant::USER_RECHARGED_BEEN, ...$uids);
                $refreshNumber += count($uids);
            }
        }
        return $refreshNumber;
    }

    /**
     * @desc 开通过会员的用户 发送小秘书
     */
    public function handlerSendMsg()
    {
        $refreshNumber = 0;
        $this->redis = RedisCommon::getInstance()->getRedis();

        $iterator = null;
        $msg = '亲爱的用户，平台内会员权益权限更新，请更新到最新版本进行查看哦！';
        while ($members = $this->redis->sScan(VipConstant::USER_VIP_PAY, $iterator, null, 500)) {
            if (!empty($members)){
                YunxinCommon::getInstance()->sendBatchMsg(config('config.fq_assistant'),$members,0,['msg' => $msg]);
            }
            $refreshNumber += 500;
        }

        return $refreshNumber;
    }
}
