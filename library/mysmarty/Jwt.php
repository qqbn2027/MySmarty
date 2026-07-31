<?php

namespace library\mysmarty;

use SodiumException;

/**
 * JWT
 */
class Jwt extends Container
{
    const ASN1_INTEGER = 0x02;
    const ASN1_SEQUENCE = 0x10;
    const ASN1_BIT_STRING = 0x03;

    /**
     * 密钥
     * @var string
     */
    private mixed $key;

    /**
     * 算法名称
     * @var string
     */
    private string $alg;

    /**
     * 额外的响应头数据
     * @var array
     */
    private array $header;

    /**
     * 错误信息
     * @var string
     */
    private string $error = '';

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->key = \config\Jwt::KEY;
        $this->alg = \config\Jwt::ALG;
        $this->header = [];
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 获取当前密钥
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * 设置密钥
     * @param mixed $key
     * @return static
     */
    public function setKey(mixed $key): static
    {
        $this->key = $key;
        return $this;
    }

    /**
     * 算法名称
     * @return string
     */
    public function getAlg(): string
    {
        return $this->alg;
    }

    /**
     * 设置算法名称
     * @param string $alg
     * @return static
     */
    public function setAlg(string $alg): static
    {
        $this->alg = $alg;
        return $this;
    }

    /**
     * 获取额外的响应头数据
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * 设置额外的响应头数据
     * @param array $header
     * @return static
     */
    public function setHeader(array $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 编码
     * @param array $payload 待编码的数据
     * @param int $expireTime 过期时间，单位：秒
     * @return bool|string
     */
    public function encode(array $payload, int $expireTime = 3600): bool|string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->alg
        ];
        if (!empty($this->header)) {
            $header = array_merge($header, $this->header);
        }
        $segment1 = $this->safeEncode(json_encode($header));
        if (!isset($payload['iss'])) {
            $payload['iss'] = getAbsoluteUrl();
        }
        if (!isset($payload['aud'])) {
            $payload['aud'] = getAbsoluteUrl();
        }
        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }
        if (!isset($payload['nbf'])) {
            $payload['nbf'] = time();
        }
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + $expireTime;
        }
        $segment2 = $this->safeEncode(json_encode($payload));
        $segment3 = $this->sign($segment1 . '.' . $segment2);
        if (false === $segment3) {
            return false;
        }
        $segment3 = $this->safeEncode($segment3);
        $this->_initialize();
        return $segment1 . '.' . $segment2 . '.' . $segment3;
    }

    /**
     * 生成签名
     * @param string $str
     * @return bool|string
     */
    private function sign(string $str): bool|string
    {
        $parame = $this->getAlgParame($this->alg);
        switch ($parame[0]) {
            case 'hash_hmac':
                return hash_hmac($parame[1], $str, $this->key, true);
            case 'openssl':
                $signature = '';
                if (!openssl_sign($str, $signature, $this->key, $parame[1])) {
                    $this->error = '签名失败';
                    return false;
                }
                if ('ES256' === $this->alg) {
                    $signature = $this->correctSignature($signature, 256);
                } elseif ('ES384' === $this->alg) {
                    $signature = $this->correctSignature($signature, 384);
                }
                return $signature;
            case 'sodium_crypto':
                try {
                    $lines = array_filter(explode("\n", $this->key));
                    $key = base64_decode(end($lines));
                    return sodium_crypto_sign_detached($str, $key);
                } catch (SodiumException $e) {
                    $this->error = '签名失败：' . $e->getMessage();
                    return false;
                }
        }
        $this->error = '签名失败：未找到对应的算法名称';
        return false;
    }

    /**
     * 编码
     * @param string $signature
     * @param int $size
     * @return string
     */
    private function correctSignature(string $signature, int $size): string
    {
        list($offset,) = $this->readSignature($signature);
        list($offset, $r) = $this->readSignature($signature, $offset);
        list(, $s) = $this->readSignature($signature, $offset);
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, $size / 8, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, $size / 8, "\x00", STR_PAD_LEFT);
        return $r . $s;
    }

    /**
     * 读取数据进行解码
     * @param string $signature
     * @param int $offset
     * @return array
     */
    private function readSignature(string $signature, int $offset = 0): array
    {
        $pos = $offset;
        $size = strlen($signature);
        $constructed = (ord($signature[$pos]) >> 5) & 0x01;
        $type = ord($signature[$pos++]) & 0x1f;
        $len = ord($signature[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | ord($signature[$pos++]);
            }
        }
        if ($type == self::ASN1_BIT_STRING) {
            $pos++;
            $data = substr($signature, $pos, $len - 1);
            $pos += $len - 1;
        } else if (!$constructed) {
            $data = substr($signature, $pos, $len);
            $pos += $len;
        } else {
            $data = '';
        }
        return [$pos, $data];
    }

    /**
     * 获取加密方式
     * @param string $alg 算法名称
     * @return string[]
     */
    private function getAlgParame(string $alg = ''): array
    {
        if (empty($alg)) {
            $alg = $this->alg;
        }
        $algs = [
            'ES384' => ['openssl', 'SHA384'],
            'ES256' => ['openssl', 'SHA256'],
            'HS256' => ['hash_hmac', 'SHA256'],
            'HS384' => ['hash_hmac', 'SHA384'],
            'HS512' => ['hash_hmac', 'SHA512'],
            'RS256' => ['openssl', 'SHA256'],
            'RS384' => ['openssl', 'SHA384'],
            'RS512' => ['openssl', 'SHA512'],
            'EdDSA' => ['sodium_crypto', 'EdDSA']
        ];
        return $algs[$alg] ?? $algs['HS256'];
    }

    /**
     * 对字符串进行安全编码
     * @param string $str
     * @return string
     */
    private function safeEncode(string $str): string
    {
        return str_replace('=', '', strtr(base64_encode($str), '+/', '-_'));
    }

    /**
     * 对字符串进行安全解码
     * @param string $str
     * @return bool|string
     */
    private function safeDecode(string $str): bool|string
    {
        $remainder = strlen($str) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $str .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * 解码
     * @param string $token
     * @return false|array
     */
    public function decode(string $token): bool|array
    {
        $tokenArr = explode('.', $token);
        if (3 !== count($tokenArr)) {
            $this->error = '解密失败：token 格式错误';
            return false;
        }
        list($headb64, $payloadb64, $cryptob64) = $tokenArr;
        $header = $this->safeDecode($headb64);
        if (false === $header) {
            $this->error = '解密失败：header 解码失败';
            return false;
        }
        $header = json_decode($header, true, flags: JSON_BIGINT_AS_STRING);
        if (empty($header)) {
            $this->error = '解密失败：header 解码失败';
            return false;
        }
        $payload = $this->safeDecode($payloadb64);
        if (false === $payload) {
            $this->error = '解密失败：payload 解码失败';
            return false;
        }
        $payload = json_decode($payload, true, flags: JSON_BIGINT_AS_STRING);
        if (empty($payload)) {
            $this->error = '解密失败：payload 解码失败';
            return false;
        }
        $signature = $this->safeDecode($cryptob64);
        if (false === $signature) {
            $this->error = '解密失败：signature 解码失败';
            return false;
        }
        if (empty($header['alg'])) {
            $this->error = '算法名称为空';
            return false;
        }
        $alg = $header['alg'];
        if ('ES256' === $alg || 'ES384' === $alg) {
            $signature = $this->restoreSignature($signature);
        }
        $parame = $this->getAlgParame($alg);
        if (!$this->verify($headb64 . '.' . $payloadb64, $signature, $parame[0], $parame[1])) {
            $this->error = '签名验证失败';
            return false;
        }
        if ($payload['nbf'] > time() || $payload['iat'] > time()) {
            $this->error = 'token 不合法';
            return false;
        }
        if ($payload['exp'] <= time()) {
            $this->error = 'token 已过期';
            return false;
        }
        $this->_initialize();
        return $payload;
    }

    /**
     * 验证签名
     * @param string $str
     * @param string $signature
     * @param string $type
     * @param string $algorithm
     * @return bool
     */
    private function verify(string $str, string $signature, string $type, string $algorithm): bool
    {
        switch ($type) {
            case 'openssl':
                $result = openssl_verify($str, $signature, $this->key, $algorithm);
                if (1 === $result) {
                    return true;
                } else if (0 === $result) {
                    $this->error = '签名验证失败';
                    return false;
                } else {
                    $this->error = '签名验证失败：' . openssl_error_string();
                    return false;
                }
            case 'sodium_crypto':
                try {
                    $lines = array_filter(explode("\n", $this->key));
                    $key = base64_decode(end($lines));
                    return sodium_crypto_sign_verify_detached($signature, $str, $key);
                } catch (SodiumException $e) {
                    $this->error = '签名验证失败：' . $e->getMessage();
                }
                return false;
            case 'hash_hmac':
                $hash = hash_hmac($algorithm, $str, $this->key, true);
                return hash_equals($signature, $hash);
        }
        return false;
    }

    /**
     * 转换签名
     * @param string $signature
     * @return string
     */
    private function restoreSignature(string $signature): string
    {
        list($r, $s) = str_split($signature, (int)(strlen($signature) / 2));
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        if (ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }
        if (ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }
        return $this->encodeValue(self::ASN1_SEQUENCE, $this->encodeValue(self::ASN1_INTEGER, $r) . $this->encodeValue(self::ASN1_INTEGER, $s));
    }

    /**
     * 编码
     * @param int $type
     * @param string $value
     * @return string
     */
    private function encodeValue(int $type, string $value): string
    {
        $tagHeader = 0;
        if ($type === self::ASN1_SEQUENCE) {
            $tagHeader |= 0x20;
        }
        $der = chr($tagHeader | $type);
        $der .= chr(strlen($value));
        return $der . $value;
    }
}