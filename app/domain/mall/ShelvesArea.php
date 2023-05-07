<?php

namespace app\domain\mall;

use app\utils\ArrayUtil;

/**
 * 货架区
 */
class ShelvesArea
{
    public $type = '';
    // 区域名称
    public $displayName = '';
    // 区域图片
    public $displayImg = '';
    // 货架列表
    public $shelvesList = null;

    public function decodeFromJson($jsonObj) {
        $this->type = $jsonObj['type'];
        $this->displayName = $jsonObj['displayName'];
        $this->displayImg = ArrayUtil::safeGet($jsonObj, 'displayImg',"");
        $this->shelvesList = [];
        foreach (ArrayUtil::safeGet($jsonObj, 'shelves', []) as $shelvesConf) {
            $shelves = new Shelves();
            $shelves->decodeFromJson($shelvesConf);
            $this->shelvesList[] = $shelves;
        }
        return $this;
    }
}
