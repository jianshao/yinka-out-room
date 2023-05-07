<?php


namespace app\domain\problem;


class ProblemService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ProblemService();
        }
        return self::$instance;
    }

    public function listProblem($type) {
        if ($type != null) {
            $where = ['btypeid' => 1];
        } else {
            $where = [['btypeid', 'in', [1,2]]];
        }

        $datas = ProblemModelDao::getInstance()->findByWhere($where);

        $ret = [];
        foreach ($datas as $data) {
            $model = new ProblemModel();
            $model->id = $data['id'];
            $model->typeId = $data['btypeid'];
            $model->title = $data['title'];
            $model->createTime = $data['createtime'];
            $ret[] = $model;
        }
        return $ret;
    }
}