<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;
use app\domain\user\model\ComplaintUserFollowModel;
use app\utils\ArrayUtil;

class ComplaintUserFollowModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_complaints_new_follow';
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ComplaintUserFollowModelDao();
        }
        return self::$instance;
    }


    /**
     * @param $data
     * @return ComplaintUserFollowModel
     */
    private function dataToModel($data)
    {
        $model = new ComplaintUserFollowModel();
        $model->id = ArrayUtil::safeGet($data, "id", 0);
        $model->cid = ArrayUtil::safeGet($data, "cid", 0);
        $model->content = ArrayUtil::safeGet($data, "content", 0);
        $model->adminId = ArrayUtil::safeGet($data, "admin_id", 0);
        $model->createTime = ArrayUtil::safeGet($data, "create_time", 0);
        return $model;
    }

    /**
     * @param ComplaintUserFollowModel $model
     * @return array
     */
    private function modelToData(ComplaintUserFollowModel $model)
    {
        return [
            'cid' => $model->cid,
            'content' => $model->content,
            'admin_id' => $model->adminId,
            'create_time' => $model->createTime,
        ];
    }

    /**
     * @info load data
     * @param $cid
     * @return ComplaintUserFollowModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadFollowData($cid)
    {
        $where['cid'] = $cid;
        $object = $this->getModel()->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @param ComplaintUserFollowModel $model
     * @return int|string
     */
    public function storeData(ComplaintUserFollowModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }
}