<?php

namespace app\domain\prop;
use app\domain\exceptions\AssetNotEnoughException;
use app\utils\ArrayUtil;
use Exception;

/**
 * 数量
 */
class PropUnitCountMax1 extends PropUnitCountMaxN
{
    public static $TYPE_NAME = 'countMax1';

    public function __construct() {
        $this->maxN = 1;
    }

    protected function decodeFromJsonImpl($jsonObj) {
    }
}


