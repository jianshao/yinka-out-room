<?php

namespace app\domain\sms\dao;

use app\core\mysql\ModelDao;
use app\domain\sms\model\RongtongdaReportModel;


//短信状态上报模型
class RongtongdaReportModelDao extends ModelDao
{
    protected $table = 'zb_sms_report';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RongtongdaReportModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new RongtongdaReportModel();
        $model->uid = $data['uid'];
        $model->uname = $data['uname'];
        $model->seq = $data['seq'];
        $model->pn = $data['pn'];
        $model->stm = $data['stm'];
        $model->sc = $data['sc'];
        $model->st = $data['st'];
        $model->bid = $data['bid'];
        $model->str_date = $data['str_date'];
        $model->platform = $data['platform'];
        $model->create_time = $data['create_time'];
        $model->origin_data = $data['origin_data'];
        return $model;
    }

    public function modelToData(RongtongdaReportModel $model)
    {
        return [
            'uid' => $model->uid,
            'uname' => $model->uname,
            'seq' => $model->seq,
            'pn' => $model->pn,
            'stm' => $model->stm,
            'sc' => $model->sc,
            'st' => $model->st,
            'bid' => $model->bid,
            'str_date' => $model->str_date,
            'platform' => $model->platform,
            'create_time' => $model->create_time,
            'origin_data' => $model->origin_data,
        ];
    }

    /**
     * @param RongtongdaReportModel $model
     * @return int|string
     */
    public function storeData(RongtongdaReportModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel()->insertGetId($data);
    }
}


