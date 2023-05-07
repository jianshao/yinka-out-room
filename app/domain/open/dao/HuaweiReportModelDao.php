<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\HuaweiReportModel;
use app\domain\open\model\PromoteFactoryTypeModel;


class HuaweiReportModelDao extends ModelDao
{
    protected $table = 'zb_promote_report';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new HuaweiReportModel();
        $model->oaid = $data['oaid'] ?? "";
        $model->factoryType = $data['factory_type'] ?? "";
        $model->callback = $data['callback_url'] ?? "";
        $model->taskid = $data['ext_1'] ?? "";
        $model->channel = $data['ext_2'] ?? "";
        $model->rtaid = $data['ext_3'] ?? "";
        $model->subTaskId = $data['ext_4'] ?? "";
        $model->strDate = $data['str_date'] ?? "";
        $model->createTime = $data['create_time'] ?? "";
        return $model;
    }

    /**
     * @param HuaweiReportModel $model
     * @return array
     *
     */
    public function modelToData(HuaweiReportModel $model)
    {
        return [
            'oaid' => $model->oaid,
            'factory_type' => $model->factoryType,
            'callback_url' => $model->callback,
            'ext_1' => $model->taskid,
            'ext_2' => $model->channel,
            'ext_3' => $model->rtaid,
            'ext_4' => $model->subTaskId,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }


    /**
     * @param HuaweiReportModel $model
     * @return false|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function insertIfExists(HuaweiReportModel $model)
    {
        $where[] = ['oaid', "=", $model->oaid];
        $where[] = ['factory_type', "=", $model->factoryType];
        $dbmodel = $this->getModel()->where($where)->field('id')->find();
        if ($dbmodel !== null) {
            return false;
        }
        return $this->storeModel($model);
    }


    /**
     * @param $model
     * @return int|string
     */
    private function storeModel($model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }

    /**
     * @return string
     */
    private function loadExceptUniq()
    {
        $unique = $this->getUniqueFiled();
        $getfield = $this->getModel()->getConnection()->getFields($this->table);
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
     * @return HuaweiReportModel|null
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
        $where[] = ['factory_type', "=", PromoteFactoryTypeModel::$HUAWEI];
        $object = $this->getModel()->where($where)->order("id asc")->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }
}




























