<?php

namespace app\web\model;

use app\core\mysql\ModelDao;

class OpenInstallModel extends ModelDao
{
    protected $serviceName = 'biMaster';
    protected $table = 'bi_openinstall';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new OpenInstallModel();
        }
        return self::$instance;
    }

    /**
     * 获得设备信息
     * @param [string] $key 客户端打包jey值
     * @param [string] $encry_key_1 三个参数key值
     * @param [string] $encry_key_2 五个参数key值
     *
     * @return
     */
    public function getDevice($ip, $key, array $inputs)
    {
        $scWidth = $inputs['scWidth'];
        $scHeight = $inputs['scHeight'];
        $pixelRatio = $inputs['pixelRatio'];
        $version = $inputs['version'];
        $renderer = $inputs['renderer'];

        $time = date('Y-m-d', strtotime('-1 day'));
        $where_1 = [
            ['ip', '=', $ip],
            ['key', '=', $key],
            ['created', '>=', $time],
        ];
        $where_2 = [
            ['ip', '=', $ip],
            ['key', '=', $key],
            ['sc_width', '=', $scWidth],
            ['sc_height', '=', $scHeight],
            ['pixel_ratio', '=', $pixelRatio],
            ['created', '>=', $time],
        ];
        $where_3 = [
            ['ip', '=', $ip],
            ['key', '=', $key],
            ['sc_width', '=', $scWidth],
            ['sc_height', '=', $scHeight],
            ['pixel_ratio', '=', $pixelRatio],
            ['version', '=', $version],
            ['renderer', '=', $renderer],
            ['created', '>=', $time],
        ];
        return $this->getModel()
            ->whereOr([$where_1, $where_2, $where_3])
            ->order('id', 'desc')
            ->find();
    }

    /**
     * 获得设备信息
     * @param [string] $key 客户端打包jey值
     * @param [string] $encry_key_1 三个参数key值
     * @param [string] $encry_key_2 五个参数key值
     *
     * @return
     */
    public  function getDeviceOld($ip, $key, array $encry_keys)
    {
        return $this->getModel()->where('key', $key)->where('ip', $ip)->whereIn('encry_key', $encry_keys)
            ->find();
    }

    public function updateOne($ip, $key, array $encry_keys, array $updates)
    {
        return $this->getModel()->where([
            ['key', '=', $key],
            ['ip', '=', $ip],
            ['encry_key', 'in', $encry_keys],
        ])->update($updates);
    }

    /**
     * @param $data
     * @return int|string
     * @throws \app\domain\exceptions\FQException
     */
    public function storeData($data){
        return $this->getModel()->insertGetId($data);
    }

}