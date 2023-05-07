<?php


namespace app\domain\vip\dao;


use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\vip\model\VipModel;
use app\query\user\cache\CachePrefix;

class VipModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new VipModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new VipModel();
        $model->level = $data['is_vip'];
        $model->vipExpiresTime = $data['vip_exp'];
        $model->svipExpiresTime = $data['svip_exp'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'is_vip' => $model->level,
            'vip_exp' => $model->vipExpiresTime,
            'svip_exp' => $model->svipExpiresTime,
        ];
    }

    public function loadVip($userId) {
        $data = $this->getModel($userId)->field('is_vip,vip_exp,svip_exp')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function saveVip($userId, $model) {
        $data = $this->modelToData($model);
        $this->getModel($userId)->where([
            'id' => $userId
        ])->update($data);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
    }
}