<?php
namespace app\web\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Session;
use think\facade\Request;
use app\web\model\DoAppModelDao;

class IndexController extends BaseController
{

    /**
     * 首页列表操作
     */
    public function DownloadTheAndroid()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/DownloadTheAndroid.html');
    }
    /**
     * 首页列表操作
     */
    public function DownloadThe_Android()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/DownloadThe_Android.html');
    }
    /**
     * 首页列表操作
     */
    public function index()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
//        return View::fetch('../view/web/index.html');
        return View::fetch('../view/web/fqpartylove/love.html');
    }

    /**
     * 首页列表操作
     */
    public function love()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        View::assign('web_url', $web_url);
        View::assign('username', $username);
        return View::fetch('../view/web/love.html');
    }


    /**
     * 统计点击连接数
     */
    public function doAppCount()
    {
        //访问连接次数统计
        $nowdate = date('Y-m-d',time());
        //更新数据库
        $urlcount = 1;
        $result = DoAppModelDao::getInstance()->getOne(['ctime' => strtotime($nowdate)]);
        if($result){
            DoAppModelDao::getInstance()->updateData(['ctime' => strtotime($nowdate)], $urlcount);
        }else{
            $data['ctime'] = strtotime($nowdate);
            $data['adowncount'] = 0;
            $data['pdowncount'] = 0;
            $data['urlcount'] = $urlcount;
            DoAppModelDao::getInstance()->saveData($data);
        }
    }

    //type : 1 充值 2下载 3提现 4官网
    public function getUrl() {
        $type = $this->request->param('type');
        if (empty($type)) {
            $url = "https://www.muayuyin.com/gw/#/download?ts=" . time();
            header("Location: $url");
            exit();
        }
        $redis = $this->getRedis();
        $redis->select(3);
        $url = $redis->hGet('h5url_config', $type);
        $url = $url . time();
        switch ($type) {
            case 1:
                header("Location: $url");
                exit();
            case 2:
                header("Location: $url");
                exit();
            case 3:
                header("Location: $url");
                exit();
            case 4:
                header("Location: $url");
                exit();
            default:
                header("Location: $url");
                exit();
        }
    }


}
