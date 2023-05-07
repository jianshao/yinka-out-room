<?php


namespace app\domain\queue\model;


class ListModel
{
    public $topic = '';

    public $messageId = '';

    public $data = [];

    public $timestamp = 0;
}