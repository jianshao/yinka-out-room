<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

class MemberInvitcodeLogModelDao extends ModelDao
{
    protected $serviceName = 'biMaster';
    protected $table = 'zb_member_invitcode_log';
    protected $pk = 'id';
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberInvitcodeLogModelDao();
        }
        return self::$instance;
    }

    public function modelToData($model)
    {
        return [
            'invitcode' => $model->invitcode,
            'uid' => $model->uid,
            'room_id' => $model->roomId,
            'created' => $model->created,
        ];
    }

    public function insertModel($model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insert($data);
    }

    public function getUserinvitInfo($userId)
    {
        $res =  $this->getModel()->where(['uid' => $userId])->find();
        if (!$res) {
            return null;
        }
        return $res->toArray();
    }
}