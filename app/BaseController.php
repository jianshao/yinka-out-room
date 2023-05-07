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

use app\domain\dao\ChannelPointModelDao;
use app\utils\AesUtil;
use think\App;
use think\exception\ValidateException;
use think\facade\Request;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;
    protected $cacheuid;
    protected $headUid;
    protected $headToken;
    protected $version;
    protected $channel;
    protected $deviceId;
    protected $paltform;
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
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    protected static $redisInstance;

    protected $beforeActionList = [
        'greet' => 110,
        'TakeShot' => 111,
        'addforum' => 112,
    ];

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
        $this->source = $this->request->header('source','');
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
        // 控制器初始化
        $this->initialize();
        $this->beforeAction();  //控制器前置操作
    }

    // 初始化
    protected function initialize()
    {
        $this->headToken = $this->request->header('token');
        if (empty($this->headToken)) {//兼容老版
            $this->headToken = $this->request->param('token');
        }
        if (!empty($this->headToken)) {
            $redisinit = $this->getRedis();
            $this->headUid = $redisinit->get($this->headToken);
        }
    }


    //获取redis实例
    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = intval($redis_result['port']);
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $param[$k] = $v;
            }
        }

        if (!isset(self::$redisInstance)) {
            self::$redisInstance = new \Redis();
        }
        self::$redisInstance->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            self::$redisInstance->auth($param['password']);
        }

        if (0 != $param['select']) {
            self::$redisInstance->select($param['select']);
        }
        return self::$redisInstance;
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 控制器前置操作
     */
    protected function beforeAction()
    {
        $action = \think\facade\Request::action();
        $beforeActionList = $this->beforeActionList;
        if (array_key_exists($action, $beforeActionList)) {
            //获取数据
            $token=$this->getParamToken();
            $redis = $this->getRedis();
            $user_id = $redis->get($token) ? $redis->get($token) : 0;
            $type = $beforeActionList[$action];
            $postion = $this->request->param('postion');
            $channel = $this->request->header('CHANNEL');
            $deviceId = $this->request->header('DEVICEID');
            $version = $this->request->header('VERSION');
            $pla = $this->request->header('PLATFORM');
            //获取当前时间
            $data = [
                "riq" => time(),
                "channel" => $channel,
                "type" => $type,
                "device_id" => $deviceId,
                "login_ip" => $this->request->ip(),
                "version" => $version,
                "platform" => $pla,
                "postion" => $postion,
                "user_id" => $user_id
            ];
            ChannelPointModelDao::getInstance()->saveData($data);
        }

    }


    protected function getParamToken(){
        $token = $this->request->header('token');
        if (empty($token)) {//兼容老版
            $token = $this->request->param('token');
        }
        return $token;
    }
}
