<?php

namespace app\domain\prop\model;

/**
 * 背包中的道具
 */
class PropModel
{
    // 道具ID
    public $propId = 0;
    // 道具种类ID
    public $kindId = 0;
    // 创建时间
    public $createTime = 0;
    // 修改时间
    public $updateTime = 0;
    // 剩余数量
    public $count = 0;
    // 到期时间 0为永不过期
    public $expiresTime = 0;
    // 是否穿戴 0 未穿戴 1 已穿戴
    public $isWore = 0;
    // 佩戴时间
    public $woreTime = 0;
}


