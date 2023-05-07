<?php

namespace app\api\script;

use app\query\user\cache\CachePrefix;
use app\query\user\cache\IndexUserCache;
use app\query\user\cache\UserModelCache;
use app\domain\user\service\UserService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;

ini_set('set_time_limit', 0);

/**
 * @info  在线用户数据桶 清洗处理 维护在线用户backet
 * Class UserBucket
 * @package app\command
 * ThreeLootRefundCommand
 * @command  php think UserBucketCommand man >> /tmp/UserBucket.log 2>&1
 */
class UserBucketCommand extends Command
{

    private $cacheBucketKey = "";   //bucket的key
    private $backetUserNumber = 0;  //缓存的用户数
    private $argumentSex = '';

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\userbucket')
            ->addArgument('sex', Argument::OPTIONAL, "switch name [man woman all]")
            ->setDescription('refresh online user list data');
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
        $sex = $input->getArgument('sex') ? trim($input->getArgument('sex')) : "woman";
        $output->info(sprintf('app\command\userbucket sex:%s entry date:%s', $sex, $this->getDateTime()));
        try {
            $this->initConf($sex);
            list($result, $refreshNumber) = $this->fitExeCute($sex);
        } catch (\Exception $e) {
            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
        }
        // 指令输出
        $output->writeln(sprintf('app\command\userbucket sex:%s success end date:%s exec result:%d refreshNumber:%d', $this->argumentSex, $this->getDateTime(), $result, $refreshNumber));
    }

    /**
     * @info 初始化参数和配置
     * @param $sex
     */
    private function initConf($sex)
    {
        $this->cacheBucketKey = sprintf("%s:%s", CachePrefix::$publicBucketBoxSex, $sex);
        $this->backetUserNumber = 200;
        $this->argumentSex = $sex;
    }

    private function getOnLineUserData($sex)
    {
        if (isset($sexCoveData[$sex])) {
            throw  new Exception("getOnLineUserData sex error");
        }
        $sexCoveData = [
            'man' => "1",
            'woman' => "2",
            'all' => 'all',
        ];
        $dbSex = $sexCoveData[$sex];
        $data = UserService::getInstance()->getOnlineCacheSex($dbSex, 200);
        return [$data, count($data)];
    }

    private function complementIds($onLineIdsCount, $sex)
    {
        if ($onLineIdsCount >= 200) {
            return [];
        }
//        补足不在线用户
        $diffCount = (200 - $onLineIdsCount) > 30 ? 30 : (200 - $onLineIdsCount);
        $offLineTime = $this->getUnixTime() - 36000;
        $indexCache = new IndexUserCache();
        return $indexCache->getOffLineUserIdsForCache($diffCount, $offLineTime, $sex);
    }

    private function fitExeCute($sex)
    {
//        获取所有当前在线用户id,数量统计
        list($onlineUser, $onLineIdsCount) = $this->getOnLineUserData($sex);
//        是否够200人，取不够的差值人数,组合已下线的用户id
        $offlineUser = $this->complementIds($onLineIdsCount, $sex);
//        获取所有需存储的用户数据，并缓存到redis hash
//        $this->cacheUser($onlineUser, 1);
//        $this->cacheUser($offlineUser, 2);
//        merge uid
        $userIds = array_merge($onlineUser, $offlineUser);
//        替换在线用户id 桶数据
        $result = $this->updateUserBucket($userIds);
        return [$result, count($userIds)];
    }

    private function updateUserBucket($userIds)
    {
        if (empty($this->cacheBucketKey)) {
            throw new Exception("cacheBucketKey bucket key error");
        }
        return UserModelCache::getInstance()->cacheUserBucket($this->cacheBucketKey, $userIds);
    }

//    /**
//     * @info 在线为1 不在线为2
//     * @param $userIds
//     * @param int $online
//     * @throws \app\domain\exceptions\FQException
//     */
//    private function cacheUser($userIds, $online = 2)
//    {
////        获取用户数据
//        $userData = $this->readUserInfo($userIds);
//        foreach ($userData as $item) {
//            if (empty($item)) {
//                continue;
//            }
//            $item->onlineStatus = $online;
////            缓存用户数据
//            UserModelCache::getInstance()->saveUserInfo($item);
//        }
//    }
//
//
//    private function readUserInfo($userIds)
//    {
//        foreach ($userIds as $userId) {
//            $d = UserModelDao::getInstance()->loadUserModel($userId);
//            yield $d;
//        }
//    }


}
