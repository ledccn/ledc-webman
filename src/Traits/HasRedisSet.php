<?php

namespace Ledc\Webman\Traits;

use Illuminate\Redis\Connections\Connection;
use support\Redis;

/**
 * Redis集合
 */
trait HasRedisSet
{
    /**
     * 集合的key
     * @var string
     */
    protected string $setKey;

    /**
     * 【获取】集合的key
     * @return string
     */
    public function getSetKey(): string
    {
        return $this->setKey;
    }

    /**
     * 【设置】集合的key
     * @param string $setKey
     * @return HasRedisSet
     */
    public function setSetKey(string $setKey): static
    {
        $this->setKey = $setKey;
        return $this;
    }

    /**
     * 向集合添加一个或多个成员
     * @param string $member
     * @return false|int
     */
    public function sAdd(string $member): false|int
    {
        return $this->connection()->sAdd($this->getSetKey(), $member);
    }

    /**
     * 移除集合中一个或多个成员
     * @param string $member
     * @return false|int
     */
    public function sRem(string $member): false|int
    {
        return $this->connection()->sRem($this->getSetKey(), $member);
    }

    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param string $member
     * @return bool
     */
    public function sIsMember(string $member): bool
    {
        return $this->connection()->sIsMember($this->getSetKey(), $member);
    }

    /**
     * 获取集合的成员数
     * @return bool|int
     */
    public function sCard(): false|int
    {
        return $this->connection()->sCard($this->getSetKey());
    }

    /**
     * 刷新成员（创建或者移除）
     * @param string $member
     * @param callable $fn 返回值：true添加、false移除
     * @return void
     */
    public function refreshMember(string $member, callable $fn): void
    {
        if (call_user_func($fn, $member, $this)) {
            static::sAdd($member);
        } else {
            static::sRem($member);
        }
    }

    /**
     * 获取Redis连接
     * @return Connection|\Redis
     */
    public function connection(): Connection|\Redis
    {
        return Redis::connection();
    }
}
