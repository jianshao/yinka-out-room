<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class FqpartyFanqieController extends BaseController
{
    public function bouncedTextTomato()
    {
        $str =  '';
        $channel = Request::header('CHANNEL');
        if ($channel == 'MaiZu') {
            return rjson($str);
        }else{
            return rjson();
        }

    }
    public function bouncedTomato()
    {
        return View::fetch('../view/web/fqpartytomato/bounced.html');
    }
    public function pactTomato()
    {
        return View::fetch('../view/web/fqpartytomato/index.html');
    }

    public function chargeTomato()
    {
        return View::fetch('../view/web/fqpartytomato/charge.html');
    }

    public function czsmTomato()
    {
        return View::fetch('../view/web/fqpartytomato/czsm.html');
    }

    public function masterTomato()
    {
        return View::fetch('../view/web/fqpartytomato/master.html');
    }

    public function MinorTomato()
    {
        return View::fetch('../view/web/fqpartytomato/Minor.html');
    }

    public function payTomato()
    {
        return View::fetch('../view/web/fqpartytomato/pay.html');
    }
    public function PrivacyTomato()
    {
        return View::fetch('../view/web/fqpartytomato/privacy.html');
    }

    public function RegisteredTomato()
    {
        return View::fetch('../view/web/fqpartytomato/registered.html');
    }

    public function InTomato()
    {
        return View::fetch('../view/web/fqpartytomato/In.html');
    }
    public function vipTextTomato()
    {
        return View::fetch('../view/web/fqpartytomato/vipText.html');
    }
    public function TheHostInTomato()
    {
        return View::fetch('../view/web/fqpartytomato/TheHostIn.html');
    }

    /**
     * 爵位协议
     */
    public function dukeDocTomato() {
        return View::fetch('../view/web/fqpartytomato/dukeDocLove.html');
    }

    /**
     * 用户行为规范
     */
    public function actionRuleTomato() {
        return View::fetch('../view/web/fqpartytomato/actionRuleLove.html');
    }

    public function Tomato() {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/fqpartytomato/love.html');
    }

    /**
     * 会员说明
     */
    public function vipDocTomato() {
        return View::fetch('../view/web/fqpartytomato/vipDoc.html');
    }

    /**
     * 会员协议
     */
    public function vipRuleTomato() {
        return View::fetch('../view/web/fqpartytomato/vipRule.html');
    }

    /**
     * 自动续费协议
     */
    public function autoRenewalRuleTomato() {
        return View::fetch('../view/web/fqpartytomato/autoRenewalRule.html');
    }



}