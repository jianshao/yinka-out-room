<?php


namespace app\domain\forum\dao;


use app\core\mysql\ModelDao;

class ForumReportModelDao extends ModelDao
{
    protected $table = 'zb_forum_report';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReportModelDao();
        }
        return self::$instance;
    }

    //查询单条
    public function getOne($where, $field='*') {
        return $this->getModel()->where($where)->field($field)->find();
    }

    public function insertData($addParam) {
        return $this->getModel()->insert($addParam);
    }
}