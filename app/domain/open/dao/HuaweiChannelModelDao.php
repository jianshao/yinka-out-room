<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\HuaweiChannelModel;


class HuaweiChannelModelDao extends ModelDao
{
    protected $table = 'bi_channel_huawei';
    protected $serviceName = 'biMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->pk = 'id';
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new HuaweiChannelModel();
        $model->userId = $data['user_id'] ?? 0;
        $model->deviceId = $data['device_id'] ?? "";
        $model->taskid = $data['hw_taskid'] ?? "";
        $model->channel = $data['hw_channel'] ?? "";
        $model->ctime = $data['ctime'] ?? 0;
        $model->oaid = $data['oaid'] ?? "";
        $model->callback = $data['callback'] ?? "";
        $model->rtaid = $data['rtaid'] ?? "";
        $model->subTaskId = $data['sub_task_id'] ?? "";
        return $model;
    }


    /**
     * @param HuaweiChannelModel $model
     * @return array
     *
     */
    public function modelToData(HuaweiChannelModel $model)
    {
        return [
            'user_id' => $model->userId,
            'device_id' => $model->deviceId,
            'hw_taskid' => $model->taskid,
            'hw_channel' => $model->channel,
            'ctime' => $model->ctime,
            'oaid' => $model->oaid,
            'callback' => $model->callback,
            'rtaid' => $model->rtaid,
            'sub_task_id' => $model->subTaskId,
        ];
    }


    /**
     * @param HuaweiChannelModel $model
     * @return int|string
     */
    public function insertOrUpdateMul(HuaweiChannelModel $model)
    {
        $exceptUniq = $this->loadExceptUniq();
        $data = $this->modelToData($model);
        return $this->getModel()->extra("IGNORE")->duplicate($exceptUniq)->insert($data);
    }

    /**
     * @return string
     */
    private function loadExceptUniq()
    {
        $unique = $this->getUniqueFiled();
        $getfield = $this->getModel()->getConnection()->getFields($this->table);;
        $updateFields = array_diff(array_keys($getfield), $unique);
        return implode(",", $updateFields);
    }

    /**
     * @return string[]
     */
    private function getUniqueFiled()
    {
        return ["user_id", "id"];
    }


    /**
     * @param $oaid
     * @return HuaweiChannelModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForDeviceId($oaid)
    {
        if (empty($oaid)) {
            return null;
        }
        $where[] = ['oaid', '=', $oaid];
        $object = $this->getModel()->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }
}




























