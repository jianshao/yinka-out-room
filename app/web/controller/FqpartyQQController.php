<?php

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class FqpartyQQController extends BaseController
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
        return View::fetch('../view/web/fqpartyQQ/bounced.html');
    }
    public function pactLove()
    {
        return View::fetch('../view/web/fqpartyQQ/index.html');
    }

    public function chargeLove()
    {
        return View::fetch('../view/web/fqpartyQQ/charge.html');
    }

    public function czsmLove()
    {
        return View::fetch('../view/web/fqpartyQQ/czsm.html');
    }

    public function masterLove()
    {
        return View::fetch('../view/web/fqpartyQQ/master.html');
    }

    public function MinorLove()
    {
        return View::fetch('../view/web/fqpartyQQ/Minor.html');
    }

    public function payLove()
    {
        return View::fetch('../view/web/fqpartyQQ/pay.html');
    }
    public function PrivacyLove()
    {
        return View::fetch('../view/web/fqpartyQQ/privacy.html');
    }

    public function PrivacyLoveKlk()
    {
        return View::fetch('../view/web/fqpartyQQ/privacyKlk.html');
    }

    public function PrivacyLoveLrf()
    {
        return View::fetch('../view/web/fqpartyQQ/privacyLrf.html');
    }

    public function RegisteredLove()
    {
        return View::fetch('../view/web/fqpartyQQ/registered.html');
    }

    public function InLove()
    {
        return View::fetch('../view/web/fqpartyQQ/In.html');
    }
    public function vipTextLove()
    {
        return View::fetch('../view/web/fqpartyQQ/vipText.html');
    }
    public function TheHostInLove()
    {
        return View::fetch('../view/web/fqpartyQQ/TheHostIn.html');
    }

    /**
     * 爵位协议
     */
    public function dukeDocLove() {
        return View::fetch('../view/web/fqpartyQQ/dukeDocLove.html');
    }

    /**
     * 用户行为规范
     */
    public function actionRuleLove() {
        return View::fetch('../view/web/fqpartyQQ/actionRuleLove.html');
    }

    public function love() {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/fqpartyQQ/love.html');
    }




}
