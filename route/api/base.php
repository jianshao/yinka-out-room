<?php

use think\facade\Route;

Route::group('base', function () {
//    Route::rule('init', 'base.InitController/baseInit');               //app加密数据配置下发
    Route::rule('nicai', 'base.InitController/nicai');               //app加密数据配置下发
})->middleware([\app\middleware\VersionCheck::class, \app\middleware\ResponseLog::class]);

