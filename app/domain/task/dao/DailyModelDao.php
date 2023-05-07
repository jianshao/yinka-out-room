<?php


namespace app\domain\task\dao;


class DailyModelDao extends TaskModelDao
{
    protected $table = 'zb_task_daily';
    protected $pk = 'uid';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DailyModelDao();
        }
        return self::$instance;
    }
}