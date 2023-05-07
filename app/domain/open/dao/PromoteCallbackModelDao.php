<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\PromoteCallbackModel;
use think\Model;

class PromoteCallbackModelDao extends ModelDao
{
    protected $table = 'zb_promote_callback';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PromoteCallbackModelDao();
            self::$instance->pk = 'id';
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new PromoteCallbackModel();
        return $model;
    }

    public function modelToData(PromoteCallbackModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'factory_type' => $model->factoryType,
            'idfa_md5' => $model->idfaMD5,
            'imei_md5' => $model->imeiMD5,
            'oaid' => $model->oaid,
            'callback_url' => $model->callbackUrl,
            'status' => $model->status,
            'event_type' => $model->eventType,
            'response' => $model->response,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }

    /**
     * @param PromoteCallbackModel $model
     * @return int|string
     */
    public function storeModel(PromoteCallbackModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    /**
     * @param PromoteCallbackModel $model
     * @return int|string
     */
    public function getOne($factoryType, $eventType, $idfa, $imei, $oaid)
    {
        if (empty($idfa) && empty($imei) && empty($oaid)) {
            return true;
        }
        $where[] = ['factory_type', '=', $factoryType];
        $where[] = ['event_type', '=', $eventType];
        if ($idfa && $idfa !== '00000000-0000-0000-0000-000000000000') {
            $where[] = ['idfa_md5', '=', $idfa];
        }
        if ($imei) {
            $where[] = ['imei_md5', '=', $imei];
        }
        if ($oaid) {
            $where[] = ['oaid', '=', $oaid];
        }
        $object = $this->getModel($this->shardingId)->where($where)->field("id")->find();
        if ($object === null) {
            return false;
        }
        $data = $object->toArray();
        if (empty($data)) {
            return false;
        }
        return true;
    }


    /**
     * @param $oaid
     * @param $actionType
     * @param $userId
     * @param $factory_type
     * @return mixed
     */
    public function getCallbackStatus($oaid, $actionType, $userId, $factory_type)
    {
        $where[] = ['oaid', "=", $oaid];
        $where[] = ['event_type', "=", $actionType];
        $where[] = ['user_id', "=", $userId];
        $where[] = ['factory_type', "=", $factory_type];
        return $this->getModel($this->shardingId)->where($where)->value("status");
    }

}




























