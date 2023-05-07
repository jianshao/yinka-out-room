<?php

namespace app\domain\open\model;

class HuaweiCallbackModel
{
    /**
     * @var string
     */
    public $appId;

    /**
     * @var string
     */
    public $deviceIdType;

    /**
     * @var string
     */
    public $deviceId;

    /**
     * @var int
     */
    public $actionTime;

    /**
     * @var string
     */
    public $actionType;

    /**
     * @var string
     */
    public $callBack;


    /**
     * @return array
     */
    public function modelToData()
    {
        return [
            "appId" => $this->appId,
            "deviceIdType" => $this->deviceIdType,
            "deviceId" => $this->deviceId,
            "actionTime" => $this->actionTime,
            "actionType" => $this->actionType,
            "callBack" => $this->callBack,
        ];
    }
}

