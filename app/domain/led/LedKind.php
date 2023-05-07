<?php


namespace app\domain\led;


use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class LedKind
{
    public $kindType = 0;                    //ledtype
    public $imgTop = '';                     //背景图前部
    public $imgEnd = '';                    //背景图后部
    public $color = 0;                       //背景图中部的色值
    public $borderColor = 0;                 //背景图边部的色值


    public function decodeFromJson($kindType, $jsonObj) {
        $this->kindType = $kindType;
        $this->imgTop = $jsonObj['imgTop'];
        $this->imgEnd = $jsonObj['imgEnd'];
        $this->color = ArrayUtil::safeGet($jsonObj, 'color');
        $this->borderColor = ArrayUtil::safeGet($jsonObj, 'borderColor');
    }

    public function makeBackground($startImg=null){
        return [
            "startImg" => $startImg!=null?$startImg:CommonUtil::buildImageUrl($this->imgTop),
		    "endImg" => CommonUtil::buildImageUrl($this->imgEnd),
            "color" => $this->color,
            "borderColor"=> $this->borderColor,
        ];
    }
}