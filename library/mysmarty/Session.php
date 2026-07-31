<?php

namespace library\mysmarty;

/**
 * session操作类
 */
class Session extends Container
{
    public function __construct()
    {
        $this->_initialize();
    }

    public function _initialize()
    {
        $this->startSession();
    }

    /**
     * 开启session
     */
    public function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $lifetime = \config\Session::LIFETIME;
            $path = \config\Session::PATH;
            $domain = \config\Session::DOMAIN;
            $secure = \config\Session::SECURE;
            $httponly = \config\Session::HTTPONLY;
            $sessionName = \config\Session::NAME;
            if (!empty($sessionName)) {
                session_name($sessionName);
            }
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly
            ]);
            session_start();
        }
    }

    /**
     * 设置
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, mixed $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * 获取
     * @param string $name
     * @param mixed $defValue
     * @return string
     */
    public function get(string $name, mixed $defValue = ''): mixed
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return $defValue;
    }

    /**
     * 删除
     * @param string $name
     */
    public function delete(string $name): void
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * 清空
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * 获取所有session
     * @return array
     */
    public function getAll(): array
    {
        return $_SESSION;
    }

    /**
     * 获取当前会话ID
     * @return string
     */
    public function getSessionId(): string
    {
        $sessionId = session_id();
        if (false === $sessionId) {
            return '';
        }
        return $sessionId;
    }
}