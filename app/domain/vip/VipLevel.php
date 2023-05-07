<?php


namespace app\domain\vip;


use app\domain\asset\AssetItem;
use app\utils\ArrayUtil;

class VipLevel
{
    // 级别
    public $level = 0;
    // 特权说明
    public $privilegeDescList = null;
    // 特权资产
    public $privilegeAssets = null;
    // 特权组
    public $privilegeGroup = null;

    public function decodeFromJson($jsonObj) {
        $this->level = $jsonObj['level'];
        $this->privilegeDescList = [];
        $this->privilegeAssets = [];
        $privilegeDescList = ArrayUtil::safeGet($jsonObj, 'privilegeDesc', []);
        foreach ($privilegeDescList as $privilegeDescJsonObj) {
            $privilegeDesc = new VipPrivilegeDesc();
            $privilegeDesc->decodeFromJson($privilegeDescJsonObj);
            $this->privilegeDescList[] = $privilegeDesc;
        }
        $privilegeAssets = ArrayUtil::safeGet($jsonObj, 'privilegeAssets', []);
        foreach ($privilegeAssets as $privilegeAsset) {
            $assetItem = new AssetItem();
            $assetItem->decodeFromJson($privilegeAsset);
            $this->privilegeAssets[] = $assetItem;
        }
        $privilegeGroup = ArrayUtil::safeGet($jsonObj, 'privilegeGroup', []);
        foreach ($privilegeGroup as $privilegeGroupJsonObj) {
            $privilegeGroup = new VipPrivilegeGroup();
            $privilegeGroup->decodeFromJson($privilegeGroupJsonObj);
            $this->privilegeGroup[] = $privilegeGroup;
        }
    }
}