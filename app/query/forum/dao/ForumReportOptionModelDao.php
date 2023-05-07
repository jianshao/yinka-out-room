<?php


namespace app\query\forum\dao;


use app\core\mysql\ModelDao;

class ForumReportOptionModelDao extends ModelDao  {

    protected $table = 'zb_forum_report_option';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReportOptionModelDao();
        }
        return self::$instance;
    }

    public function getList() {
        return $this->getModel()->field('')->select();
    }
}