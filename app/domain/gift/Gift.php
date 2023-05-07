<?php

namespace app\domain\gift;

class Gift
{
    // 道具类型
    public $kind = null;
    public $model = null;

    public function __construct($giftKind) {
        $this->kind = $giftKind;
    }

    public function getCount() {
        return $this->model->count;
    }
}
