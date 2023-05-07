<?php


namespace app\domain\adserving;


use app\core\mysql\ModelDao;

class AdServingModelDao extends ModelDao
{
    protected $table = 'zb_ad_serving';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AdServingModelDao();
        }
        return self::$instance;
    }

    public function saveData($params) {
        if (!empty($params)) {
            $source = $params['source'];
            $appId = $params['appid'];
            $idfa = $params['idfa'];
            $callback = $params['callback'];
            $data = $this->getModel()->where(['source' => $source, 'appid' => $appId, 'idfa' => $idfa])->find();
            if (!empty($data)) {
                $updateData = [
                    'source' => $source,
                    'appid' => $appId,
                    'idfa' => $idfa,
                    'callbackaddress' => urldecode($callback),
                    'created_time' => time()
                ];
                $this->getModel()->where(['id' => $data['id']])->update($updateData);
            } else {
                $insertData = [
                    'source' => (string)$source,
                    'appid' => (int)$appId,
                    'idfa' => (string)$idfa,
                    'callbackaddress' => urldecode($callback),
                    'created_time' => time()
                ];
                $this->getModel()->insert($insertData);
            }
        }
    }

    public function updateDataByWhere($where, $data) {
        $this->getModel()->where($where)->update($data);
    }

    public function findOne($where) {
        $data = $this->getModel()->where($where)->order('id','desc')->find();
        if (empty($data)) {
            return [];
        }
        return $data;
    }

}