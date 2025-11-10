<?php

namespace Ledc\Webman;

use Ledc\Snowflake\RedisSequenceResolver;
use Ledc\Snowflake\Snowflake;
use RedisException;
use support\Redis;
use think\exception\ValidateException;
use think\Validate;
use Throwable;

/**
 * 获取当前请求的User-Agent
 * @return string|null
 */
function get_user_agent(): ?string
{
    return request()?->header('user-agent', '');
}

/**
 * 判断是否通过微信客户端访问
 * @return bool
 */
function is_wechat(): bool
{
    $userAgent = get_user_agent();
    if (empty($userAgent)) {
        return false;
    }
    return str_contains($userAgent, 'MicroMessenger');
}

/**
 * 判断是否通过移动端访问
 * @return bool
 */
function is_mobile(): bool
{
    $userAgent = get_user_agent();
    if (empty($userAgent)) {
        return false;
    }
    $mobileAgents = ["Android", "iPhone", "iPod", "iPad", "Windows Phone", "BlackBerry", "SymbianOS", "OpenHarmony", "Mobile"];
    foreach ($mobileAgents as $needle) {
        if (str_contains($userAgent, $needle)) {
            return true;
        }
    }
    return false;
}

/**
 * 生成20位纯数字订单号
 * - 规则：年月日时分秒 + 6位微秒数
 * - 示例值：20251010101010123456
 * @return string
 */
function generate_order_number(): string
{
    [$mSec, $second] = explode(' ', microtime());
    return date('YmdHis', (int)$second) . substr($mSec, 2, 6);
}

/**
 * 生成18位纯数字订单号
 * - 规则：年月日时分秒 + 4位微秒数（
 * - 示例值：202510101010101234
 * @return string
 */
function generate_order_sn(): string
{
    [$timestamp, $mSec] = explode('.', microtime(true));
    return date('YmdHis', (int)$timestamp) . str_pad($mSec, 4, '0');
}

/**
 * 雪花ID生成器
 * @return Snowflake
 * @throws RedisException|Throwable
 */
function snowflake(): Snowflake
{
    $sequence = new RedisSequenceResolver(Redis::connection()->client());
    $sequence->setCachePrefix('Snowflake:' . md5(__FILE__) . ':');
    $snowflake = new Snowflake;
    $snowflake->setSequenceResolver($sequence);

    return $snowflake;
}

/**
 * thinkPHP验证器助手函数
 * @param array $data 待验证的数据
 * @param array|string $validate 验证器类名或者验证规则数组
 * @param array $message 错误提示信息
 * @param bool $batch 是否批量验证
 * @return bool|true
 * @throws ValidateException
 */
function validate(array $data, array|string $validate, array $message = [], bool $batch = false): bool
{
    if (is_array($validate)) {
        $v = new Validate();
        $v->rule($validate);
    } else {
        if (strpos($validate, '.')) {
            // 支持场景
            [$validate, $scene] = explode('.', $validate);
        }
        if (!class_exists($validate)) {
            throw new ValidateException('验证类不存在:' . $validate);
        }
        /** @var Validate $v */
        $v = new $validate();
        if (!empty($scene)) {
            $v->scene($scene);
        }
    }

    return $v->message($message)->batch($batch)->failException(true)->check($data);
}

/**
 * 获取当前版本commit.
 */
function current_git_commit(string $branch = 'master', bool $short = true): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $hash = file_get_contents($filename);
        $hash = trim($hash);
        return $short ? substr($hash, 0, 7) : $hash;
    }

    return '';
}

/**
 * 获取当前版本时间.
 */
function current_git_filemtime(string $branch = 'master', string $format = 'Y-m-d H:i:s'): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $time = filemtime($filename);
        return date($format, $time);
    }

    return '';
}

/**
 * xlswriter导出文件
 * @param string $path xlsx文件保存路径
 * @param string $filename 文件名
 * @param array $header 表头
 * @param array $data 表数据（二维数组）
 * @return string
 */
function xls_writer(string $path, string $filename, array $header, array $data): string
{
    $config = [
        'path' => $path,     // xlsx文件保存路径
    ];
    $excel = new \Vtiful\Kernel\Excel($config);

    // fileName 会自动创建一个工作表，你可以自定义该工作表名称，工作表名称为可选参数
    $filePath = $excel->fileName($filename . '.xlsx', 'sheet1')
        ->header($header)
        ->data($data)
        ->output();
    // 关闭当前打开的所有文件句柄 并 回收资源
    if (method_exists($excel, 'close')) {
        $excel->close();
    }

    return $filePath;
}

/**
 * 获取xlswriter句柄
 * @param string $path xlsx文件保存路径
 * @return \Vtiful\Kernel\Excel
 */
function xls_writer_handle(string $path): \Vtiful\Kernel\Excel
{
    $config = ['path' => $path];
    return new \Vtiful\Kernel\Excel($config);
}
