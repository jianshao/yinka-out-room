<?php


namespace app\web\common;

use app\BaseController;
use app\common\RedisCommon;
use think\facade\Request;
use think\facade\Config;
use think\cache\driver\Redis;
use think\facade\View;
use think\facade\Log;
use think\facade\Session;

class WebBaseController extends BaseController
{

    protected $filterRouter = [
        '/web/smsCode',
        '/web/login',
    ];

    public function initialize()
    {
        /*if (!in_array(explode('?', $this->request->server('REQUEST_URI'))[0], $this->filterRouter)) {
            $userinfo = Session::All();
            if (empty($userinfo)) {
                header('Location: /web/musicList/');
                die;
            }
            $this->userinfo = $userinfo;
        }*/
        $userinfo = Session::All();
        $this->userinfo = $userinfo;
    }

    public function return_json($code = 200, $data = array(), $msg = '', $is_die = 0)
    {
        $out['code'] = $code ?: 0;
        $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
        $out['data'] = $data ?: [];
        if ($is_die) {
            echo json_encode($out);
            return;
        } else {
            return json_encode($out);
        }
    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $param[$k] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }

    //客户端ip
    public function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
   public  function curl_get($url, $time = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);

        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }



    protected function getToken(){
        $token = request()->header('token');
        if (empty($token)) {//兼容老版
            $token = request()->param('token') ?? '';
        }
        return $token;
    }


    /**
     * @param $token
     * @return array
     */
    protected function parseToken($token){
     $paramtoken =   RedisCommon::getInstance()->getRedis()->get($token);
     return json_decode($paramtoken,true);
    }



}
