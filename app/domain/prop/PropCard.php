<?php


namespace app\domain\prop;


class PropCard extends Prop
{
    public function __construct($propKind, $propId) {
        parent::__construct($propKind, $propId);
    }
}