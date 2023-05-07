<?php


namespace app\domain\task\dao;


class NewerModelDao extends TaskModelDao
{
    protected $table = 'zb_task_newer';
    protected $pk = 'uid';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new NewerModelDao();
        }
        return self::$instance;
    }
}