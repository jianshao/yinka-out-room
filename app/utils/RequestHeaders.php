<?php

namespace app\utils;

class RequestHeaders
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $imei;

    /**
     * @var string
     */
    public $deviceId;

    /**
     * @var string
     */
    public $channel;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $source;

    /**
     * @var string
     */
    public $platform;

    /**
     * @var string
     */
    public $encrypt;

    public function dataToModel($data){
        $this->id=ArrayUtil::safeGet($data,"id","");
        $this->imei=ArrayUtil::safeGet($data,"imei","");
        $this->deviceId=ArrayUtil::safeGet($data,"deviceid","");
        $this->channel=ArrayUtil::safeGet($data,"channel","");
        $this->version=ArrayUtil::safeGet($data,"version","");
        $this->source=ArrayUtil::safeGet($data,"source","");
        $this->platform=ArrayUtil::safeGet($data,"platform","");
        $this->encrypt=ArrayUtil::safeGet($data,"encrypt","");
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatFormOs()
    {
        $pos = strpos($this->platform, "Android");
        if ($pos !== false) {
            return "Android";
        }
        $pos = strpos($this->platform, "iOS");
        if ($pos !== false) {
            return "iOS";
        }
        return "";
    }

}

