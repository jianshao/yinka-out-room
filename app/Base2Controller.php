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

use app\utils\AdminAuthTrait;
use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class Base2Controller
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
    protected $oaid;
    protected $paltform;
    protected $idfa;
    protected $appId;

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

    use AdminAuthTrait;

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
        $this->oaid = $this->request->header('OAID');
        $this->paltform = $this->request->header('PLATFORM');
        $this->idfa = $this->request->header('idfa');

        $this->appId = $this->request->header('id');

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        // $arr = explode(',', $this->paltform);
        // if ($arr[0] == 'Android') {
        //     echo json_encode(['code'=>3000,'desc'=>'请更新最新版本音恋','apk_url'=>'http://file.fqparty.com/download/fqGW101.apk']);
        //     exit;
        // }else{
        //     echo json_encode(['code'=>801,'desc'=>'请更新最新版本音恋','data'=>'http://file.fqparty.com/download/fqparty100.apk']);
        //     exit;
        // }

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
        $param['port'] = (int)$redis_result['port'];
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

}
