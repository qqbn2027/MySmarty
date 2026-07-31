<?php

namespace config;

use PDO;

/**
 * 数据库配置
 */
class Database
{
    // 主机ip
    public const HOST = 'localhost';
    // mysql 用户名
    public const USER = 'root';
    // mysql 密码
    public const PASSWORD = '123456';
    // mysql 端口
    public const PORT =3306;
    // mysql 默认数据库
    public const DATABASE = 'test';
    // mysql 字符编码
    public const CHARSET = 'utf8mb4';
    // mysql 驱动的连接选项
    // 通用数据库驱动连接选项设置参考：https://www.php.net/manual/zh/pdo.setattribute.php
    // mysql8 驱动连接选项设置参考：https://www.php.net/manual/zh/ref.pdo-mysql.php
    public const OPTIONS = [
        // 错误报告：
        // PDO::ERRMODE_SILENT：仅设置错误代码
        // PDO::ERRMODE_WARNING: 引发 E_WARNING 错误
        // PDO::ERRMODE_EXCEPTION: 抛出 exceptions 异常
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
    ];
}
