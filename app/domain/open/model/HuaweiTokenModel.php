<?php

namespace app\domain\open\model;

/**
 * 华为token模型
 * Class HuaweiTokenModel
 * @TODO  https://developer.huawei.com/consumer/cn/doc/distribution/promotion/ocpd-obtain-token-0000001147372704
 * @package app\domain\promote\model
 */
class HuaweiTokenModel
{
    public $accessToken = "";   //认证Token，用于接口调用。此参数只在获取成功时返回。
    public $expires_in = 0;  //access_token的有效期，单位秒。您需要在过期时间到达时重新调用本接口获取新的access_token。此参数只在获取成功时返回。

    public function modelToData()
    {
        return [
            'access_token' => $this->accessToken,
            'expires_in' => $this->expires_in,
        ];

    }

    public function dataToModel($data)
    {
        $this->accessToken = $data['access_token'] ?? "";
        $this->expires_in = $data['expires_in'] ?? 0;
    }
}


