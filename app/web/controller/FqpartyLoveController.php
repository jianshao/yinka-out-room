<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class fqpartyloveController extends BaseController
{
    public function bouncedTextLove()
    {
        $str =  '';
//        $str =  'http://Lovemtestapi.muayuyin.com/web/bounced';
        // $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $channel = Request::header('CHANNEL');
        if ($channel == 'MaiZu') {
            return rjson($str);
        }else{
            return rjson();
        }

    }
    public function bouncedLove()
    {
        return View::fetch('../view/web/fqpartylove/bounced.html');
    }
    public function pactLove()
    {
        return View::fetch('../view/web/fqpartylove/index.html');
    }

    public function chargeLove()
    {
        return View::fetch('../view/web/fqpartylove/charge.html');
    }

    public function czsmLove()
    {
        return View::fetch('../view/web/fqpartylove/czsm.html');
    }

    public function masterLove()
    {
        return View::fetch('../view/web/fqpartylove/master.html');
    }

    public function MinorLove()
    {
        return View::fetch('../view/web/fqpartylove/Minor.html');
    }

    public function payLove()
    {
        return View::fetch('../view/web/fqpartylove/pay.html');
    }
    public function PrivacyLove()
    {
        return View::fetch('../view/web/fqpartylove/privacy.html');
    }

    public function RegisteredLove()
    {
        return View::fetch('../view/web/fqpartylove/registered.html');
    }

    public function InLove()
    {
        return View::fetch('../view/web/fqpartylove/In.html');
    }
    public function vipTextLove()
    {
        return View::fetch('../view/web/fqpartylove/vipText.html');
    }
    public function TheHostInLove()
    {
        return View::fetch('../view/web/fqpartylove/TheHostIn.html');
    }

    /**
     * 爵位协议
     */
    public function dukeDocLove() {
        return View::fetch('../view/web/fqpartylove/dukeDocLove.html');
    }

    /**
     * 用户行为规范
     */
    public function actionRuleLove() {
        return View::fetch('../view/web/fqpartylove/actionRuleLove.html');
    }

    public function love() {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/fqpartylove/love.html');
    }

    /**
     * 会员说明
     */
    public function vipDocLove() {
        return View::fetch('../view/web/fqpartylove/vipDoc.html');
    }

    /**
     * 会员协议
     */
    public function vipRuleLove() {
        return View::fetch('../view/web/fqpartylove/vipRule.html');
    }

    /**
     * 自动续费协议
     */
    public function autoRenewalRuleLove() {
        return View::fetch('../view/web/fqpartylove/autoRenewalRule.html');
    }



}