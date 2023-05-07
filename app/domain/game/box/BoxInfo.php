<?php


namespace app\domain\game\box;


class BoxInfo
{
    public $box = null;
    public $selfProgress = 0;
    public $globalProgress = 0;

    public function __construct($box) {
        $this->box = $box;
    }
}