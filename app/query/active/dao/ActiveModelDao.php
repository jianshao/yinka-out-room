<?php


namespace app\query\active\dao;


use app\core\mysql\ModelDao;
use app\query\active\model\ActiveModel;

class ActiveModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
    protected $table = 'zb_active';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveModelDao();
        }
        return self::$instance;
    }

    public function dataToView($data, $token)
    {
        $ret = [];
//        $ret = new ActiveModel();
//        $ret->name = $data['name'];
//        $ret->introduce = $data['introduce'];
//        $ret->activeImg = $data['active_img'];
//        $ret->activeAddress = $data['active_address'] . "?mtoken=" . $token;
        $ret['images'] = $data['active_img'];
        $ret['link_url'] = $data['active_address'] . '?mtoken=' . $token;
        return $ret;
    }


    public function getAll($token)
    {
        $time = time();
        $where[] = ['active_status', '=', 1];
        $where[] = ['end_time', '>', $time];
        $activityInfo = $this->getModel()->field('name,introduce,active_img,active_address')->where($where)->select()->toArray();
        if (empty($activityInfo)) {
            return [];
        }
        $result = [];
        foreach ($activityInfo as $data) {
            $result = $this->dataToView($data, $token);
        }
        return $result;
    }
}