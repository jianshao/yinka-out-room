<?php


namespace app\query\banner\dao;


use app\core\mysql\ModelDao;
use app\query\banner\BannerModel;
use app\utils\ArrayUtil;

class BannerModelDao extends ModelDao
{

    protected $table = 'zb_banner';
    protected $serviceName = 'commonSlave';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BannerModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $ret = new BannerModel();
        $ret->id = $data['id'];
        $ret->type = $data['type'];
        $ret->image = $data['image'];
        $ret->linkUrl = $data['linkurl'];
        $ret->title = $data['title'];
        $ret->channel = $data['banner_channel'];
        $ret->createTime = $data['create_time'];
        $ret->startTime = $data['start_time'];
        $ret->endTime = $data['end_time'];
        $ret->showType = $data['show_type'];
        $ret->status = $data['status'];
        $ret->bannerType = ArrayUtil::safeGet($data, 'bannerType');
        $ret->location = ArrayUtil::safeGet($data, 'location', 1);
        return $ret;
    }

    public function listByType($type, $status)
    {
        $datas = $this->getModel()->where([
            'type' => $type,
            'status' => $status
        ])->order('id desc')->select();
        $ret = [];
        if (!empty($datas)) {
            $datas = $datas->toArray();
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function getSoonStartByType($type) {
        $where[] = ['type', '=', $type];
        $where[] = ['status', '=', 1];
        $where[] = ['start_time', '>', date('Y-m-d')];
        $datas = $this->getModel()->where($where)->order('id desc')->select();
        $ret = [];
        if (!empty($datas)) {
            $datas = $datas->toArray();
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}