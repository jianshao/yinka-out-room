<?php
/**
 * User: yond
 * Date: 2020
 * 注册埋点统计
 */

namespace app\common\model;

use app\core\mysql\ModelDao;
use app\form\ClientInfo;

class RegisterDataModel extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_register_data';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RegisterDataModel();
        }
        return self::$instance;
    }

    public function StoreDeviceData(ClientInfo $clientInfo)
    {
        return $this->getInstance()->getModel()->save(['date' => date('Y-m-d'),
            'deviceid' => $clientInfo->deviceId,
            'device' => $clientInfo->device,
            'version' => $clientInfo->version,
            'channel' => $clientInfo->channel,
            'paltform' => $clientInfo->platform,
            'source' => $clientInfo->source
        ]);
    }


}