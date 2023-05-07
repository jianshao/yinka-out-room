<?php

namespace app\api\script;

use app\domain\exceptions\FQException;
use app\query\user\cache\UserIdCache;
use app\utils\CommonUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

/**
 * @info  生成用户id
 * Class MakeUserIDsCommand
 * @package app\api\script
 */
class MakeUserIDsCommand extends Command
{

    private $limit = 100000;

    protected function configure()
    {
        $this->setName('MakeUserIDsCommand')->setDescription('MakeUserIDsCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        Log::info(sprintf('MakeUserIDsCommand execute start'));

        try {
            $remainUserCount = UserIdCache::getInstance()->getRemainUserIdsCount();
            if ($remainUserCount > $this->limit){
                # 如果当前剩余的id有10万个就不生成id了
                Log::info(sprintf('MakeUserIDsCommand execute end'));
                return;
            }

            $baseId = UserIdCache::getInstance()->getUsedUserId();
            $newIds = [];
            $endId = 0;

            if (empty($baseId)){
                throw new FQException('基本数值未设置', 999);
            }

            for ($i = $baseId; $i <= $baseId+$this->limit; $i++) {
                if (!CommonUtil::isPrettyNumber($i)) {
                    $newIds[] = $i;
                }
                $endId = $i;
            }

            UserIdCache::getInstance()->saveUsedUserId($endId);
            UserIdCache::getInstance()->saveUserIds($newIds);
            Log::info(sprintf('MakeUserIDsCommand ok baseId=%d endId=%d newIds=%d', $baseId, $endId, count($newIds)));

        }catch (\Exception $e) {
            Log::error(sprintf("MakeUserIDsCommand monitoring alarm error msg=生成用户id出错 baseId=%d endId=%d strace:%s",
                $baseId, $endId, $e->getTraceAsString()));
        } finally {
            if (UserIdCache::getInstance()->getRemainUserIdsCount() < 10000){
                Log::error(sprintf("MakeUserIDsCommand monitoring alarm error msg=当前剩余的id小于1万"));
            }
        }
    }



}
