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

namespace app;

use app\domain\exceptions\FQException;
use app\domain\exceptions\FQFatalException;
use app\domain\exceptions\ImException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Log;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        Log::error($exception->getTraceAsString());
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof FQException) {
            return rjsonToResponse([], $e->getCode(), $e->getMessage());
        }
        if ($e instanceof ImException) {
            return rjsonToResponse([], $e->getCode(), $e->getMessage());
        }
        if ($e instanceof FQFatalException) {
            $str = $e->getMessage();
            $response = new Response;
            $response->content($str);
            return $response;
        }

        Log::error(sprintf('errorMsg %s errorCode %d errorLine %d', $e->getMessage(), $e->getCode(), $e->getLine()));
        return rjsonToResponse([], 500, '未知错误，请重试');
    }
}
