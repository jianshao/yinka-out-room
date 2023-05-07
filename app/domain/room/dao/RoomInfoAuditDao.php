<?php


namespace app\domain\room\dao;

use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\room\model\RoomInfoAuditActionModel;
use app\domain\user\model\MemberDetailAuditModel;


class RoomInfoAuditDao extends ModelDao
{
    protected $pk = 'id';
    protected $table = 'zb_member_detail_audit';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomInfoAuditDao();
        }
        return self::$instance;
    }

    public function findNotAudit(MemberDetailAuditModel $model)
    {
        $where['room_id'] = $model->roomId;
        $where['action'] = $model->action;
        $where['status'] = 0;
        $itemObject = $this->getModel()->where($where)->find();
        if (!empty($itemObject) && $itemObject->getAttr('content') != $model->content) {
            $typeStr = RoomInfoAuditActionModel::typeToMsg($model->action);
            throw new FQException(sprintf('您的%s未审核通过，不能再次提交', $typeStr), 500);
        }
        return $itemObject;
    }

    public function updateStatus($id, $status, $operatorId)
    {
        $data['status'] = $status;
        $data['update_time'] = time();
        $data['admin_user_name'] = $operatorId;
        $this->getModel()->where('id', $id)->update($data);
        return $this->getModel()->where('id', $id)->find();
    }

    public function store(MemberDetailAuditModel $model)
    {
        $where['room_id'] = $model->roomId;
        $where['action'] = $model->action;

        $updateData['content'] = $model->content;
        $updateData['status'] = $model->status;
        $updateData['update_time'] = 0;
        $updateData['create_time'] = $model->createTime;
        $result = $this->getModel()->where($where)->update($updateData);
        if ($result >= 1) {
            return $result;
        }

        $data = [
            "user_id" => $model->userId,
            "room_id" => $model->roomId,
            "content" => $model->content,
            "status" => $model->status,
            "action" => $model->action,
            "update_time" => 0,
            "create_time" => $model->createTime,
        ];
        $result = $this->getModel()->insert($data);
        return $result;
    }

    public function getAuditStatus($roomId)
    {
        $where['room_id'] = $roomId;
        $where['status'] = 0;
        return $this->getModel()->where($where)->select()->toArray();
    }

    public function cancelAuditStatus($roomId, $action, $content)
    {
        $where['room_id'] = $roomId;
        $where['action'] = $action;

        $updateData['content'] = $content;
        $updateData['status'] = 1;
        $updateData['update_time'] = time();
        return $this->getModel()->where($where)->update($updateData);
    }
}
