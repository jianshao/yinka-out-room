<?php

namespace app\domain\user\model;

class BeanModel
{
    public $total = 0;
    public $free = 0;

    public function __construct($total=0, $free=0) {
        $this->total = $total;
        $this->free = $free;
    }

    public function balance() {
        return $this->total - $this->free;
    }
}


