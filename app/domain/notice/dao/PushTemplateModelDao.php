<?php

namespace app\domain\notice\dao;

use app\core\mysql\ModelDao;
use app\domain\notice\model\PushTemplateModel;
use think\Model;
class PushTemplateModelDao extends ModelDao
{
    protected $table = 'zb_push_template';
    protected $cacheKey = "push_template";
    protected static $instance;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PushTemplateModelDao();
        }
        return self::$instance;
    }


    public function dataToModel($data)
    {
        $model = new PushTemplateModel();
        $model->id = $data['id'];
        $model->originId = $data['origin_id'];
        $model->title = $data['title'];
        $model->content = $data['content'];
        $model->type = $data['type'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        $model->template_name = $data['template_name'];
        return $model;
    }


    public function modelTodata(PushTemplateModel $model)
    {
        return [
            'id' => $model->id,
            'origin_id' => $model->originId,
            'content' => $model->content,
            'type' => $model->type,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
            'template_name' => $model->template_name,
        ];
    }


    /**
     * @param $id
     * @return PushTemplateModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($id)
    {
        if (empty($id)) {
            return null;
        }
        $cacheKey = sprintf("%s:id:%d", $this->cacheKey, $id);
        $object = $this->getModel($this->shardingId)->where('id', $id)->cache($cacheKey, 120)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @param PushTemplateModel $model
     * @return int|string
     */
    public function storeData(PushTemplateModel $model)
    {
        $data = $this->modelTodata($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }
}












