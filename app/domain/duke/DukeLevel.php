<?php

namespace app\domain\duke;

use app\domain\asset\AssetItem;
use app\domain\duke\DukePrivilegeDesc;
use app\utils\ArrayUtil;

class DukeLevel
{
    public $level = 0;
    public $name = '';
    public $picture = '';
    public $value = 0;
    public $relegation = 0;
    public $animation = '';
    public $upgradeBroadcast = 0;
    public $avoidKick = 0;
    public $avoidForbidwords = 0;
    public $privilegeDescList = null;
    public $privilegeAssets = null;

    public function decodeFromJson($jsonObj) {
        $this->level = $jsonObj['level'];
        $this->name = $jsonObj['name'];
        $this->picture = $jsonObj['picture'];
        $this->value = $jsonObj['value'];
        $this->relegation = $jsonObj['relegation'];
        $this->animation = $jsonObj['animation'];
        $this->upgradeBroadcast = $jsonObj['upgradeBroadcast'];
        $this->avoidKick = $jsonObj['avoidKick'];
        $this->avoidForbidwords = $jsonObj['avoidForbidwords'];

        $this->privilegeDescList = [];
        $this->privilegeAssets = [];

        $privilegeDescList = ArrayUtil::safeGet($jsonObj, 'privilegeDesc', []);
        foreach ($privilegeDescList as $privilegeDescJsonObj) {
            $privilegeDesc = new DukePrivilegeDesc();
            $privilegeDesc->decodeFromJson($privilegeDescJsonObj);
            $this->privilegeDescList[] = $privilegeDesc;
        }

        $privilegeAssets = ArrayUtil::safeGet($jsonObj, 'privilegeAssets', []);
        foreach ($privilegeAssets as $privilegeAsset) {
            $assetItem = new AssetItem();
            $assetItem->decodeFromJson($privilegeAsset);
            $this->privilegeAssets[] = $assetItem;
        }
    }
}