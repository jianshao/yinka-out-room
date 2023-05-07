<?php


namespace app\query\advert;


use app\core\mysql\ModelDao;
use think\Model;

class AdvertModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
    protected $table = 'zb_advert';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AdvertModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new AdvertModel();
        $ret->id = $data['id'];
        $ret->name = $data['name'];
        $ret->image = $data['image'];
        $ret->linkUrl = $data['linkurl'];
        $ret->startTime = $data['start_time'];
        $ret->endTime = $data['end_time'];
        $ret->displayTime = $data['display_time'];
        return $ret;
    }

    /**
     * 查询所有广告列表
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList() {
        $ret = [];
        $datas = $this->getModel()->limit(0, 20)->select()->toArray();
        if(!empty($datas)){
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}