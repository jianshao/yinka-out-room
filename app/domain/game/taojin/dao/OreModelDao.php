<?php


namespace app\domain\game\taojin\dao;


use app\core\mysql\ModelDao;
use app\domain\game\taojin\model\OreModel;
use app\domain\game\taojin\OreTypes;
use app\utils\ArrayUtil;


class OreModelDao extends ModelDao
{
    protected $table = 'zb_user_extend';
    protected $serviceName = 'userMaster';

    protected static $instance;
    protected $ORE_TYPE = null;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new OreModelDao();

            self::$instance->ORE_TYPE = [
                OreTypes::$IRON => 'iron_ore',
                OreTypes::$SILVER => 'silver_ore',
                OreTypes::$GOLD => 'gold_ore',
                OreTypes::$FOSSIL => 'fossil_ore',
            ];
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new OreModel();
        $model->oreMap[OreTypes::$IRON] = $data['iron_ore'];
        $model->oreMap[OreTypes::$SILVER] = $data['silver_ore'];
        $model->oreMap[OreTypes::$GOLD] = $data['gold_ore'];
        $model->oreMap[OreTypes::$FOSSIL] = $data['fossil_ore'];
        $model->updateTime = $data['update_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'iron_ore' => ArrayUtil::safeGet($model->oreMap, OreTypes::$IRON, 0),
            'silver_ore' => ArrayUtil::safeGet($model->oreMap, OreTypes::$SILVER, 0),
            'gold_ore' => ArrayUtil::safeGet($model->oreMap, OreTypes::$GOLD, 0),
            'fossil_ore' => ArrayUtil::safeGet($model->oreMap, OreTypes::$FOSSIL, 0),
            'update_time' => $model->updateTime
        ];
    }

    public function loadOre($userId) {
        $data = $this->getModel($userId)->where(['uid' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function incOre($userId, $oreType, $count, $updateTime) {
        assert($count >= 0);
        return $this->getModel($userId)->where(['uid' => $userId])->inc($this->ORE_TYPE[$oreType], $count)->update(['update_time' => $updateTime]);
    }

    public function decOre($userId, $oreType, $count, $updateTime) {
        assert($count >= 0);
        $whereStr = sprintf('uid=%d and %s >= %d', $userId, $this->ORE_TYPE[$oreType], $count);
        return $this->getModel($userId)->whereRaw($whereStr)->dec($this->ORE_TYPE[$oreType], $count)->update(['update_time' => $updateTime]);
    }

    public function saveOre($userId, $oreModel) {
        $data = $this->modelToData($oreModel);
        $data['uid'] = $userId;
        $this->getModel($userId)->save($data);
    }
}