<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;

class AccountMapDao extends ModelDao
{
    protected $table = 'zb_user_account_map';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    // 单例
    public static function getInstance(): AccountMapDao
    {
        if (!isset(self::$instance)) {
            self::$instance = new AccountMapDao();
        }
        return self::$instance;
    }

    public function getUserIdByMobile($mobile)
    {
        return $this->getModel()->where(['type' => 'mobile', 'value' => $mobile])->value('user_id');
    }

    public function getUserIdBySnsType($snsType, $snsId)
    {
        return $this->getModel()->where(['type' => $snsType, 'value' =>$snsId])->value('user_id');
    }

    public function delAccountMap($userId) {
        $this->getModel()->where(['user_id' => $userId])->delete();
    }

    public function updateMobile($mobile, $userId)
    {
        $where = ['user_id' => $userId, 'type' => 'mobile'];
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            $data = [
                'value' => $mobile,
            ];
            $this->getModel()->where($where)->update($data);
        }else{
            $this->addByMobile($mobile, $userId);
        }
    }

    public function addByMobile($mobile, $userId)
    {
        $data = [
            'type' => 'mobile',
            'value' => $mobile,
            'user_id' => $userId
        ];
        $this->getModel()->insert($data);
    }

    public function addBySnsType($snsType, $snsId, $userId)
    {
        $data = [
            'type' => $snsType,
            'value' => $snsId,
            'user_id' => $userId
        ];
        $this->getModel()->insert($data);
    }
}