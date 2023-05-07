<?php

namespace app\query\user\dao;

use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\query\user\cache\MemberDetailAuditCache;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\model\MemberDetailAuditModel;

class MemberDetailAuditDao extends ModelDao
{
    protected $table = 'zb_member_detail_audit';
    protected $serviceName = 'commonSlave';
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
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'action' => $action])
            ->where('status', '=', 0)
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
    public function loadData($id)
    {
        $data = $this->getModel()->where('id', $id)->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data->toArray());
    }

}