<?php

namespace Ledc\Webman;

use support\exception\BusinessException;
use support\exception\NotFoundException;
use think\exception\ClassNotFoundException;
use think\exception\FuncNotFoundException;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\FileException;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 异常信息以Json返回
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