<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\AvatarLibraryModel;

//更改为缓存配置
class AvatarLibraryDao extends ModelDao
{
    protected $table = 'zb_avatar_library';
    protected $serviceName = 'commonMaster';
    protected $pk = 'id';
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AvatarLibraryDao();
        }
        return self::$instance;
    }


    /**
     * @param $data
     * @return AvatarLibraryModel
     */
    public function dataToModel($data)
    {
        $model = new AvatarLibraryModel();
        $model->id = $data['id'];
        $model->href = $data['href'];
        $model->sex = $data['sex'];
        $model->createTime = $data['create_time'];
        $model->status = $data['status'];
        return $model;
    }


    public function store($data)
    {
        if (empty($data)) {
            return false;
        }
        return $this->getModel()->create($data);
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getManList()
    {
        $where[] = ['status', '=', 1];
        $where[] = ['sex', '=', 1];
        $data = $this->getModel()->where($where)->select();

        $result = [];
        foreach ($data as $item) {
            $result[] = $this->dataToModel($item);
        }
        return $result;
    }

    public function getWomanList()
    {
        $where[] = ['status', '=', 1];
        $where[] = ['sex', '=', 2];
        $data = $this->getModel()->where($where)->select();

        $result = [];
        foreach ($data as $item) {
            $result[] = $this->dataToModel($item);
        }
        return $result;
    }
}