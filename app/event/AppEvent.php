<?php


namespace app\event;


class AppEvent
{
    public $timestamp = 0;

    public function __construct($timestamp)
    {
        $this->timestamp = $timestamp;
    }


    public function jsonToModel($data)
    {
        return $this;
    }

    public function modelToJson()
    {
        return [];
    }
}