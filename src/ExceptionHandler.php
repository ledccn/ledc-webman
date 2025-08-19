<?php

namespace Ledc\Webman;

use BadFunctionCallException;
use BadMethodCallException;
use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use OutOfRangeException;
use OverflowException;
use RangeException;
use RuntimeException;
use support\exception\BusinessException;
use support\exception\NotFoundException;
use think\exception\ClassNotFoundException;
use think\exception\FuncNotFoundException;
use think\exception\ValidateException;
use Throwable;
use UnderflowException;
use UnexpectedValueException;
use Webman\Exception\FileException;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 异常处理类
 */
class ExceptionHandler extends \Webman\Exception\ExceptionHandler
{
    /**
     * @var string[]
     */
    public $dontReport = [
        BusinessException::class,
    ];

    /**
     * 异常白名单
     * - 在白名单内，返回详细的异常描述
     * @var array
     */
    public const array whiteListException = [
        // 异常：业务
        BusinessException::class,
        // 异常：类或函数不存在
        NotFoundException::class,
        // 异常：类不存在
        ClassNotFoundException::class,
        // 异常：函数不存在
        FuncNotFoundException::class,
        // 异常：验证器
        ValidateException::class,
        // 异常：文件
        FileException::class,
        // 异常：无效参数
        InvalidArgumentException::class,
        // 异常：方法不存在
        BadMethodCallException::class,
        // 异常：函数不存在
        BadFunctionCallException::class,
        // 异常：数据域异常
        DomainException::class,
        // 异常：长度超出
        LengthException::class,
        // 异常：键或索引不存在
        OutOfRangeException::class,
        // 异常：数据溢出
        OverflowException::class,
        // 异常：数据范围
        RangeException::class,
        // 异常：容器下溢出
        UnderflowException::class,
        // 异常：数据类型或值不匹配
        UnexpectedValueException::class,
        // 异常：逻辑错误【放最后】
        LogicException::class,
        // 异常：运行时【放最后】
        RuntimeException::class,
    ];

    /**
     * 渲染返回
     * @param Request $request
     * @param Throwable $exception
     * @return Response
     */
    public function render(Request $request, Throwable $exception): Response
    {
        if (($exception instanceof BusinessException) && ($response = $exception->render($request))) {
            return $response;
        }

        $header = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache', //禁止缓存
            'Pragma' => 'no-cache', //禁止缓存
        ];

        $rs = [
            'code' => $exception->getCode() ?: 500,
            'msg' => match (true) {
                $this->debug, $this->canWhiteList($exception) => $exception->getMessage(),
                default => 'server internal error',
            },
        ];
        if ($this->debug) {
            $rs['traces'] = (string)$exception;
        }

        return new Response(200, $header, json_encode($rs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param Throwable $exception
     * @return bool
     */
    private function canWhiteList(Throwable $exception): bool
    {
        foreach (static::whiteListException as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }
        return false;
    }
}