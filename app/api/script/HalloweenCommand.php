<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\activity\halloween\service\HalloweenService;
use app\domain\user\UserRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  万圣节发放榜单奖励脚本
 * Class HalloweenCommand
 * @package app\api\script
 */
class HalloweenCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\HalloweenCommand')
            ->setDescription('send prop');
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getDateTime()
    {
        return date("Ymd", $this->getUnixTime());
    }

    protected function execute(Input $input, Output $output)
    {
        $timestamp = $this->getUnixTime();
        $date = $this->getDateTime();
        $size = 500;
        $redis = RedisCommon::getInstance()->getRedis(['select' => 13]);

        //获取昨天开始的时间戳
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        //日榜奖励 rich like
        $richDayKey = HalloweenService::getInstance()->getRichDay1RedisKey($beginYesterday);
        $likeDayKey = HalloweenService::getInstance()->getLikeDay1RedisKey($beginYesterday);
        if ($redis->exists($richDayKey) && !$redis->get(sprintf('rich1_%s_send', date('Ymd', $beginYesterday)))) {
            $count = $redis->zCard($richDayKey);
            for ($i = 1; $i <= ceil($count / 500); $i++) {
                $offset = ($i - 1) * $size;
                $richRankList = $redis->zRange($richDayKey, $offset, $i * $size - 1, true);
                if (!empty($richRankList)) {
                    foreach ($richRankList as $userId => $score) {
                        $this->dealDayRank($userId, $score, $timestamp);
                    }
                }
            }
            //发放奖励
            //标记发放完成
            $redis->set(sprintf('rich1_%s_send', date('Ymd', $beginYesterday)), 1);
        }

        if ($redis->exists($likeDayKey) && !$redis->get(sprintf('like1_%s_send', date('Ymd', $beginYesterday)))) {
            //发放奖励
            $count = $redis->zCard($likeDayKey);
            for ($i = 1; $i <= ceil($count / 500); $i++) {
                $offset = ($i - 1) * $size;
                $likeRankList = $redis->zRange($likeDayKey, $offset, $i * $size - 1, true);
                if (!empty($likeRankList)) {
                    foreach ($likeRankList as $userId => $score) {
                        $this->dealDayRank($userId, $score, $timestamp);
                    }
                }
            }
            //标记发放完成
            $redis->set(sprintf('like1_%s_send', date('Ymd', $beginYesterday)), 1);
        }


        //总榜奖励 rich like
        if ($date == '20221106') {
            $richAllKey = HalloweenService::getInstance()->getRichAll1RedisKey();
            $likeAllKey = HalloweenService::getInstance()->getLikeAll1RedisKey();
            if ($redis->exists($richAllKey) && !$redis->get('rich1_all_send')) {
                $richAllRankList = $redis->zRevRange($richAllKey, 0, 9, true);
                //发放奖励
                if (!empty($richAllRankList)) {
                    $userIds = array_keys($richAllRankList);
                    foreach ($userIds as $k => $userId) {
                        if ($k == 0 || $k == 1) {
                            $this->addVip($userId, 365, $timestamp);
                        } else {
                            $this->addVip($userId, 31, $timestamp);
                        }
                    }
                }
                //标记发放完成
                $redis->set('rich1_all_send', 1);
            }
            if ($redis->exists($likeAllKey) && !$redis->get('like1_all_send')) {
                $likeAllRankList = $redis->zRevRange($likeAllKey, 0, 9, true);
                //发放奖励
                if (!empty($likeAllRankList)) {
                    $userIds = array_keys($likeAllRankList);
                    foreach ($userIds as $k => $userId) {
                        if ($k == 0 || $k == 1) {
                            $this->addVip($userId, 365, $timestamp);
                        } else {
                            $this->addVip($userId, 31, $timestamp);
                        }
                    }
                }
                //标记发放完成
                $redis->set('like1_all_send', 1);
            }
        }
    }


    private function dealDayRank($userId, $score, $timestamp)
    {
        $count = 0;
        if ($score >= 1000 && $score < 3000) {
            $count = 1;
        } elseif ($score >= 3000 && $score < 5000) {
            $count = 3;
        } elseif ($score >= 5000 && $score < 10000) {
            $count = 7;
        } elseif ($score >= 10000) {
            $count = 15;
        }
        if ($count > 0) {
            $this->addVip($userId, $count, $timestamp);
        }


    }

    private function addVip($userId, $count, $timestamp)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        $vip = $user->getVip($timestamp);
        $vip->addVip($count, $timestamp);
    }
}
