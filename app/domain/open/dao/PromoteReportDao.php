<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\KuaishouReportModel;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\open\model\ToutiaoReportModel;

class PromoteReportDao extends ModelDao
{
    protected $table = 'zb_promote_report';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PromoteReportDao();
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
     * @return KuaishouReportModel|ToutiaoReportModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadModelForId($id)
    {
        $object = $this->getModel($this->shardingId)->where(['id' => $id])->find();
        if (empty($object)) {
            return null;
        }
        $data = $object->toArray();
        if ($data['factory_type'] === PromoteFactoryTypeModel::$TOUTIAO) {
            return ToutiaoReportModelDao::getInstance()->dataToModel($data);
        }

        if ($data['factory_type'] === PromoteFactoryTypeModel::$XINGTU) {
            return ToutiaoReportModelDao::getInstance()->dataToModel($data);
        }

        if ($data['factory_type'] === PromoteFactoryTypeModel::$JUXING) {
            return KuaishouReportModelDao::getInstance()->dataToModel($data);
        }
        if ($data['factory_type'] === PromoteFactoryTypeModel::$XINGTU) {
            return ToutiaoReportModelDao::getInstance()->dataToModel($data);
        }
        return null;
    }

    /**
     * @param $idfaMD5
     * @return KuaishouReportModel|ToutiaoReportModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadModelForIdfa($idfaMD5)
    {
        if (empty($idfaMD5)) {
            return null;
        }
        $id = $this->getModel($this->shardingId)->where('idfa_md5', $idfaMD5)->order("id asc")->limit(1)->column('id');
        if (empty($id)) {
            return null;
        }
        return $this->LoadModelForId($id);
    }

    /**
     * @param $idfaMD5
     * @return mixed|null
     * @throws \app\domain\exceptions\FQException
     */
    public function LoadChannelForIdfaColumn($idfaMD5)
    {
        if (empty($idfaMD5)) {
            return null;
        }
        $tempArr = $this->getModel($this->shardingId)->where('idfa_md5', $idfaMD5)->order("id asc")->limit(1)->column('factory_type');
        if (empty($tempArr)) {
            return null;
        }
        return current($tempArr);
    }


    /**
     * @param $imeiMD5
     * @param string $oaid
     * @param string $androidid
     * @return KuaishouReportModel|ToutiaoReportModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadModelForImei($imeiMD5, $oaid = "")
    {
        if ($imeiMD5 !== "") {
            $id = $this->getModel($this->shardingId)->where('imei_md5', $imeiMD5)->order('id asc')->limit(1)->column('id');
            if (!empty($id)) {
                return $this->LoadModelForId($id);
            }
        }
        if ($oaid !== "" && $oaid !== "00000000-0000-0000-0000-000000000000") {
            $id = $this->getModel($this->shardingId)->where('oaid', $oaid)->order('id asc')->limit(1)->column('id');
            if (!empty($id)) {
                return $this->LoadModelForId($id);
            }
        }
        return null;
    }

}




























