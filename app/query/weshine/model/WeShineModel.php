<?php

namespace app\query\weshine\model;

use app\utils\ArrayUtil;

class WeShineModel
{
    public $src = "";
    public $width = 0;
    public $height = 0;

    public function fromJson($jsonObj)
    {
        $this->src = ArrayUtil::safeGet($jsonObj, 'src', 0);
        $this->width = ArrayUtil::safeGet($jsonObj, 'width', 0);
        $this->height = ArrayUtil::safeGet($jsonObj, 'height', 0);
        return $this;
    }

    public function toJson()
    {
        return [
            'src' => $this->src,
            'width' => $this->width,
            'height' => $this->height
        ];
    }
}


