<?php

namespace app\domain\open\model;


class BiZhanCallbackModel
{
    /**
     * @var string
     * @info 转化类型
     */
    public $convType;

    /**
     * @var string
     * @info 转化时间发生时间
     */
    public $convTime;

    /**
     * @var string
     * @info 转化的价值，单位是分，100 分为一元
     */
    public $convValue;

    /**
     * @var string
     * @info 转化数量，单位是个
     */
    public $convCount;

    /**
     * @var string
     * @info 终端的设备 ID，仅安卓设备上报
     */
    public $imei;

    /**
     * @var string
     * @info iOS 广告标识
     */
    public $idfa;

    /**
     * @var string
     * @info 终端的设备 ID，仅安卓设备上报
     */
    public $oaid;

    /**
     * @var string
     * @info 用户终端的 eth0 接口的 MAC 地址（大写保留冒号分隔符）
     */
    public $mac;

    /**
     * @var string
     * @info 用于追踪来源广告的追踪 ID
     */
    public $trackId;

    /**
     * @return array
     */
    public function modelToData()
    {
        return [
            "conv_type" => $this->convType,
            "conv_time" => $this->convTime,
            "conv_value" => $this->convValue,
            "conv_count" => $this->convCount,
            "imei" => $this->getImei(),
            "idfa" => $this->getIdfa(),
            "oaid" => $this->getOaid(),
            "mac" => $this->mac,
            "track_id" => $this->trackId,
        ];
    }

    public function getImei()
    {
        if ($this->imei === "__IMEI__") {
            return "";
        }
        return $this->imei;
    }

    public function getIdfa()
    {
        if ($this->idfa === '9f89c84a559f573636a47ff8daed0d33' || $this->idfa === "__IDFAMD5__") {
            return "";
        }
        return $this->idfa;
    }


    public function getOaid()
    {
        if ($this->oaid === "__OAIDMD5__") {
            return "";
        }
        return $this->oaid;
    }

}

