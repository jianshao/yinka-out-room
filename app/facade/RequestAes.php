<?php
namespace app\facade;

use think\Facade;

/**
 * @see \app\common\RequestAes
 * @package think\facade
 * @mixin \app\common\RequestAes
 */
class RequestAes extends Facade
{

    protected static function getFacadeClass()
    {
        return 'app\common\RequestAes';
    }
}