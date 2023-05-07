<?php

namespace app\domain\withdraw\dao;

use app\core\mysql\ModelDao;
use app\domain\withdraw\model\WithdrawWhiteList;

//提现白名单
class WithdrawWhiteListDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_withdraw_white_list';
    protected static $instance;
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @param WithdrawWhiteList $model
     * @return array
     */
    public function modelToData(WithdrawWhiteList $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'enable' => $model->enable,
            'create_time' => $model->createTime,
            'admin_id' => $model->adminId,
        ];
    }

    /**
     * @param $data
     * @return WithdrawWhiteList
     */
    public function dataToModel($data)
    {
        $model = new WithdrawWhiteList();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->enable = $data['enable'];
        $model->createTime = $data['create_time'];
        $model->adminId = $data['admin_id'];
        return $model;
    }

    /**
     * @param $userId
     * @return WithdrawWhiteList|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($userId)
    {
        if (empty($userId)) {
            return null;
        }
        $where['user_id'] = $userId;
        $where['enable'] = 1;
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        // 获取用户角色是否为白名单用户
        return $this->dataToModel($data);
    }


}