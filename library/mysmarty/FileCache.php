<?php

namespace library\mysmarty;
/**
 * 文件缓存类
 */
class FileCache
{
    /**
     * 缓存文件夹
     * @var string
     */
    private static string $cacheDir = RUNTIME_DIR . '/cache';

    /**
     * 获取缓存变量所在的文件位置
     * @param string $name
     * @return string
     */
    public static function getCacheFile(string $name): string
    {
        return self::$cacheDir . '/' . md5($name . \config\App::ENCRYPTION_KEY);
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
        $data = serialize([
            'data' => $value,
            'expire' => time() + $expire
        ]);
        createDir(self::$cacheDir);
        return false !== file_put_contents(self::getCacheFile($name), $data);
    }

    /**
     * 获取缓存
     * @param string $name 键
     * @param mixed $defValue 默认值
     * @return mixed
     */
    public static function get(string $name, mixed $defValue = ''): mixed
    {
        $file = self::getCacheFile($name);
        if (!file_exists($file)) {
            return $defValue;
        }
        $content = file_get_contents($file);
        if (false === $content) {
            return $defValue;
        }
        $data = unserialize($content);
        if (false === $data) {
            return $defValue;
        }
        if ($data['expire'] > time()) {
            return $data['data'];
        } else {
            unlink($file);
        }
        return $defValue;
    }

    /**
     * 删除缓存
     * @param string $name 键
     * @return bool
     */
    public static function delete(string $name): bool
    {
        $file = self::getCacheFile($name);
        if (!file_exists($file)) {
            return false;
        }
        return unlink($file);
    }

    /**
     * 清空缓存文件
     * @param int $expire 多久前创建的缓存文件，单位秒
     * @param string $dir
     * @return int 返回清空的文件数
     */
    public static function clear(int $expire = 0, string $dir = ''): int
    {
        static $num = 0;
        if (empty($dir)) {
            $dir = self::$cacheDir;
        }
        if (!is_dir($dir)) {
            return $num;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $curFile = $dir . '/' . $file;
            if (is_dir($curFile)) {
                self::clear($expire, $curFile);
                rmdir($curFile);
            } else {
                if (time() - filemtime($curFile) >= $expire) {
                    unlink($curFile);
                    $num++;
                }
            }
        }
        return $num;
    }
}