<?php

namespace app\domain\recall\dao;

use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\recall\model\PushRecallConfModel;
use app\domain\recall\model\PushRecallConfPushWhenModel;


class PushRecallConfModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_push_recall_conf';
    protected $shardingColumn = 0;
    protected $cacheTypeKey = 'c_push_recall_conf';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PushRecallConfModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return PushRecallConfModel
     */
    public function dataToModel($data)
    {
        $pushWhenModel = new PushRecallConfPushWhenModel();
        if (is_string($data['push_when'])){
            $pushWhen = json_decode($data['push_when'], true);
        }else{
            $pushWhen=$data['push_when'];
        }
        $pushWhenModel->dataToModel($pushWhen);
        $templateIds = json_decode($data['template_ids'], true);
        if (!is_array($templateIds)) {
            $templateIds = [];
        }
        $model = new PushRecallConfModel();
        $model->id = $data['id'];
        $model->pushWhen = $pushWhenModel;
        $model->pushType = $data['push_type'];
        $model->templateIds = $templateIds;
        return $model;
    }


    public function modelToData(PushRecallConfModel $model)
    {
        if (is_array($model->pushWhen)) {
            $pushWhenData = $model->pushWhen;
        } else {
            $pushWhenData = $model->pushWhen->modelToData($model->pushWhen);
        }
        return [
            'id' => $model->id,
            'push_type' => $model->pushType,
            'push_when' => json_encode($pushWhenData),
            'template_ids' => json_encode($model->templateIds),
        ];
    }

    private function getTypeDataForCache($type){
        $where[] = ['push_type', '=', $type];
        $where[] = ['is_delete', '=', 0];
        $redis=RedisCommon::getInstance()->getRedis();
        $cacheKey = sprintf("%s:type:%s", $this->cacheTypeKey, $type);
        $cacheData=$redis->get($cacheKey);
        if ($cacheData){
            return json_decode($cacheData,true);
        }
        $object = $this->getModel($this->shardingColumn)->where($where)->select();
        if ($object === null) {
            throw new FQException("zb_push_recall_conf loadTypeDayData error", 500);
        }
        $data=$object->toArray();
        $redis->setex($cacheKey,120,json_encode($data));
        return $data;
    }

    public function loadTypeDayDataToday($type)
    {
        if (empty($type)) {
            throw  new FQException("loadTypedata error", 500);
        }
        $data=$this->getTypeDataForCache($type);
        $boxData = [];
        foreach ($data as $itemData) {
            $boxData[] = $this->dataToModel($itemData);
        }
        $result = [];
        foreach ($boxData as $key => $itemModel) {
            if ($itemModel->pushWhen->time<86400){
                $result[$itemModel->pushWhen->time] = $itemModel;
            }
        }
        if (empty($result)) {
            return [];
        }
        ksort($result);
        return $result;
    }

    public function loadTypeDayData($type)
    {
        if (empty($type)) {
            throw  new FQException("loadTypedata error", 500);
        }
        $data=$this->getTypeDataForCache($type);
        $boxData = [];
        foreach ($data as $itemData) {
            $boxData[] = $this->dataToModel($itemData);
        }
        $result = [];
        foreach ($boxData as $key => $itemModel) {
            $result[$itemModel->pushWhen->time / 86400] = $itemModel;
        }
        if (empty($result)) {
            return [];
        }
        ksort($result);
        return $result;
    }

    public function loadModel($id)
    {
        $object = $this->getModel($this->shardingColumn)->where('id', $id)->find();
        if ($object === null) {
            return [];
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }
}












