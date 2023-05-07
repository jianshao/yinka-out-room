<?php


namespace app\domain\withdraw\dao;


use app\core\mysql\ModelDao;
use app\domain\withdraw\model\WithdrawUser;
use app\query\user\dao\UserModelDao;

//提现用户dao
class WithdrawUserDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_member';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data, $userRole)
    {
        $model = new WithdrawUser();
        $model->userModel = UserModelDao::getInstance()->dataToModel($data);
        $model->userRole = $userRole;
        return $model;
    }

    /**
     * @param $userId
     * @return WithdrawUser|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadUser($userId)
    {
        $data = $this->getModel($userId)->where(['id' => $userId])->find();
        if ($data === null) {
            return null;
        }
        // 获取用户角色是否为白名单用户
        $userRole = $this->loadUserRole($userId);
        return $this->dataToModel($data, $userRole);
    }

    /**
     * load 用户的角色 【白名单，普通用户】
     * @param $userId
     */
    public function loadUserRole($userId)
    {
        $model = WithdrawWhiteListDao::getInstance()->loadModel($userId);
        if ($model !== null && $model->id !== 0) {
            return WithdrawUser::$specialUser;
        }
        return WithdrawUser::$NormalUser;
    }


}