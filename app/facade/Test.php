<?php
namespace app\facade;

use think\Facade;

/**
 * @see \app\common\Test
 * @package think\facade
 * @mixin \app\common\Test
 */
class Test extends Facade
{

    protected static function getFacadeClass()
    {
        return 'app\common\Test';
    }
}