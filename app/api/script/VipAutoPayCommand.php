<?php

namespace app\api\script;

use app\domain\autorenewal\dao\AutoRenewalAgreementModelDao;
use app\domain\autorenewal\service\AlipayService;
use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\pay\ChargeService;
use app\domain\pay\ProductSystem;
use app\domain\vip\constant\VipConstant;
use app\utils\ArrayUtil;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @desc 支付宝vip自动续费 扣款   // 每一分钟执行，一次处理10条
 * Class VipAutoPayCommand
 * @package app\api\script
 * @command  php think VipAutoPayCommand >> /tmp/VipAutoPayCommand.log 2>&1
 */
class VipAutoPayCommand extends Command
{
    private $limit = 10;

    private $agreementNo = '';

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\VipAutoPayCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->addArgument('limit', Argument::OPTIONAL, "set limit") // 一次批量操作
            ->addArgument('agreement_no', Argument::OPTIONAL, "set agreement_no") // 指定协议
            ->setDescription('支付宝vip自动续费 扣款');
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
        $this->limit = $input->getArgument('limit') ?? $this->limit;
        $this->agreementNo = $input->getArgument('agreement_no') ?? $this->agreementNo;
        if (is_null($func)) $func = 'handler';
        $output->writeln(sprintf('app\command\VipAutoPayCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\VipAutoPayCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\VipAutoPayCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    /**
     * @desc 支付宝vip自动续费 扣款
     * @return int
     * @throws \Exception
     */
    private function handler()
    {
        $refreshNumber = 0;
        $time = time();
        // 10:00开始执行
        $curHour = date('H', $time);
        if ((int)$curHour < 10){
            return 0;
        }

        $timeStr = date('Y-m-d', $time);
        // 查询当天扣款 && 状态为已签约的 用户自动续费VIP
        $where[] = ['status', '=', 1];
        $where[] = ['execute_time', '=', $timeStr];
        $where[] = ['renew_status', '=', 1];

        $field = 'id,user_id,agreement_no,transaction_ids,status,execute_time,product_id,sign_type,config_source';
        $agreementList = AutoRenewalAgreementModelDao::getInstance()->loadAgreementList($where, $this->limit, $field);
        if (!$agreementList){
            return 0;
        }
        foreach ($agreementList as $agreementModel) {
            try{
                // 查询支付宝的用户协议是否正常
                $agreementStatus = AlipayService::getInstance()->isUserAgreement($agreementModel->configSource, $agreementModel->agreementNo);
                if (!$agreementStatus) {
                    // 修改之前协议状态--为已失效状态
                    AutoRenewalAgreementModelDao::getInstance()->updateAgreementStatus($agreementModel->agreementNo, VipConstant::AGREEMENT_STATUS_FALSE);
                    continue;
                }
                // 发起支付扣款
                // 1. 创建订单
                $product = ProductSystem::getInstance()->findProduct($agreementModel->productId);
                $order = ChargeService::getInstance()->buyProduct(
                    $agreementModel->userId, $product, $product->autoRenewalPrice, 1, '支付宝app包月支付', 4);
                // 2. 发起支付
                $pay = AlipayService::getInstance()->alipayAutoPayVip($agreementModel->configSource, $agreementModel->agreementNo, $order);
                Log::channel(['pay', 'file'])->info(sprintf('VipAutoPayCommand::handler pay params=%s', json_encode($pay)));
                // 3. 扣款成功，发货
                $upDate = [];
                if (ArrayUtil::safeGet($pay, 'code') == 10000) {
                    // 可以同步发货，也可以异步发货选其一，即可。这里异步发货
                    // 修改签约表的数据   下次扣款时间加一个周期
                    $period = VipConstant::PERIOD;
                    if (VipConstant::PERIOD_TYPE == 'MONTH') {
                        $executeTime = date('Y-m-d', strtotime("$agreementModel->executeTime +$period month"));
                    } else {
                        $executeTime = date('Y-m-d', strtotime("$agreementModel->executeTime +$period day"));
                    }
                    $upDate['execute_time'] = $executeTime;
                    $upDate['transaction_ids'] = sprintf("%s,%s", $agreementModel->transactionIds, $order->orderId);
                } else {
                    // 扣款失败
                    Log::channel(['pay', 'file'])->error(sprintf('VipAutoPayCommand::handler error pay=%s order=%s', json_encode($pay) , json_encode($order)));
                    $upDate['renew_status'] = 2; // 续费状态.   1: 等待扣费;  2: 扣费失败
                    // 扣款失败发送到钉钉群
                    $pay['order_id'] = $order->orderId;
                    $pay['agreement_no'] = $agreementModel->agreementNo;
                    AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode($pay));
                }
                // 4. 修改签约表状态
                AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                $refreshNumber += 1;
            } catch (\Exception $e) {
                // 有错误消息发送钉钉群
                $data['err_msg'] = 'alipay failed - '.$e->getMessage();
                $data['agreement'] = $agreementModel->agreementNo ?? '';
                AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode($data));
                Log::error(sprintf('VipAutoPayCommand handler failed ex=%d:%s', $e->getCode(), $e->getMessage()));
            }
            usleep(200);
        }

        return $refreshNumber;
    }

    /**
     * @desc 通过命令行单独执行某个协议
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    private function handlerAgreementNo()
    {
        $refreshNumber = 0;

        $agreementModel = AutoRenewalAgreementModelDao::getInstance()->loadAgreement($this->agreementNo);
        if (!$agreementModel){
            return 0;
        }
        // 查询支付宝的用户协议是否正常
        $agreementStatus = AlipayService::getInstance()->isUserAgreement($agreementModel->configSource, $agreementModel->agreementNo);
        if (!$agreementStatus) {
            // 修改之前协议状态--为已失效状态
            AutoRenewalAgreementModelDao::getInstance()->updateAgreementStatus($agreementModel->agreementNo, VipConstant::AGREEMENT_STATUS_FALSE);
        }
        // 发起支付扣款
        // 1. 创建订单
        $product = ProductSystem::getInstance()->findProduct($agreementModel->productId);
        $order = ChargeService::getInstance()->buyProduct(
            $agreementModel->userId, $product, $product->autoRenewalPrice, 1, '支付宝app包月支付', 0);
        // 2. 发起支付
        $pay = AlipayService::getInstance()->alipayAutoPayVip($agreementModel->configSource, $agreementModel->agreementNo, $order);
        Log::channel(['pay', 'file'])->info(sprintf('VipAutoPayCommand::handler pay params=%s', json_encode($pay)));
        // 3. 扣款成功，发货
        $upDate = [];
        if (ArrayUtil::safeGet($pay, 'code') == 10000) {
            // 可以同步发货，也可以异步发货选其一，即可。这里异步发货
            // 修改签约表的数据   下次扣款时间加一个周期
            $period = VipConstant::PERIOD;
            if (VipConstant::PERIOD_TYPE == 'MONTH') {
                $executeTime = date('Y-m-d', strtotime("$agreementModel->executeTime +$period month"));
            } else {
                $executeTime = date('Y-m-d', strtotime("$agreementModel->executeTime +$period day"));
            }
            $upDate['execute_time'] = $executeTime;
            $upDate['transaction_ids'] = sprintf("%s,%s", $agreementModel->transactionIds, $order->orderId);
        } else {
            // 扣款失败
            Log::channel(['pay', 'file'])->error(sprintf('VipAutoPayCommand::handler error pay=%s order=%s', json_encode($pay) , json_encode($order)));
            $upDate['renew_status'] = 2; // 续费状态.   1: 等待扣费;  2: 扣费失败
            // 扣款失败发送到钉钉群
            $pay['order_id'] = $order->orderId;
            $pay['agreement_no'] = $agreementModel->agreementNo;
            AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode($pay));
        }
        // 4. 修改签约表状态
        AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
        $refreshNumber += 1;
        usleep(200);

        return $refreshNumber;
    }
}
