<?php


namespace app\domain\open\dao;


use app\core\mysql\ModelDao;
use app\domain\open\model\ToutiaoReportModel;
use think\Model;

class ToutiaoReportModelDao extends ModelDao
{
    protected $table = 'zb_promote_report';
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ToutiaoReportModelDao();
            self::$instance->pk = 'id';
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return ToutiaoReportModel
     */
    public function dataToModel($data)
    {
        $model = new ToutiaoReportModel();
        $model->id = $data['id'];
        $model->factoryType = $data['factory_type'];
        $model->aid = $data['aid'];
        $model->cid = $data['cid'];
        $model->idfa = $data['idfa_md5'];
        $model->imei = $data['imei_md5'];
        $model->mac = $data['mac'];
        $model->oaid = $data['oaid'];
        $model->androidid = $data['androidid'];
        $model->os = $data['os'];
        $model->tempstamp = $data['tempstamp'];
        $model->callback = $data['callback_url'];
        $model->strDate = $data['str_date'];
        $model->createTime = $data['create_time'];
        $model->ext1 = $data['ext_1'];
        $model->ext2 = $data['ext_2'];
        return $model;
    }

    public function modelToData(ToutiaoReportModel $model)
    {
        return [
            'id' => $model->id,
            'factory_type' => $model->factoryType,
            'aid' => $model->aid,
            'cid' => $model->cid,
            'idfa_md5' => $model->idfa ?? "",
            'imei_md5' => $model->imei ?? "",
            'mac' => $model->mac,
            'oaid' => $model->oaid,
            'androidid' => $model->androidid,
            'os' => $model->os,
            'tempstamp' => $model->tempstamp,
            'callback_url' => $model->callback,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
            'ext_1' => $model->ext1,
            'ext_2' => $model->ext2,
        ];
    }

    /**
     * @param $id
     * @return ToutiaoReportModel|null
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
     * @param ToutiaoReportModel $model
     * @return int|string
     */
    public function storeModel(ToutiaoReportModel $model)
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




























