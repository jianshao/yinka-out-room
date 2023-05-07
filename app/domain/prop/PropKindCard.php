<?php


namespace app\domain\prop;

use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;

/**
 * 体验卡
 * Class PropKindCard
 * @package app\domain\prop
 */
class PropKindCard extends PropKind
{
    public static $TYPE_NAME = 'card';
    public  $action_name = ['use'];

    public function newProp($propId) {
        return new PropCard($this, $propId);
    }

}