<?php
use think\facade\Route;
//官网由原来的模板渲染修改成api形式
Route::post("v1/user/login","v1.LoginController/login"); //登录
Route::post("v1/user/loginout","v1.LoginController/loginOut"); //退出登录
Route::post("v1/user/smscode","v1.LoginController/smsCode"); //获取手机验证码
Route::post("v1/user/userData","v1.UserController/userData"); //上传音乐
Route::post("v1/pay/alipay","v1.PayController/alipay"); //支付宝支付列表
Route::post("v1/pay/wxh5pay","v1.PayController/wxh5pay"); //微信支付列表

Route::group("v1",function(){
    Route::post("music/musicList","v1.MusicController/musicList"); //音乐列表
    Route::post("music/statusMusic","v1.MusicController/statusMusic"); //音乐上架调整
    Route::post("music/uploadFileMusic","v1.MusicController/uploadFileMusic"); //上传音乐
})->middleware(\app\web\middleware\AuthToken::class);








