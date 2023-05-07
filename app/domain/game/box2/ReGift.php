<?php


namespace app\domain\game\box2;


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