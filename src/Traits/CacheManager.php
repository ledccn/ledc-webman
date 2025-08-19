<?php

namespace Ledc\Webman\Traits;

use Closure;
use DateInterval;
use LogicException;
use Psr\SimpleCache\InvalidArgumentException;
use support\Cache;
use Symfony\Component\Cache\Psr16Cache;

/**
 * 缓存管理器
 * - 具备缓存穿透防护技术
 * - 使用方法：新建子类继承此抽象类，即可使用缓存管理器
 */
abstract class CacheManager
{
    use CacheMissed;

    /**
     * 【获取】缓存键前缀
     * - 子类可以重写此方法，返回自定义的缓存键前缀
     * @return string
     */
    protected static function getPrefix(): string
    {
        return static::class;
    }

    /**
     * 【获取】完整的缓存键名
     * - 子类可以重写此方法，返回自定义的缓存键名
     * @param string $key 缓存键
     * @return string
     */
    protected static function getKey(string $key): string
    {
        if (self::class === static::class) {
            throw new LogicException('请继承' . self::class . '类');
        }
        return static::getPrefix() . $key;
    }

    /**
     * 获取 Psr16Cache 缓存实例
     * - 子类可以重写此方法，返回自定义的 Psr16Cache 实例
     * @return Psr16Cache
     */
    protected static function store(): Psr16Cache
    {
        return Cache::store('redis');
    }

    /**
     * 【仅支持Redis驱动】批量获取缓存值，如果缓存不存在，则执行闭包函数获取值并保存
     * @param array $keys 缓存键列表
     * @param Closure $closure 回调函数
     * @param int|DateInterval|null $ttl 过期时间
     * @param array $missedKeys 缓存不存在的key
     * @return array 结果集：key => value
     * @throws InvalidArgumentException
     */
    final public static function batchGetAndSet(array $keys, Closure $closure, int|DateInterval|null $ttl = 600, array &$missedKeys = []): array
    {
        if (empty($keys)) {
            return [];
        }

        $cacheKeys = [];
        $keysMaps = [];
        foreach ($keys as $key) {
            $cacheKey = static::getKey((string)$key);
            $cacheKeys[] = $cacheKey;
            $keysMaps[$cacheKey] = $key;
        }

        $values = static::store()->getMultiple($cacheKeys);

        $keyValues = [];
        $results = [];
        foreach ($values as $cacheKey => $value) {
            $key = $keysMaps[$cacheKey];
            if (null === $value) {
                // 缓存未命中，查询 missed 拦截器，避免缓存穿透
                if (static::isEnableMissed() && static::hasMissed($key)) {
                    continue;
                }

                // 调用闭包，获取数据
                $value = call_user_func($closure, $key);
                if (null === $value) {
                    // 闭包返回null，设置 missed 拦截器，避免缓存穿透
                    static::isEnableMissed() && static::setMissed($key);
                    $missedKeys[] = $key;
                    continue;
                }

                // 批量设置缓存值
                static::isEnableMissed() && static::delMissed($key);
                $keyValues[$cacheKey] = $value;
            }

            // 结果集：key => value
            $results[$key] = $value;
        }
        // 批量设置缓存值
        $keyValues && static::store()->setMultiple($keyValues, $ttl);

        return $results;
    }

    /**
     * 获取缓存值，如果缓存不存在，则执行闭包函数获取缓存值并保存
     * @param string $key 缓存键
     * @param Closure $closure 回调函数
     * @param mixed|null $default 默认值
     * @param int|DateInterval|null $ttl 过期时间
     * @return mixed
     * @throws InvalidArgumentException
     */
    final public static function getAndSet(string $key, Closure $closure, mixed $default = null, int|DateInterval|null $ttl = 600): mixed
    {
        $value = static::get($key);
        if (null !== $value) {
            return $value;
        }
        // 缓存未命中，查询 missed 拦截器，避免缓存穿透
        if (static::isEnableMissed() && static::hasMissed($key)) {
            return $default;
        }
        // 调用闭包获取值
        $value = call_user_func($closure, $key);
        if (null !== $value) {
            self::set($key, $value, $ttl);
            return $value;
        }
        // 闭包返回null，设置 missed 拦截器，避免缓存穿透
        static::isEnableMissed() && static::setMissed($key);
        return $default;
    }

    /**
     * 【获取】缓存值
     * @param string $key 缓存键
     * @param mixed|null $default 默认值
     * @return mixed
     * @throws InvalidArgumentException
     */
    final public static function get(string $key, mixed $default = null): mixed
    {
        return static::store()->get(static::getKey($key), $default);
    }

    /**
     * 【设置】缓存值
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|DateInterval|null $ttl 过期时间
     * @return bool
     * @throws InvalidArgumentException
     */
    final public static function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        static::isEnableMissed() && static::delMissed($key);
        return static::store()->set(static::getKey($key), $value, $ttl);
    }

    /**
     * 【删除】缓存值
     * @param string $key 缓存键
     * @return bool
     * @throws InvalidArgumentException
     */
    final public static function delete(string $key): bool
    {
        return static::store()->delete(static::getKey($key));
    }

    /**
     * 【判断】缓存值是否存在
     * @param string $key 缓存键
     * @return bool
     * @throws InvalidArgumentException
     */
    final public static function has(string $key): bool
    {
        return static::store()->has(static::getKey($key));
    }

    /**
     * 【清空】所有缓存
     * @return bool
     */
    final public static function clear(): bool
    {
        return static::store()->clear();
    }

    /**
     * 【批量获取】缓存值
     * @param array|array<string|int|float> $keys 缓存键列表
     * @param mixed|null $default 默认值
     * @param bool $filterNullValue 是否过滤null值
     * @return iterable
     * @throws InvalidArgumentException
     */
    final public static function getMultiple(array $keys, mixed $default = null, bool $filterNullValue = true): iterable
    {
        $cacheKeys = [];
        $keysMaps = [];
        foreach ($keys as $key) {
            $cacheKey = static::getKey((string)$key);
            $cacheKeys[] = $cacheKey;
            $keysMaps[$cacheKey] = $key;
        }

        $values = static::store()->getMultiple($cacheKeys, $default);
        $results = [];
        foreach ($values as $cacheKey => $value) {
            if ($filterNullValue && null === $value) {
                continue;
            }
            $key = $keysMaps[$cacheKey];
            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * 【批量设置】缓存值
     * @param array|array<string|int|float, mixed> $values 缓存键值对
     * @param int|DateInterval|null $ttl 过期时间
     * @return bool
     * @throws InvalidArgumentException
     */
    final public static function setMultiple(array $values, int|DateInterval|null $ttl = null): bool
    {
        $key_values = [];
        foreach ($values as $key => $value) {
            static::isEnableMissed() && static::delMissed($key);
            $key_values[static::getKey((string)$key)] = $value;
        }
        return static::store()->setMultiple($key_values, $ttl);
    }

    /**
     * 【批量删除】缓存值
     * @param array $keys 缓存键列表
     * @return bool
     * @throws InvalidArgumentException
     */
    final public static function deleteMultiple(array $keys): bool
    {
        return static::store()->deleteMultiple(array_map(fn($key) => static::getKey($key), $keys));
    }
}
