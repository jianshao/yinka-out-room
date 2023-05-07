<?php

namespace app\domain\gift\model;

/**
 * 用户礼物背包的礼物实例
 */
class GiftModel
{
    // 类型Id
    public $kindId = 0;
    // 创建时间
    public $createTime = 0;
    // 修改时间
    public $updateTime = 0;
    // 数量
    public $count = 0;

    public function __construct($kindId=0, $createTime=0, $updateTime=0, $count=0) {
        $this->kindId = $kindId;
        $this->createTime = $createTime;
        $this->updateTime = $updateTime;
        $this->count = $count;
    }
}


