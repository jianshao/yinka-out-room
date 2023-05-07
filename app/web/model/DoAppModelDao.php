<?php
/**
 * User: yond
 * Date: 2019
 * APP连接统计
 */
namespace app\web\model;
use app\core\mysql\ModelDao;
use think\Model;
class DoAppModelDao extends ModelDao {
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_doapp';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DoAppModelDao();
        }
        return self::$instance;
    }

    public function getOne($where) {
        return $this->getModel()->where($where)->find();
    }

    public function updateData($where, $step) {
        $this->getModel()->where($where)->inc('urlcount', $data)->update();
    }

    public function saveData($data) {
        $this->getModel()->save($data);
    }
}