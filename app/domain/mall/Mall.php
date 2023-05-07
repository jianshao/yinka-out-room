<?php

namespace app\domain\mall;

use app\utils\ArrayUtil;

class Mall
{
    private $mallId = '';
    private $shelvesAreaList = [];
    private $shelvesAreaMap = [];

    public function __construct($mallId) {
        $this->mallId = $mallId;
    }

    public function getMallId() {
        return $this->mallId;
    }

    public function getShelvesAreaList() {
        return $this->shelvesAreaList;
    }

    public function findShelvesArea($type) {
        return ArrayUtil::safeGet($this->shelvesAreaMap, $type);
    }

    public function decodeFromJson($jsonObj) {
        $shelvesAreaListConf = ArrayUtil::safeGet($jsonObj, 'areas', []);
        $shelvesAreaList = [];
        $shelvesAreaMap = [];
        foreach ($shelvesAreaListConf as $shelvesAreaConf) {
            $shelvesArea = new ShelvesArea();
            $shelvesArea->decodeFromJson($shelvesAreaConf);
            $shelvesAreaList[] = $shelvesArea;
            $shelvesAreaMap[$shelvesArea->type] = $shelvesArea;
        }
        $this->shelvesAreaList = $shelvesAreaList;
        $this->shelvesAreaMap = $shelvesAreaMap;
        return $this;
    }
}