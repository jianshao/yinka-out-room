<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use think\facade\Log;

class UserInfoMapDao extends ModelDao
{
    protected $table = 'zb_user_info_map';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserInfoMapDao();
        }
        return self::$instance;
    }

    public function getUserIdByPrettyId($prettyId)
    {
        return $this->getModel()->where(['type' => 'pretty', 'value' => $prettyId])->value('user_id');
    }

    public function getUserIdByNickname($nickname)
    {
        return $this->getModel()->where(['type' => 'nickname', 'value' =>$nickname])->value('user_id');
    }

    public function delUserInfoMap($userId) {
        $this->getModel()->where(['user_id' => $userId])->delete();
    }

    public function updatePretty($prettyId, $userId)
    {
        $where = ['user_id' => $userId, 'type' => 'pretty'];
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            $data = [
                'value' => $prettyId,
            ];
            $this->getModel()->where($where)->update($data);
        }else{
            $this->addByPretty($prettyId, $userId);
        }
    }

    public function updateNickname($nickname, $userId)
    {
        $where = ['user_id' => $userId, 'type' => 'nickname'];
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            $data = [
                'value' => $nickname,
            ];
            $this->getModel()->where($where)->update($data);
        }else{
            $this->addByNickname($nickname, $userId);
        }
    }

    public function addByPretty($prettyId, $userId)
    {
        $data = [
            'type' => 'pretty',
            'value' => $prettyId,
            'user_id' => $userId
        ];
        $this->getModel()->insert($data);
    }

    public function addByNickname($nickname, $userId)
    {
        $data = [
            'type' => 'nickname',
            'value' => $nickname,
            'user_id' => $userId
        ];
        $this->getModel()->insert($data);
    }
}