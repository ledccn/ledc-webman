<?php

namespace Ledc\Webman;

use Ledc\Snowflake\RedisSequenceResolver;
use Ledc\Snowflake\Snowflake;
use RedisException;
use support\Redis;

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
