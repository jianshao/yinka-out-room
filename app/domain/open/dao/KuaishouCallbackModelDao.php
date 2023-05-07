<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\KuaishouCallbackModel;
use think\Model;

class KuaishouCallbackModelDao extends ModelDao
{
    protected $table = 'zb_promote_callback';
    protected $serviceName = 'commonMaster';
    protected static $instance;
    protected $shardingId = 0;


    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new KuaishouCallbackModelDao();
            self::$instance->pk = 'id';
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new KuaishouCallbackModel();
        return $model;
    }

    public function modelToData(KuaishouCallbackModel $model)
    {
        return [
            'id' => $model->id,
            'factory_type' => $model->factoryType,
            'idfa_md5' => $model->idfaMD5,
            'imei_md5' => $model->imeiMD5,
            'callback_url' => $model->callbackUrl,
            'status' => $model->status,
            'response' => $model->response,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }

    /**
     * @param KuaishouCallbackModel $model
     * @return int|string
     */
    public function storeModel(ToutiaoCallbackModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

}




























