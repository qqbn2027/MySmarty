<?php

namespace config;

/**
 * 缓存配置
 */
class Cache
{
    // 缓存类型：file, redis
    public const string TYPE = 'redis';
    // redis缓存配置
    public const array REDIS = [
        // 主机
        'host' => '127.0.0.1',
        // 端口
        'port' => 6379,
        // 密码
        'password' => '',
        // 超时时间
        'timeout' => 1,
        // 数据库
        'db' => 0
    ];
}