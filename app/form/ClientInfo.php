<?php

namespace app\form;

class ClientInfo
{
    public $clientIp = '';
    public $channel = '';
    public $device = '';
    public $deviceId = '';
    public $platform = '';
    public $version = '';
    public $edition = '';
    public $imei = '';
    public $appId = '';
    public $idfa = '';
    public $source = '';
    public $simulatorInfo = '';
    public $simulator = '';
    public $promoteCode = '';
    public $oaid = '';
    public $androidid = '';


    public function getHeader($request, $name, $defval='') {
        $value = $request->header($name);
        if (empty($value)) {
            return $defval;
        }
        return $value;
    }

    public function fromRequest($request) {
        $this->clientIp = getIP();
        $this->channel = $this->getHeader($request, 'CHANNEL');
        $this->device = $this->getHeader($request, 'DEVICE');
        $this->deviceId = $this->getHeader($request, 'DEVICEID');
        $this->platform = $this->getHeader($request, 'PLATFORM');
        $this->version = $this->getHeader($request, 'VERSION');
        $this->edition = $this->getHeader($request, 'EDITION');
        $this->imei = $this->getHeader($request, 'IMEI');
        $this->idfa = $this->getHeader($request, 'idfa');
        $this->appId = $this->getHeader($request, 'id', 'com.party.fq');
        $this->source = $this->getHeader($request, 'source', 'fanqie');
        $this->simulator = $this->getHeader($request, 'simulator', false);
        $this->promoteCode = $this->getHeader($request,'promote',0);
        $this->oaid = $this->getHeader($request, 'OAID');
        $this->androidid = $this->getHeader($request, 'ANDROIDID');
        return $this;
    }
}