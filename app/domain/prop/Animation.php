<?php

namespace app\domain\prop;

class Animation
{
    public $animation = '';
    public $scale = 1;

    public function decodeFromJson($jsonObj) {
        $this->animation = $jsonObj['animation'];
        $this->scale = $jsonObj['scale'];
    }
}


