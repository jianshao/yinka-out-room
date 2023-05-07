<?php


namespace app\api\controller;


use app\Base3Controller;
use think\facade\Request;

class ApiBase3Controller extends Base3Controller
{
    protected $clientInfo = null;
    protected $requestUserId = 0;


    protected function getToken(){
        return Request::param('token', "") ? Request::param('token', "") : Request::header('token', "");
    }



}