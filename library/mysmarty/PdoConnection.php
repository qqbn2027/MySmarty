<?php

namespace library\mysmarty;

use PDO;

/**
 * pdo连接
 */
class PdoConnection
{

    /**
     * 获取单一实例
     * @param string $dsn 数据源名称
     * @param string $username DSN字符串中的用户名
     * @param string $password DSN字符串中的密码
     * @param array $driverOptions 一个具体驱动的连接选项的 键=>值 数组
     * @return PDO
     */
    public static function getInstance(string $dsn, string $username = '', string $password = '', array $driverOptions = []): PDO
    {
        return new PDO($dsn, $username, $password, $driverOptions);
    }

    /**
     * 通过dsn获取数据库连接对象
     * @param string $dsn dsn
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param array $driverOptions 数据库连接驱动选项
     * @return PDO
     */
    public static function connectByDsn(string $dsn, string $username = '', string $password = '', array $driverOptions = []): PDO
    {
        return self::getInstance($dsn, $username, $password, $driverOptions);
    }

    /**
     * 通过 mysql unix_socket 获取数据库连接对象
     * @param string $unixSocket
     * @param string $dbname 数据库名称
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $charset 数据库编码
     * @param array $driverOptions 数据库连接驱动选项
     * @return PDO
     */
    public static function connectByUnixSocket(string $unixSocket, string $dbname, string $username = '', string $password = '', string $charset = 'utf8mb4', array $driverOptions = []): PDO
    {
        return self::getInstance(self::getDsnByUnixSocket($unixSocket, $dbname, $charset), $username, $password, $driverOptions);
    }

    /**
     * 通过数据库主机获取数据库连接对象
     * @param string $dbname 数据库名称
     * @param string $host 数据库主机
     * @param int $port 数据库端口
     * @param string $username 数据库用户名
     * @param string $password 数据库密码
     * @param string $charset 数据库编码
     * @param array $driverOptions 数据库连接驱动选项
     * @return PDO
     */
    public static function connectByHost(string $host, string $dbname, int $port, string $username = '', string $password = '', string $charset = 'utf8mb4', array $driverOptions = []): PDO
    {
        return self::getInstance(self::getDsn($host, $dbname, $port, $charset), $username, $password, $driverOptions);
    }

    /**
     * 获取数据库连接dsn
     * @param string $dbname 数据库名称
     * @param string $host 数据库主机
     * @param int $port 数据库端口
     * @param string $charset 数据库字符编码
     * @return string
     */
    public static function getDsn(string $host, string $dbname, int $port, string $charset = 'utf8mb4'): string
    {
        return 'mysql:dbname=' . $dbname . ';host=' . $host . ';port=' . $port . ';charset=' . $charset;
    }

    /**
     * 根据mysql unix_socket 获取dsn
     * @param string $unixSocket
     * @param string $dbname 数据库名称
     * @param string $charset 数据库字符编码
     * @return string
     */
    public static function getDsnByUnixSocket(string $unixSocket, string $dbname, string $charset = 'utf8mb4'): string
    {
        return 'mysql:unix_socket=' . $unixSocket . ';dbname=' . $dbname . ';charset=' . $charset;
    }
}