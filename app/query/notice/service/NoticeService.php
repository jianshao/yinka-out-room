<?php


namespace app\query\notice\service;


use app\query\notice\cache\NoticeCache;
use app\query\notice\dao\NoticeModelDao;

class NoticeService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NoticeService();
        }
        return self::$instance;
    }

    public function loadNotice($start) {
        return NoticeModelDao::getInstance()->loadNoticeModelsByLimit($start);
    }

    public function getLastNoticeTime($userId) {
        return NoticeCache::getInstance()->getLastNoticeTime($userId);
    }

    //是否有新消息
    public function hasNewNotice($endtime, $createTime, $userRegisterTime)
    {
        if ($endtime) {
            if ($endtime >= $createTime || $userRegisterTime > $createTime) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }
}