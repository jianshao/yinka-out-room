<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\BiZhanReportModel;
use app\domain\open\model\PromoteFactoryTypeModel;


class BiZhanReportModelDao extends ModelDao
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

    /**
     * @param $data
     * @return BiZhanReportModel
     */
    public function dataToModel($data)
    {
        $model = new BiZhanReportModel();
        $model->id = $data['id'];
        $model->factoryType = $data['factory_type'];
        $model->trackId = $data['mission_id'];
        $model->accountId = $data['ext_1'];
        $model->campaignId = $data['aid'];
        $model->unitId = $data['order_id'];
        $model->creativeId = $data['cid'];
        $model->os = $data['os'];
        $model->imei = $data['imei_md5'];
        $model->callbackUrl = $data['callback_url'];
        $model->mac1 = $data['mac'];
        $model->idfaMd5 = $data['idfa_md5'];
        $model->aaId = $data['ext_2'];
        $model->androidId = $data['androidid'];
        $model->oaidMd5 = $data['oaid'];
        $model->ts = $data['tempstamp'];
        $model->strDate = $data['str_date'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    /**
     * @param BiZhanReportModel $model
     * @return array
     */
    public function modelToData(BiZhanReportModel $model)
    {
        return [
            'id' => $model->id,
            'factory_type' => $model->factoryType,
            'mission_id' => $model->trackId,
            'ext_1' => $model->accountId,
            'aid' => $model->campaignId,
            'order_id' => $model->unitId,
            'cid' => $model->creativeId,
            'os' => $model->os,
            'imei_md5' => $model->imei,
            'callback_url' => $model->callbackUrl,
            'mac' => $model->mac1,
            'idfa_md5' => $model->idfaMd5,
            'ext_2' => $model->aaId,
            'androidid' => $model->androidId,
            'oaid' => $model->oaidMd5,
            'tempstamp' => $model->ts,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
        ];
    }


    /**
     * @param BiZhanReportModel $model
     * @return int|string
     */
    public function storeModel(BiZhanReportModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }


    /**
     * @param $oaid
     * @return BiZhanReportModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForDeviceId($imei, $oaid)
    {
        $BiZhanReportModel = null;
        if ($imei !== "") {
            $BiZhanReportModel = $this->loadModelForImei($imei);
        }
        if ($BiZhanReportModel === null && $oaid !== "") {
            $BiZhanReportModel = $this->loadModelForOaid($oaid);
        }

        return $BiZhanReportModel;
    }


    /**
     * @param $imei
     * @return BiZhanReportModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForImei($imei)
    {
        if (empty($imei)) {
            return null;
        }
        $where[] = ['imei_md5', '=', $imei];
        $where[] = ['factory_type', "=", PromoteFactoryTypeModel::$BIZHAN];
        $tempArr = $this->getModel()->where($where)->order("id asc")->field("id")->column("id");
        if (empty($tempArr)) {
            return null;
        }
        $pkId = current($tempArr);
        $data = $this->loadModelForId($pkId);
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
    }


    /**
     * @param $oaid
     * @return BiZhanReportModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForOaid($oaid)
    {
        if (empty($oaid)) {
            return null;
        }
        $where[] = ['oaid', '=', $oaid];
        $where[] = ['factory_type', "=", PromoteFactoryTypeModel::$BIZHAN];
        $tempArr = $this->getModel()->where($where)->order("id asc")->field("id")->column("id");
        if (empty($tempArr)) {
            return null;
        }
        $pkId = current($tempArr);
        $data = $this->loadModelForId($pkId);
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
    }

    /**
     * @param $id
     * @return array|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function loadModelForId($id)
    {
        if (empty($id)) {
            return null;
        }
        $object = $this->getModel()->where(['id' => $id])->find();
        if ($object === null) {
            return null;
        }
        return $object->toArray();
    }


    /**
     * @param $idfa
     * @return BiZhanReportModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function LoadModelForIdfa($idfa)
    {
        if (empty($idfa)) {
            return null;
        }
        $where[] = ['idfa_md5', '=', $idfa];
        $where[] = ['factory_type', "=", PromoteFactoryTypeModel::$BIZHAN];
        $tempArr = $this->getModel()->where($where)->order("id asc")->field("id")->column("id");
        if (empty($tempArr)) {
            return null;
        }
        $pkId = current($tempArr);
        $data = $this->loadModelForId($pkId);
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
    }

}




























