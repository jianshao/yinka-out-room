<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\KuaishouReportModel;
use think\Model;
class KuaishouReportModelDao extends ModelDao
{
    protected $table = 'zb_promote_report';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KuaishouReportModelDao();
            self::$instance->pk = 'id';
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new KuaishouReportModel();
        $model->id = $data['id'];
        $model->factoryType = $data['factory_type'];
        $model->missionId = $data['mission_id'];
        $model->orderId = $data['order_id'];
        $model->idfaMD5 = $data['idfa_md5'];
        $model->imeiMD5 = $data['imei_md5'];
        $model->callbackUrl = $data['callback_url'];
        $model->strDate = $data['str_date'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelToData(KuaishouReportModel $model)
    {
        return [
            'id' => $model->id,
            'factory_type' => $model->factoryType,
            'mission_id' => $model->missionId,
            'order_id' => $model->orderId,
            'idfa_md5' => $model->idfaMD5,
            'imei_md5' => $model->imeiMD5,
            'callback_url' => $model->callbackUrl,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }

    /**
     * @param $id
     * @return KuaishouReportModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadModelForId($id)
    {
        $data = $this->getModel($this->shardingId)->where(['id' => $id])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @param KuaishouReportModel $model
     * @return int|string
     */
    public function storeModel(KuaishouReportModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    public function LoadModelForIdfa($idfaMD5)
    {
        $id = $this->getModel($this->shardingId)->where('idfa_md5', $idfaMD5)->order("id desc")->limit(1)->column('id');
        if (empty($id)) {
            return null;
        }
        return $this->LoadModelForId($id);
    }

    public function LoadModelForImei($imeiMD5)
    {
        $id = $this->getModel($this->shardingId)->where('imei_md5', $imeiMD5)->order('id desc')->limit(1)->column('id');
        if (empty($id)) {
            return null;
        }
        return $this->LoadModelForId($id);
    }

}




























