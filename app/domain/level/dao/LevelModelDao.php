<?php


namespace app\domain\level\dao;


use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\level\model\LevelModel;
use app\query\user\cache\CachePrefix;

class LevelModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new LevelModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new LevelModel();
        $model->level = $data['lv_dengji'];
        $model->levelExp = $data['level_exp'];
        $model->vipLevel = $data['is_vip'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'lv_dengji' => $model->level,
            'level_exp' => $model->levelExp
        ];
    }

    public function loadLevel($userId) {
        $data = $this->getModel($userId)->field('lv_dengji,level_exp,freecoin,is_vip')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function saveLevel($userId, $model) {
        $data = $this->modelToData($model);
        $this->getModel($userId)->where([
            'id' => $userId
        ])->update($data);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
    }
}