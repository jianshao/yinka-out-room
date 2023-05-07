<?php


namespace app\domain\game\turntable\dao;


class ReGift
{
    public $id = 0;
    public $giftId = 0;
    public $state = 0;

    public function __construct($id, $giftId, $state) {
        $this->id = $id;
        $this->giftId = $giftId;
        $this->state = $state;
    }
}