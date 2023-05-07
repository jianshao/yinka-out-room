<?php


namespace app\query\weshine\dao;


use app\core\mysql\ModelDao;
use app\query\weshine\model\ShineBlackKeywordModel;

class ShineBlackKeywordModelDao extends ModelDao
{
    protected $table = 'zb_shine_black_keyword';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new ShineBlackKeywordModel();
        $model->id = $data['id'];
        $model->keyword = $data['keyword'];
        $model->keywordHash = $data['keyword_hash'];
        $model->createTime = $data['create_time'];
        $model->adminId = $data['admin_id'];
        return $model;
    }

    public function modelToData(ShineBlackKeywordModel $model)
    {
        return [
            'is_vip' => $model->id,
            'keyword' => $model->keyword,
            'keyword_hash' => $model->keywordHash,
            'create_time' => $model->createTime,
            'admin_id' => $model->adminId,
        ];
    }

    /**
     * @param $keyword
     * @return ShineBlackKeywordModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForKeyword($keyword)
    {
        if (empty($keyword)) {
            return null;
        }
        $where['keyword_hash'] = md5($keyword);
        $object = $this->getModel()->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }
}