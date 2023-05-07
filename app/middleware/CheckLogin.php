<?php
/*
检测是否登录
 */
namespace app\middleware;

class CheckLogin
{
    public function handle($request, \Closure $next)
    {
    	$noLogin = []; //不登录路由地址
    	$action = $request->action(true);
        // if (!in_array($action, $noLogin)) {
        // 	$token = $request->param('token');
        // 	//判断token
        // 	//
        	
        //     return rjson();
        // }

        return $next($request);
    }
}