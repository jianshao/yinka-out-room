<?php

namespace app\api\script;

use app\domain\exceptions\FQException;
use app\domain\withdraw\dao\UserWithdrawDetailModelDao;
use app\query\user\cache\UserModelCache;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  数据初始化命令行
 * Class InitializeCommand
 * @package app\api\script
 * @replaceInfo:  cleanGuildUserCache: 工会用户缓存初始化脚本
 * @command  php think InitializeCommand cleanGuildUserCache >> /tmp/InitializeCommand.log 2>&1
 * @command  php think InitializeCommand syncWithdrawData  >> /tmp/InitializeCommand.log 2>&1
 */
class InitializeCommand extends Command
{
    private $offset = 0;
    private $endOffset = 0;
    private $appDev;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\InitializeCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->addArgument('offset', Argument::OPTIONAL, "set offset")
            ->setDescription('InitializeCommand初始化数据');
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getDateTime()
    {
        return date("Y-m-d H:i:s");
    }

    protected function execute(Input $input, Output $output)
    {
        $func = $input->getArgument('func');
        $output->writeln(sprintf('app\command\InitializeCommand entry func:%s offset:%d endOffset:%d date:%s', $func, $this->offset, $this->endOffset, $this->getDateTime()));
        try {
            $refreshNumber = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\InitializeCommand execute func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\InitializeCommand success end func:%s date:%s exec refreshNumber:%d', $func, $this->getDateTime(), $refreshNumber));
    }

    private function storeLog($msg)
    {
        $this->output->info($msg);
        return true;
    }

    /**
     * @return int[]
     */
    private function loadGuildUserIdsArr()
    {
//        return [
//            1439778,
//        ];
        $str = <<<str
87	1099144
88	1099510
190	1202392
251	1172610
253	1099615
258	1021100
292	1101508
340	1456958
344	1473193
346	1466534
359	1518357
370	1101042
392	1702723
396	1716386
401	1745792
406	1759639
450	1846284
456	1593733
457	1952575
458	1862271
473	1544310
474	1003649
487	2112664
493	1581305
495	2200562
500	1896531
503	1626314
504	1891925
510	1197874
514	2283897
522	1932208
529	2366885
530	2204937
537	1117564
541	1979575
543	1623729
552	1955634
556	1075874
558	2352579
573	2474156
613	1100875
626	2365714
630	2722617
641	2738271
642	2729824
651	2778351
667	2360500
668	2806950
681	1459875
687	1505256
698	2661784
703	2106860
704	2877932
706	2496160
714	2898439
715	2744978
723	2721224
726	1784684
743	2996905
763	3045745
764	2539712
765	3069782
767	2586815
768	2896756
771	3106568
772	1360094
773	1048391
776	2942118
788	2971908
790	3085390
795	1348716
799	3187915
813	3245870
817	3272654
819	2591625
820	3261265
821	2227946
822	2402180
823	1659743
826	3292612
827	3294321
829	1118096
str;

        $temp = explode("\n", $str);
        if (empty($temp)) {
            return [];
        }
        $result = [];
        foreach ($temp as $itemData) {
            list($_, $userId) = explode("\t", $itemData);
            $result[] = $userId;
        }
        return $result;
    }

    private function cleanGuildUserCache()
    {
        $msg = "cleanGuildUserCache start...";
//        获取所有工会长用户ids
//        update 用户表 工会信息为工会id
        $this->storeLog($msg);
        $userIds = $this->loadGuildUserIdsArr();
        $msg = sprintf("InitializeCommand:hander entry param userIds:%s", json_encode($userIds));
        $this->storeLog($msg);
        $result = $this->cleanGuildUserCacheHandler($userIds);
        $msg = sprintf("InitializeCommand:hander result %s", json_encode($result));
        $this->storeLog($msg);
        return true;
    }


    /**
     * @param $userIds
     * @return array
     * @throws FQException
     */
    public function cleanGuildUserCacheHandler($userIds)
    {
        if (empty($userIds)) {
            throw new FQException("userids error", 500);
        }
        $result = [];
        foreach ($userIds as $userId) {
            $cleanRe = UserModelCache::getInstance()->cleanUserCache($userId);
            $result[$userId] = $cleanRe;
        }
        return $result;
    }

    private function withdrawStartTime()
    {
        $startStrTime = "2022-06-01";
        return strtotime($startStrTime);
    }

    private function withdrawLimitNumber()
    {
        if ($this->appDev === "dev") {
            return 10;
        }
        return 1000;
    }

    /**
     * @return int
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncWithdrawData()
    {
        $refreshNumber = 0;
        $startTimeUnix = $this->withdrawStartTime();
        $limit = $this->withdrawLimitNumber();
        $msg = sprintf("syncWithdrawData start startTimeUnix:%d,limit:%d", $startTimeUnix, $limit);
        $this->storeLog($msg);
//        load数据 初始化 id 1000
        $generator = UserWithdrawDetailModelDao::getInstance()->getSyncOldDataGenerator($startTimeUnix, $limit);

        foreach ($generator as $pkId) {
            if (empty($pkId)) {
                continue;
            }
            $msg = sprintf("syncWithdrawData generator pkId:%d", $pkId);
            $this->storeLog($msg);
//            遍历数据，初始化itemdata
            $userWithdrawDetailModel = UserWithdrawDetailModelDao::getInstance()->loadModel($pkId);
            if ($userWithdrawDetailModel === null) {
                $msg = sprintf("syncWithdrawData generator error load model error pkId:%d", $pkId);
                $this->storeLog($msg);
                continue;
            }
//            初始化日期格式
            $userWithdrawDetailModel->dateStrMonth = $this->operationDateStrMonth($userWithdrawDetailModel->createTime);
//            修改数据
            $updateRe = UserWithdrawDetailModelDao::getInstance()->updateStrTimeForModel($userWithdrawDetailModel);
//            输出日志记录
            if ($updateRe >= 1) {
                $msg = sprintf("syncWithdrawData generator success load model error pkId:%d updateRe:%d", $pkId, $updateRe);
                $this->storeLog($msg);
                $refreshNumber++;
            }
        }
        if ($refreshNumber === 0) {
            return $refreshNumber;
        }
        $msg = sprintf("syncWithdrawData success end startTimeUnix:%d,limit:%d,refreshNumber:%d", $startTimeUnix, $limit, $refreshNumber);
        $this->storeLog($msg);
        $refreshNumber += $this->syncWithdrawData();
        return $refreshNumber;
    }


    /**
     * @return int
     */
    public function TestSyncWithdrawData()
    {
        $this->appDev = "dev";
        return $this->syncWithdrawData();
    }


    private function operationDateStrMonth($unixTime)
    {
        if ($unixTime < 1) {
            return "";
        }
        return date("Y-m", $unixTime);
    }


}
