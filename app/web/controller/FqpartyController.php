<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class FqpartyController extends BaseController
{
    public function bouncedText()
    {
        $str =  '';
//        $str =  'http://newmtestapi.muayuyin.com/web/bounced';
        // $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $channel = Request::header('CHANNEL');
        if ($channel == 'MaiZu') {
            return rjson($str);
        }else{
            return rjson();
        }

    }
    public function ghzm()
    {
        return View::fetch('../view/web/fqparty/ghzm.html');
    }
    public function double()
    {
        return View::fetch('../view/web/fqparty/double12.html');
    }
    public function bounced()
    {
        return View::fetch('../view/web/fqparty/bounced.html');
    }
    public function pact()
    {
        return View::fetch('../view/web/fqparty/pact.html');
    }

    public function charge()
    {
        return View::fetch('../view/web/fqparty/charge.html');
    }

    public function czsm()
    {
        return View::fetch('../view/web/fqparty/czsm.html');
    }

    public function master()
    {
        return View::fetch('../view/web/fqparty/master.html');
    }

    public function Minor()
    {
        return View::fetch('../view/web/fqparty/Minor.html');
    }

    public function pay()
    {
        return View::fetch('../view/web/fqparty/pay.html');
    }
    public function Privacy()
    {
        return View::fetch('../view/web/fqparty/privacy.html');
    }

    public function Registered()
    {
        return View::fetch('../view/web/fqparty/registered.html');
    }

    public function In()
    {
        return View::fetch('../view/web/fqparty/In.html');
    }
    public function vipText()
    {
        return View::fetch('../view/web/fqparty/vipText.html');
    }
    public function TheHostIn()
    {
        return View::fetch('../view/web/fqparty/TheHostIn.html');
    }

    /**
     * 爵位协议
     */
    public function dukeDoc() {
        return View::fetch('../view/web/fqparty/dukeDoc.html');
    }

    /**
     * 用户行为规范
     */
    public function actionRule() {
        return View::fetch('../view/web/fqparty/actionRule.html');
    }



}