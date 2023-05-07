<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\MemberDetailAuditModel;
use app\query\user\cache\MemberDetailAuditCache;
use app\utils\ArrayUtil;

class MemberDetailAuditDao extends ModelDao
{
    protected $table = 'zb_member_detail_audit';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberDetailAuditDao();
        }
        return self::$instance;
    }


    public function dataToModel($data)
    {
        $model = new MemberDetailAuditModel();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->content = $data['content'];
        $model->status = $data['status'];
        $model->action = $data['action'];
        $model->updateTime = $data['update_time'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    /**
     * @param MemberDetailAuditModel $model
     */
    public function store(MemberDetailAuditModel $model)
    {
        $where['user_id'] = $model->userId;
        $where['action'] = $model->action;
        $updateData['content'] = $model->content;
        $updateData['status'] = $model->status;
        $updateData['update_time'] = 0;
        $updateData['create_time'] = $model->createTime;
        $result = $this->getModel()->where($where)->update($updateData);
        if ($result >= 1) {
            MemberDetailAuditCache::getInstance()->store($model->userId, $model->action, $model);
            return;
        }
        $data = [
            "user_id" => $model->userId,
            "content" => $model->content,
            "status" => $model->status,
            "action" => $model->action,
            "update_time" => 0,
            "create_time" => $model->createTime,
        ];
        $this->getModel()->insert($data);
        MemberDetailAuditCache::getInstance()->store($model->userId, $model->action, $model);
        $this->getModel()->getNumRows();
    }

    /**
     * @info 获取未审核或已通过的用户详情数据
     * @param $userId
     * @param $action
     * @return MemberDetailAuditModel|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findMemberDetailAuditForCache($userId, $action)
    {
        if (empty($userId)) {
            return false;
        }
        $cacheData = MemberDetailAuditCache::getInstance()->findMemberDetailByUserId($userId, $action);
        if (!empty($cacheData)) return $cacheData;
        $data = $this->getModel()->where(['user_id' => $userId, 'action' => $action])
            ->where('status', '<>', 2)
            ->find();
        if (empty($data)) {
            $model = new MemberDetailAuditModel();
            MemberDetailAuditCache::getInstance()->storeZero($userId, $action, $model);
            return $model;
        }
        $model = $this->dataToModel($data);
        MemberDetailAuditCache::getInstance()->store($userId, $action, $model);
        return $model;
    }

    /**
     * @param $id
     * @return MemberDetailAuditModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadUserModelWithLock($id)
    {
        $data = $this->getModel()->lock(true)->where('id', $id)->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data->toArray());
    }

    /**
     * @param $id
     * @return MemberDetailAuditModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadData($id)
    {
        $data = $this->getModel()->where('id', $id)->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data->toArray());
    }

    /**
     * @param $id
     * @param $status
     */
    public function updateStatus($id, $status, $operatorId)
    {
        $data['status'] = $status;
        $data['update_time'] = time();
        $data['admin_user_name'] = $operatorId;
        return $this->getModel()->where('id', $id)->update($data);
    }

    /**
     * @desc 是否是  未审核状态
     * @param $userId
     * @param $action
     * @return bool  true:未审核状态  false:不是未审核状态
     */
    public function isMemberDetailNotAudit($userId, $action)
    {
        $auditDetail = $this->getModel()->where(['user_id' => $userId, 'action' => $action])->find();
        if (empty($auditDetail)) {
            return false;
        }
        $auditDetail = $auditDetail->toArray();
        // 未审核审核
        if (!empty($auditDetail) && ArrayUtil::safeGet($auditDetail, 'status') == 0) {
            return true;
        }
        return false;
    }

}