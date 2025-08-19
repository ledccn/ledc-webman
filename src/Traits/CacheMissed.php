<?php

namespace Ledc\Webman\Traits;

use Illuminate\Redis\Connections\Connection;
use support\Redis;

/**
 * 【特征】缓存穿透防护技术
 */
trait CacheMissed
{
    /**
     * 【获取】缓存键前缀
     * - 子类实现此方法，返回自定义的缓存键前缀
     * @return string
     */
    abstract protected static function getPrefix(): string;

    /**
     * 判断是否启用missed缓存
     * - 开启后，避免缓存穿透
     * @return bool
     */
    protected static function isEnableMissed(): bool
    {
        return true;
    }

    /**
     * 获取missed缓存的TTL
     * @return int
     */
    protected static function getMissedTTL(): int
    {
        return 600;
    }

    /**
     * 【获取】missed缓存key
     * @param string $key 缓存键
     * @return string
     */
    public static function getMissedKey(string $key): string
    {
        return static::getPrefix() . 'MissedKey:' . $key;
    }

    /**
     * 设置missed缓存
     * @param string $key 缓存键
     * @return bool
     */
    public static function setMissed(string $key): bool
    {
        return static::connection()->setex(static::getMissedKey($key), static::getMissedTTL(), 1);
    }

    /**
     * 判断missed缓存
     * @param string $key 缓存键
     * @return bool
     */
    public static function hasMissed(string $key): bool
    {
        return (bool)static::connection()->exists(static::getMissedKey($key));
    }

    /**
     * 删除missed缓存
     * @param string $key 缓存键
     * @return false|int
     */
    public static function delMissed(string $key): false|int
    {
        return static::connection()->del(static::getMissedKey($key));
    }

    /**
     * 获取Redis连接
     * @return Connection|\Redis
     */
    protected static function connection(): Connection|\Redis
    {
        return Redis::connection();
    }
}
