<?php


namespace app\domain\duke\dao;


use app\common\RedisCommon;
use app\core\mysql\ModelDao;
use app\domain\duke\model\DukeModel;
use app\query\user\cache\CachePrefix;

class DukeModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DukeModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new DukeModel();
        $model->dukeLevel = $data['duke_id'];
        $model->dukeExpiresTime = $data['duke_expires'];
        $model->dukeValue = $data['duke_value'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'duke_id' => $model->dukeLevel,
            'duke_expires' => $model->dukeExpiresTime,
            'duke_value' => $model->dukeValue
        ];
    }

    /**
     * 根据用户ID加载爵位
     *
     * @param userId: 哪个用户
     * @return DukeModel 找到返回DukeModel，没有则返回null
     */
    public function loadDuke($userId) {
        $data = $this->getModel($userId)->field('duke_id,duke_expires,duke_value')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 保存
     *
     * @param $userId
     * @param $dukeModel
     */
    public function saveDuke($userId, $dukeModel) {
        $data = $this->modelToData($dukeModel);
        $this->getModel($userId)->where(['id' => $userId])->update($data);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(CachePrefix::$USER_INFO_CACHE, $userId));
    }
}