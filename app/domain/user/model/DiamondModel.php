<?php

namespace app\domain\user\model;

class DiamondModel
{
    public $total = 0;
    public $free = 0;
    public $exchange = 0;

    public function __construct($total=0, $free=0, $exchange=0) {
        $this->total = $total;
        $this->free = $free;
        $this->exchange = $exchange;
    }

    public function balance() {
        return $this->total - $this->free - $this->exchange;
    }
}


