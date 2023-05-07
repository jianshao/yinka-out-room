<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\WholewheatGiftPoint;


class WholewheatGiftPointModelDao extends ModelDao
{
    protected $table = 'zb_wholewheat_gift_point';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new WholewheatGiftPointModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $ret = new WholewheatGiftPoint();
        $ret->id = $data['id'];
        $ret->giftId = $data['gift_id'];
        $ret->point = $data['point'];
        return $ret;
    }

    /**
     * @param $giftId
     * @return WholewheatGiftPoint|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadForGiftId($giftId)
    {
        $cacheKey = sprintf("WholewheatGiftPointModelDao_%s:giftId:%d", 'loadForGift', $giftId);
        $object = $this->getModel($giftId)->where('gift_id', $giftId)->cache($cacheKey,10)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

}