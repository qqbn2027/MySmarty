<?php

namespace config;
/**
 * Cookie配置
 */
class Cookie
{
    // cookie 保存时间
    public const EXPIRE = 604800;
    // cookie 保存路径
    public const PATH = '/';
    // cookie 有效域名
    public const DOMAIN = '';
    // cookie 启用安全传输
    public const SECURE = false;
    // httponly设置
    public const HTTPONLY = false;
}