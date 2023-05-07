<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;
use app\domain\user\model\ComplaintUserModel;
use app\domain\user\model\ComplaintUserStatus;
use app\utils\ArrayUtil;

class ComplaintUserModelDao extends ModelDao
{
    protected $table = 'zb_complaints_new';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;
    protected $pk = 'id';
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ComplaintUserModelDao();
        }
        return self::$instance;
    }


    /**
     * @param $data
     * @return ComplaintUserModel
     */
    private function dataToModel($data)
    {
        $model = new ComplaintUserModel();
        $model->id = ArrayUtil::safeGet($data, "id", 0);
        $model->fromUid = ArrayUtil::safeGet($data, "from_uid", 0);
        $model->toUid = ArrayUtil::safeGet($data, "to_uid", 0);
        $model->contents = ArrayUtil::safeGet($data, "contents", 0);
        $model->description = ArrayUtil::safeGet($data, "description", 0);
        $model->images = ArrayUtil::safeGet($data, "images", 0);
        $model->videos = ArrayUtil::safeGet($data, "videos", 0);
        $model->createTime = ArrayUtil::safeGet($data, "create_time", 0);
        $model->updateTime = ArrayUtil::safeGet($data, "update_time", 0);
        $model->status = ArrayUtil::safeGet($data, "status", 0);
        $model->adminId = ArrayUtil::safeGet($data, "admin_id", 0);
        return $model;
    }

    /**
     * @param ComplaintUserModel $model
     * @return array
     */
    private function modelToData(ComplaintUserModel $model)
    {
        return [
            'from_uid' => $model->fromUid,
            'to_uid' => $model->toUid,
            'contents' => $model->contents,
            'description' => $model->description,
            'images' => $model->images,
            'videos' => $model->videos,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
            'status' => $model->status,
            'admin_id' => $model->adminId,
        ];
    }

    /**
     * @param $fromUid
     * @param $toUserId
     * @return ComplaintUserModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadForUid($fromUid, $toUserId, $status)
    {
        $where[] = ["from_uid", "=", $fromUid];
        $where[] = ["to_uid", "=", $toUserId];
        $where[] = ["status", "<>", $status];
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param $fromUid
     * @param $toUserId
     * @return ComplaintUserModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadForCid($cid, $status)
    {
        $where[] = ["id", "=", $cid];
        $where[] = ["status", "=", $status];
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param ComplaintUserModel $model
     * @return int|string
     */
    public function storeData(ComplaintUserModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    /**
     * @param $cid
     * @param $adminId
     * @return bool
     */
    public function updateStatusForAdminId($id, $adminId, $status)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['status', '<>', $status];
        return $this->getModel($this->shardingId)->where($where)->limit(1)->save(['admin_id' => $adminId, 'status' => $status, 'update_time' => time()]);
    }


    /**
     * @param $cid
     * @param $adminId
     * @return bool
     */
    public function updateGenjinForAdminId($id, $adminId, $status)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['status', '=', ComplaintUserStatus::$DAICHULI];
        return $this->getModel($this->shardingId)->where($where)->limit(1)->save(['admin_id' => $adminId, 'status' => $status, 'update_time' => time()]);
    }
}








