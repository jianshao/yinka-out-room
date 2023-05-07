<?php


namespace app\domain\duke\model;


class DukeModel
{
    public $dukeValue = 0;
    public $dukeLevel = 0;
    public $dukeExpiresTime = 0;

    public function copyTo($other) {
        $other->dukeValue = $this->dukeValue;
        $other->dukeLevel = $this->dukeLevel;
        $other->dukeExpiresTime = $this->dukeExpiresTime;
        return $other;
    }
}