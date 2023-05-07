<?php

namespace app\domain\notice\dao;

use app\core\mysql\ModelDao;
use app\domain\notice\model\PushReportModel;
use think\Model;
class PushReportModelDao extends ModelDao
{
    protected $table = 'zb_push_report';

    protected static $instance;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PushReportModelDao();
        }
        return self::$instance;
    }


    public function dataToModel($data)
    {
        $model = new PushReportModel();
        $model->id = $data['id'];
        $model->platform = $data['platform'];
        $model->receiver = $data['receiver'];
        $model->pswd = $data['pswd'];
        $model->msgId = $data['msg_id'];
        $model->taskId = $data['task_id'];
        $model->reportTime = $data['report_time'];
        $model->mobile = $data['mobile'];
        $model->status = $data['status'];
        $model->notifyTime = $data['notify_time'];
        $model->statusDesc = $data['status_desc'];
        $model->uid = $data['uid'];
        $model->length = $data['length'];
        $model->originParam = $data['origin_param'];
        $model->createTime = $data['create_time'];
        $model->ext_1 = $data['ext_1'];
        return $model;
    }


    public function modelTodata(PushReportModel $model)
    {
        return [
            'id' => $model->id,
            'platform' => $model->platform,
            'receiver' => $model->receiver,
            'pswd' => $model->pswd,
            'msg_id' => $model->msgId,
            'task_id' => $model->taskId,
            'report_time' => $model->reportTime,
            'mobile' => $model->mobile,
            'status' => $model->status,
            'notify_time' => $model->notifyTime,
            'status_desc' => $model->statusDesc,
            'uid' => $model->uid,
            'length' => $model->length,
            'origin_param' => $model->originParam,
            'create_time' => $model->createTime,
            'ext_1' => $model->ext_1,
        ];
    }


    /**
     * @param PushReportModel $model
     * @return int
     */
    public function store(PushReportModel $model)
    {
        $data = $this->modelTodata($model);
        return (int)$this->getModel($this->shardingId)->insertGetId($data);
    }

}












