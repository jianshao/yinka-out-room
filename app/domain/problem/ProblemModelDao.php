<?php


namespace app\domain\problem;


use app\core\mysql\ModelDao;


class ProblemModelDao extends ModelDao
{
    protected $table = 'zb_problem';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ProblemModelDao();
        }
        return self::$instance;
    }

    public function findByWhere($where){
        return $this->getModel()->where($where)
            ->select()
            ->toArray();
    }
}