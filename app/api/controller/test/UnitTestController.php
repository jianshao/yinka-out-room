<?php


namespace app\api\controller\test;


use app\BaseController;
use app\utils\AesUtil;
use think\facade\Request;

class UnitTestController extends BaseController
{
    public function index() {
        $aes = new AesUtil();
        $data = $aes->decrypt(Request::param('data'));
        print_r($data);
    }
}