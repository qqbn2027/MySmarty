<?php

namespace library\mysmarty;
/**
 * 缓存类
 */
class Cache
{
    /**
     * 添加缓存
     * @param string $name 键
     * @param mixed $value 值
     * @param int $expire 缓存时间，单位秒
     * @return bool
     */
    public static function set(string $name, mixed $value, int $expire = 3600): bool
    {
        if ('file' === \config\Cache::TYPE) {
            return FileCache::set($name, $value, $expire);
        } else if ('redis' === \config\Cache::TYPE) {
            return RedisCache::set($name, $value, $expire);
        } else {
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
        if ('file' === \config\Cache::TYPE) {
            return FileCache::get($name, $defValue);
        } else if ('redis' === \config\Cache::TYPE) {
            return RedisCache::get($name, $defValue);
        } else {
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
        if ('file' === \config\Cache::TYPE) {
            return FileCache::delete($name);
        } else if ('redis' === \config\Cache::TYPE) {
            return RedisCache::delete($name);
        } else {
            return false;
        }
    }

    /**
     * 清空缓存文件
     * @param int $expire 多久前创建的缓存文件，单位秒
     * @param string $dir
     * @return int 文件缓存 返回清空的文件数，redis 返回0
     */
    public static function clear(int $expire = 0, string $dir = ''): int
    {
        if ('file' === \config\Cache::TYPE) {
            return FileCache::clear($expire, $dir);
        } else if ('redis' === \config\Cache::TYPE) {
            RedisCache::clear();
        }
        return 0;
    }
}