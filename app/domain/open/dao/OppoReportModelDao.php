<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\OppoReportModel;
use app\domain\open\model\PromoteFactoryTypeModel;


class OppoReportModelDao extends ModelDao
{
    protected $table = 'zb_promote_report';
    protected $serviceName = 'commonMaster';

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

    /**
     * @param $data
     * @return OppoReportModel
     */
    public function dataToModel($data)
    {
        $model = new OppoReportModel();
        $model->id = $data['id'];
        $model->factoryType = $data['factory_type'];
        $model->adid = (int)$data['aid'];
        $model->imeiMd5 = $data['imei_md5'];
        $model->oaid = $data['oaid'];
        $model->androidId = $data['androidid'];
        $model->tempstamp = $data['tempstamp'];
        $model->strDate = $data['str_date'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelToData(OppoReportModel $model)
    {
        return [
            'id' => $model->id,
            'factory_type' => $model->factoryType,
            'aid' => $model->adid,
            'imei_md5' => $model->imeiMd5 ?? "",
            'oaid' => $model->oaid,
            'androidid' => $model->androidId,
            'tempstamp' => $model->tempstamp,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }


    /**
     * @param OppoReportModel $model
     * @return int|string
     */
    public function storeModel(OppoReportModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }



    /**
     * @param $oaid
     * @return OppoReportModel|null
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
        $where[] = ['factory_type', "=", PromoteFactoryTypeModel::$OPPO];
        $object = $this->getModel()->where($where)->order("id asc")->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

}




























