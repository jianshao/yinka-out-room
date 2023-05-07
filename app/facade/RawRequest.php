<?php
namespace app\facade;

use think\Facade;

/**
 * @see \app\Request
 * @package app\facade
 * @mixin \app\Request
 */
class RawRequest extends Facade
{

    protected static function getFacadeClass()
    {
        return 'app\Request';
    }
}