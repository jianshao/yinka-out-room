<?php

namespace app\domain\level;

use app\utils\ArrayUtil;

class Level
{
    //等级
    public $level = 0;
    // 等级名称
    public $name = 0;
    //该等级需要的经验值
    public $count = 0;
    // 等级图片
    public $image = 0;

    public function decodeFromJson($conf){
        $this->level = $conf["level"];
        $this->count = $conf["count"];
        $this->name = ArrayUtil::safeGet($conf, 'name');
        $this->image = ArrayUtil::safeGet($conf, 'image');
    }
}