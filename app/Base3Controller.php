<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app;

use app\common\RedisCommon;
use think\App;

/**
 * 控制器基础类
 */
abstract class Base3Controller
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;
    protected $version;
    protected $channel;
    protected $headUid;
    protected $deviceId;
    protected $paltform;
    protected $headToken;
    protected $imei;
    protected $appId;
    protected $source;
    protected $config;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];


    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->version = $this->request->header('VERSION');
        $this->channel = $this->request->header('CHANNEL');
        $this->deviceId = $this->request->header('DEVICEID');
        $this->paltform = $this->request->header('PLATFORM');
        $this->imei = $this->request->header('IMEI');
        $this->appId = $this->request->header('id');
        $this->source = $this->request->header('source', '');
        switch ($this->source) {
            case 'ccp':
                $this->config = 'ccpconfig';
                break;
            case 'chuchu':
                $this->config = 'chuchuconfig';
                break;
            default:
                $this->config = 'config';
                break;
        }
        $this->edition=$this->request->header("EDITION","");
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        $this->headToken = $this->getParamToken();
        if (!empty($this->headToken)) {
            $redisinit = $this->getRedis();
            $this->headUid = intval($redisinit->get($this->headToken));
        }
    }

    private function getRedis()
    {
        return RedisCommon::getInstance()->getRedis();
    }

    protected function getParamToken()
    {
        $token = $this->request->header('token', '');
        return $token;
    }
}
