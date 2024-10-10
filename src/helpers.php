<?php

namespace Ledc\Webman;

use Ledc\Snowflake\RedisSequenceResolver;
use Ledc\Snowflake\Snowflake;
use RedisException;
use support\Redis;
use think\exception\ValidateException;
use think\Validate;

/**
 * 雪花ID生成器
 * @return Snowflake
 * @throws RedisException
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
