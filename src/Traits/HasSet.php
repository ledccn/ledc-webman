<?php

namespace Ledc\Webman\Traits;

use Closure;
use Illuminate\Redis\Connections\Connection;
use support\Redis;

/**
 * Redis集合
 */
trait HasSet
{
    /**
     * 集合的前缀
     * @return string
     */
    abstract public static function prefix(): string;

    /**
     * 【获取】集合的key
     * @param string $key
     * @return string
     */
    public static function getSetKey(string $key): string
    {
        return static::prefix() . $key;
    }

    /**
     * 【获取】集合的成员
     * - 如果存在，则返回字段的值
     * - 如果不存在，则调用回调函数，然后把返回值写入缓存
     * @param string $key
     * @param Closure|null $fn 获取值回调
     * @param Closure|null $refresh 是否刷新回调，回调内返回布尔值，true刷新缓存，false不刷新缓存
     * @return false|array|null
     */
    public static function sMembersOrAdd(string $key, ?Closure $fn = null, ?Closure $refresh = null): false|array|null
    {
        $members = static::connection()->sMembers(static::getSetKey($key));
        if ($members && $fn && $refresh && call_user_func($refresh, $key)) {
            // 需同时满足刷新条件：1.字段存在 2.存在回调 3.回调返回true
            goto refresh;
        }
        if (!$members && $fn) {
            refresh:
            $members = call_user_func($fn, $key);
            if (!$members) {
                return null;
            }
            static::connection()->sAdd(static::getSetKey($key), ...$members);
            return $members;
        }
        return $members ?: null;
    }

    /**
     * 从一个集合键中获取所有成员
     * @param string $key
     * @return false|array
     */
    public static function sMembers(string $key): false|array
    {
        return static::connection()->sMembers(static::getSetKey($key));
    }

    /**
     * 获取第一个集合与其他集合的差集
     * @param string $key
     * @param string ...$other_keys
     * @return false|array
     */
    public static function sDiff(string $key, string ...$other_keys): false|array
    {
        return static::connection()->sDiff(static::getSetKey($key), ...array_map(fn($other_key) => static::getSetKey($other_key), $other_keys));
    }

    /**
     * 获取一个或多个集合的交集
     * @param string $key
     * @param string ...$other_keys
     * @return false|array
     */
    public static function sInter(string $key, string ...$other_keys): false|array
    {
        return static::connection()->sInter(static::getSetKey($key), ...array_map(fn($other_key) => static::getSetKey($other_key), $other_keys));
    }

    /**
     * 获取一个或多个集合的并集
     * @param string $key
     * @param array $other_keys
     * @return false|array
     */
    public static function sUnion(string $key, string ...$other_keys): false|array
    {
        if (empty($key) && empty($other_keys)) {
            return [];
        }
        return self::connection()->sUnion(static::getSetKey($key), ...array_map(fn($other_key) => static::getSetKey($other_key), $other_keys));
    }

    /**
     * 向集合添加一个或多个成员
     * @param string $key
     * @param mixed $value
     * @param mixed ...$values
     * @return false|int
     */
    public static function sAdd(string $key, mixed $value, mixed ...$values): false|int
    {
        return static::connection()->sAdd(static::getSetKey($key), $value, ...$values);
    }

    /**
     * 向集合键添加一个或多个值
     * - 这是 Redis::sAdd() 的替代方法，但它不采用可变参数形式，而是接受一个值数组
     * @param string $key
     * @param array $values
     * @return int
     */
    public function sAddArray(string $key, array $values): int
    {
        return static::connection()->sAddArray(static::getSetKey($key), $values);
    }

    /**
     * 移除集合中一个或多个成员
     * @param string $key
     * @param string $value
     * @param mixed ...$other_values
     * @return false|int
     */
    public static function sRem(string $key, mixed $value, mixed ...$other_values): false|int
    {
        return static::connection()->sRem(static::getSetKey($key), $value, ...$other_values);
    }

    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function sIsMember(string $key, mixed $value): bool
    {
        return static::connection()->sIsMember(static::getSetKey($key), $value);
    }

    /**
     * 检查一个或多个值是否是集合的成员
     * @param string $key 要查询的集合
     * @param string $member 第一个要检查是否存在于集合中的值
     * @param string ...$other_members 任意数量的附加值进行检查
     * @return false|array 返回一个整数数组，表示每个传入的值是否是集合的成员
     */
    public function sMisMember(string $key, string $member, string ...$other_members): false|array
    {
        return static::connection()->sMisMember(static::getSetKey($key), $member, ...$other_members);
    }

    /**
     * 移除并返回集合中的一个随机元素
     * @param string $key
     * @param int $count 可选的成员数量
     * @return false|array|string
     */
    public static function sPop(string $key, int $count = 0): false|array|string
    {
        return static::connection()->sPop(static::getSetKey($key), $count);
    }

    /**
     * 从一个集合中弹出一个成员并将其推入另一个集合。如果目标集合当前不存在，此命令将创建该集合
     * @param string $src 源集合的key
     * @param string $dst 目标集合的key
     * @param mixed $value 源集合的待移动成员
     * @return bool
     */
    public function sMove(string $src, string $dst, mixed $value): bool
    {
        return static::connection()->sMove(static::getSetKey($src), static::getSetKey($dst), $value);
    }

    /**
     * 获取集合中的一个或多个随机成员
     * @param string $key
     * @param int $count 可选的成员数量
     * @return false|array|string
     */
    public static function sRandMember(string $key, int $count = 1): false|array|string
    {
        return static::connection()->sRandMember(static::getSetKey($key), $count);
    }

    /**
     * 获取集合的成员数
     * @return bool|int
     */
    public static function sCard(string $key): false|int
    {
        return static::connection()->sCard(static::getSetKey($key));
    }

    /**
     * 创建或者移除
     * @param string $key
     * @param mixed $value
     * @param callable $fn 返回值：true添加、false移除
     * @return void
     */
    public static function AddOrRem(string $key, mixed $value, callable $fn): void
    {
        if (call_user_func($fn, $value)) {
            static::sAdd($key, $value);
        } else {
            static::sRem($key, $value);
        }
    }

    /**
     * 获取Redis连接
     * @return Connection|\Redis
     */
    public static function connection(): Connection|\Redis
    {
        return Redis::connection();
    }
}
