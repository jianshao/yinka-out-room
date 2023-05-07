<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class FqpartyNewController extends BaseController
{
    public function bouncedTextNew()
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
    public function bouncedNew()
    {
        return View::fetch('../view/web/fqpartynew/bounced.html');
    }
    public function pactNew()
    {
        return View::fetch('../view/web/fqpartynew/index.html');
    }

    public function chargeNew()
    {
        return View::fetch('../view/web/fqpartynew/charge.html');
    }

    public function czsmNew()
    {
        return View::fetch('../view/web/fqpartynew/czsm.html');
    }

    public function masterNew()
    {
        return View::fetch('../view/web/fqpartynew/master.html');
    }

    public function MinorNew()
    {
        return View::fetch('../view/web/fqpartynew/Minor.html');
    }

    public function payNew()
    {
        return View::fetch('../view/web/fqpartynew/pay.html');
    }
    public function PrivacyNew()
    {
        return View::fetch('../view/web/fqpartynew/privacy.html');
    }

    public function RegisteredNew()
    {
        return View::fetch('../view/web/fqpartynew/registered.html');
    }

    public function InNew()
    {
        return View::fetch('../view/web/fqpartynew/In.html');
    }
    public function vipTextNew()
    {
        return View::fetch('../view/web/fqpartynew/vipText.html');
    }
    public function TheHostInNew()
    {
        return View::fetch('../view/web/fqpartynew/TheHostIn.html');
    }


    /**
     * 会员说明
     */
    public function vipDocNew() {
        return View::fetch('../view/web/fqpartynew/vipDoc.html');
    }

    /**
     * 会员协议
     */
    public function vipRuleNew() {
        return View::fetch('../view/web/fqpartynew/vipRule.html');
    }

    /**
     * 自动续费协议
     */
    public function autoRenewalRuleNew() {
        return View::fetch('../view/web/fqpartynew/autoRenewalRule.html');
    }


}