<?php
namespace app\domain\reddot\dao;

use app\domain\reddot\model\RedDotItemModel;


//红点单元数据
class RedDotItemModelDao
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RedDotItemModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $data
     */
    public function dataToModel($data)
    {
        $ret = new RedDotItemModel();
        if (empty($data)) {
            return $ret;
        }
        $ret->count = isset($data['count']) ? intval($data['count']) : 0;
//        $ret->type = isset($data['type']) ? $data['type'] : 1;
        return $ret;
    }



    /**
     * @param $data
     */
    public function modeltoData(RedDotItemModel $model)
    {
        return [
            'count'=>$model->count,
            'type'=>$model->type
        ];
    }
}