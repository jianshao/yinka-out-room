<?php


namespace app\domain\prop;


class PropSimple extends Prop
{
    public function __construct($propKind, $propId) {
        parent::__construct($propKind, $propId);
    }
}