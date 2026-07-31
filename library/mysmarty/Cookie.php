<?php

namespace library\mysmarty;

/**
 * Cookie类
 */
class Cookie extends Container
{
    private int $expire = 3600;
    private string $path = '/';
    private string $domain = '';
    private bool $secure = false;
    private bool $httponly = false;

    public function _initialize()
    {
        $this->expire = \config\Cookie::EXPIRE;
        $this->path = \config\Cookie::PATH;
        $this->domain = \config\Cookie::DOMAIN;
        $this->secure = \config\Cookie::SECURE;
        $this->httponly = \config\Cookie::HTTPONLY;
    }

    /**
     * @return int
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * @param int $expire
     */
    public function setExpire(int $expire): void
    {
        $this->expire = $expire;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param bool $secure
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * @return bool
     */
    public function isHttponly(): bool
    {
        return $this->httponly;
    }

    /**
     * @param bool $httponly
     */
    public function setHttponly(bool $httponly): void
    {
        $this->httponly = $httponly;
    }

    /**
     * 设置
     * @param string $name
     * @param string $value
     * @param int|null $expire 多少秒后过期
     * @return bool
     */
    public function set(string $name, string $value, int|null $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = time() + $this->expire;
        } else {
            if ($expire > 0) {
                $expire = time() + $expire;
            }
        }
        return setcookie($name, $value, $expire, $this->path, $this->domain, $this->secure, $this->httponly);
    }

    /**
     * 获取
     * @param string $name
     * @param string $defValue
     * @return string
     */
    public function get(string $name, string $defValue = ''): string
    {
        return $_COOKIE[$name] ?? $defValue;
    }

    /**
     * 删除
     * @param string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        if (isset($_COOKIE[$name])) {
            return setcookie($name, '', time() - 3600, $this->path, $this->domain, $this->secure, $this->httponly);
        }
        return false;
    }

    /**
     * 清空
     */
    public function clear(): void
    {
        foreach ($_COOKIE as $k => $v) {
            setcookie($k, '', time() - 3600, $this->path, $this->domain, $this->secure, $this->httponly);
        }
    }

    /**
     * 获取所有cookie
     * @return array
     */
    public function getAll(): array
    {
        return $_COOKIE;
    }
}