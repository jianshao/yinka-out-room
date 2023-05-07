<?php


namespace app\domain\task\dao;


class ActiveBoxModelDao extends TaskModelDao
{
    protected $table = 'zb_task_activebox';
    protected $pk = 'uid';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveBoxModelDao();
        }
        return self::$instance;
    }
}