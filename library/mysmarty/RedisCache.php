<?php

namespace library\mysmarty;

use Exception;
use Redis;

/**
 * Redis缓存类
 */
class RedisCache
{
    /**
     * Redis实例
     */
    private static ?Redis $redis = null;

    /**
     * 获取Redis实例
     * @throws Exception
     */
    private static function connect(): void
    {
        if (is_null(self::$redis)) {
            if (!class_exists('Redis')) {
                throw new Exception('请安装Redis扩展');
            }
            self::$redis = new Redis();
            self::$redis->connect(\config\Cache::REDIS['host'], \config\Cache::REDIS['port'], \config\Cache::REDIS['timeout']);
            if (!empty(\config\Cache::REDIS['password'])) {
                self::$redis->auth(\config\Cache::REDIS['password']);
            }
            self::$redis->select(\config\Cache::REDIS['db']);
        }
    }

    /**
     * 添加缓存
     * @param string $name 键
     * @param mixed $value 值
     * @param int $expire 缓存时间，单位秒
     * @return bool
     */
    public static function set(string $name, mixed $value, int $expire = 3600): bool
    {
        try {
            self::connect();
            return self::$redis->setEx(md5($name . \config\App::ENCRYPTION_KEY), $expire, serialize($value));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取缓存
     * @param string $name 键
     * @param mixed $defValue 默认值
     * @return mixed
     */
    public static function get(string $name, mixed $defValue = ''): mixed
    {
        try {
            self::connect();
            $value = self::$redis->get(md5($name . \config\App::ENCRYPTION_KEY));
            if (false === $value) {
                return $defValue;
            }
            return unserialize($value);
        } catch (Exception $e) {
            return $defValue;
        }
    }

    /**
     * 删除缓存
     * @param string $name 键
     * @return bool
     */
    public static function delete(string $name): bool
    {
        try {
            self::connect();
            return self::$redis->del(md5($name . \config\App::ENCRYPTION_KEY));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 清空缓存
     * @return void
     */
    public static function clear(): void
    {
        try {
            self::connect();
            self::$redis->flushDB();
        } catch (Exception $e) {
        }
    }
}