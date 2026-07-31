<?php

namespace library\mysmarty;

/**
 * 加密与解密
 */
class Encrypt extends Container
{
    /**
     * @var string 密码学方式
     */
    private string $method;

    /**
     * @var string 非 NULL 的初始化向量
     */
    private string $iv;

    /**
     * 加密数据
     * @param string $msg
     * @param string $key 自定义key
     * @return bool|string
     */
    public function encode(string $msg, string $key = ''): bool|string
    {
        if (empty($key)) {
            $key = \config\App::ENCRYPTION_KEY;
        }
        $method = $this->getMethod();
        $iv = $this->getIv($method);
        return openssl_encrypt($msg, $method, $key, 0, $iv);
    }

    /**
     * 解密数据
     * @param string $data
     * @param string $key
     * @return bool|string
     */
    public function decode(string $data, string $key = ''): bool|string
    {
        if (empty($key)) {
            $key = \config\App::ENCRYPTION_KEY;
        }
        $method = $this->getMethod();
        $iv = $this->getIv($method);
        return openssl_decrypt($data, $method, $key, 0, $iv);
    }

    /**
     * 获取密码学方式
     * @return string
     */
    private function getMethod(): string
    {
        if (empty($this->method)) {
            $this->method = openssl_get_cipher_methods()[0];
        }
        return $this->method;
    }

    /**
     * 获取Vector (iv)
     * @param string $method
     * @return string
     */
    private function getIv(string $method): string
    {
        if (empty($this->iv)) {
            $ivlen = openssl_cipher_iv_length($method);
            $this->iv = str_pad('', $ivlen, '0');
        }
        return $this->iv;
    }

    /**
     * 设置密码学方式
     * @param string $method 密码学方式
     * @return static
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置 非 NULL 的初始化向量
     * @param string $iv
     * @return static
     */
    public function setIv(string $iv): static
    {
        $this->iv = $iv;
        return $this;
    }
}