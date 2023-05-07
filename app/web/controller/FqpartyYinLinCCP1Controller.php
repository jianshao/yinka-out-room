<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class FqpartyYinLinCCP1Controller extends BaseController
{
    public function bouncedTextCcp()
    {
        $str =  '';
        $channel = Request::header('CHANNEL');
        if ($channel == 'MaiZu') {
            return rjson($str);
        }else{
            return rjson();
        }

    }
    public function bouncedCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/bounced.html');
    }
    public function pactCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/index.html');
    }

    public function chargeCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/charge.html');
    }

    public function czsmCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/czsm.html');
    }

    public function masterCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/master.html');
    }

    public function MinorCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/Minor.html');
    }

    public function payCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/pay.html');
    }
    public function PrivacyCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/privacy.html');
    }

    public function RegisteredCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/registered.html');
    }

    public function InCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/In.html');
    }
    public function vipTextCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/vipText.html');
    }
    public function TheHostInCcp()
    {
        return View::fetch('../view/web/fqpartyccp1/TheHostIn.html');
    }

    /**
     * 爵位协议
     */
    public function dukeDocCcp() {
        return View::fetch('../view/web/fqpartyccp1/dukeDocLove.html');
    }

    /**
     * 用户行为规范
     */
    public function actionRuleCcp() {
        return View::fetch('../view/web/fqpartyccp1/actionRuleLove.html');
    }

    public function Ccp() {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/fqpartyccp1/love.html');
    }


    /**
     * 会员说明
     */
    public function vipDocCcp() {
        return View::fetch('../view/web/fqpartyccp1/vipDoc.html');
    }

    /**
     * 会员协议
     */
    public function vipRuleCcp() {
        return View::fetch('../view/web/fqpartyccp1/vipRule.html');
    }

    /**
     * 自动续费协议
     */
    public function autoRenewalRuleCcp() {
        return View::fetch('../view/web/fqpartyccp1/autoRenewalRule.html');
    }


}