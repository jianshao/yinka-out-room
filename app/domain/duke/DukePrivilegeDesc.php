<?php


namespace app\domain\duke;


class DukePrivilegeDesc
{
    public $title = '';
    public $picture = '';

    public function decodeFromJson($jsonObj) {
        $this->title = $jsonObj['title'];
        $this->picture = $jsonObj['pic'];
    }
}