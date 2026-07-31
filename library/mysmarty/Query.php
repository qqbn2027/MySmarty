<?php

namespace library\mysmarty;

use CURLStringFile;

/**
 * 请求查询类
 */
class Query extends Container
{
    private string $url = '';
    private int $outputHeader = 0;
    private int $followLocation = 1;
    private int $returnTransfer = 1;
    private int $timeOut = 20;
    private string $userAgent = '';
    private string $cookieFile = '';
    private array|string $postFields = [];
    private array $header = [];
    private string $ip = '';
    private string $referer = '';
    private bool $verifypeer = true;
    private string $srcCharset = '';
    private string $proxyIp = '';
    private int $proxyType = 0;
    private string $dohUrl = '';
    private int $protocols = 0;
    private int $sslOptions = 0;
    private string $encoding = '';
    private array $curlInfo = [];
    private string $error = '';
    private int $errno = 0;
    private string $headerContent = '';

    // 查询缓存
    private int $cacheTime = 0;// 缓存时间，0为不缓存
    private string $cacheKey = '';// 缓存key

    /**
     * 初始化变量
     */
    private function initVar()
    {
        $this->url = '';
        $this->outputHeader = 0;
        $this->followLocation = 1;
        $this->returnTransfer = 1;
        $this->timeOut = 20;
        $this->userAgent = '';
        $this->cookieFile = '';
        $this->postFields = [];
        $this->header = [];
        $this->ip = '';
        $this->referer = '';
        $this->verifypeer = true;
        $this->srcCharset = '';
        $this->proxyIp = '';
        $this->proxyType = 0;
        $this->dohUrl = '';
        $this->protocols = 0;
        $this->sslOptions = 0;
        $this->encoding = '';
        $this->cacheTime = 0;
        $this->cacheKey = '';
    }

    /**
     * CURLPROTO_*的位掩码。启用时，会限制 libcurl 在传输过程中可使用哪些协议。
     * @param int $protocols
     * @return static
     */
    public function setProtocols(int $protocols): static
    {
        $this->protocols = $protocols;
        return $this;
    }

    /**
     * 设置 CURLOPT_DOH_URL
     * @param string $dohUrl
     * @return static
     */
    public function setDohUrl(string $dohUrl): static
    {
        $this->dohUrl = $dohUrl;
        return $this;
    }

    /**
     * 设置 CURLOPT_SSL_OPTIONS，例如：CURLSSLOPT_NATIVE_CA
     * @param int $sslOptions
     * @return $this
     */
    public function setSslOptions(int $sslOptions): static
    {
        $this->sslOptions = $sslOptions;
        return $this;
    }

    /**
     * 设置 CURLOPT_ENCODING，例如：gzip, deflate, br
     * @param string $encoding
     * @return $this
     */
    public function setEncoding(string $encoding): static
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * 设置请求url
     * @param string $url 一个网址
     * @return static
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 启用时会将头文件的信息作为数据流输出
     * @param int $outputHeader 0 不输出 ，1 输出
     * @return static
     */
    public function setOutputHeader(int $outputHeader): static
    {
        $this->outputHeader = $outputHeader;
        return $this;
    }

    /**
     * TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向。（注意：这是递归的，"Location: " 发送几次就重定向几次，除非设置了 CURLOPT_MAXREDIRS，限制最大重定向次数。）。
     * @param int $followLocation 0 不重定向，1 重定向
     * @return static
     */
    public function setFollowLocation(int $followLocation): static
    {
        $this->followLocation = $followLocation;
        return $this;
    }

    /**
     * 返回原生的（Raw）内容
     * @param int $returnTransfer 0 不返回，1 返回
     * @return static
     */
    public function setReturnTransfer(int $returnTransfer): static
    {
        $this->returnTransfer = $returnTransfer;
        return $this;
    }

    /**
     * 允许 cURL 函数执行的最长秒数
     * @param int $timeOut 多少秒
     * @return static
     */
    public function setTimeOut(int $timeOut): static
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    /**
     * 设置原网页网页编码
     * @param string $srcCharset
     * @return static
     */
    public function setSrcCharset(string $srcCharset): static
    {
        $this->srcCharset = $srcCharset;
        return $this;
    }

    /**
     * 在HTTP请求中包含一个"User-Agent: "头的字符串。
     * @param string $userAgent 浏览器标识。
     * @return static
     */
    public function setUserAgent(string $userAgent = ''): static
    {
        if (empty($userAgent)) {
            $userAgent = getUserAgent();
        }
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * 包含 cookie 数据的文件名，cookie 文件的格式可以是 Netscape 格式，或者只是纯 HTTP 头部风格，存入文件。如果文件名是空的，不会加载 cookie，但 cookie 的处理仍旧启用。
     * @param string $cookieFile cookie存放位置
     * @return static
     */
    public function setCookieFile(string $cookieFile): static
    {
        $this->cookieFile = $cookieFile;
        return $this;
    }

    /**
     * 全部数据使用HTTP协议中的 "POST" 操作来发送
     * @param string|array $postFields 可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，也可以使用一个以字段名为键值，字段数据为值的数组。
     * @return static
     */
    public function setPostFields(array|string $postFields): static
    {
        $this->postFields = $postFields;
        return $this;
    }

    /**
     * 设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100')
     * @param array $header
     * @return static
     */
    public function setHeader(array $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置请求模拟ip
     * @param string $ip
     * @return static
     */
    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * 设置随机请求模拟ip
     * @return static
     */
    public function setRandIp(): static
    {
        $this->ip = mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255);
        return $this;
    }

    /**
     * 在HTTP请求头中"Referer: "的内容。
     * @param string $referer
     * @return static
     */
    public function setReferer(string $referer): static
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * FALSE 禁止 cURL 验证对等证书（peer's certificate）
     * @param bool $verifypeer
     * @return static
     */
    public function setVerifypeer(bool $verifypeer): static
    {
        $this->verifypeer = $verifypeer;
        return $this;
    }

    /**
     * 缓存时间
     * @param int $cacheTime
     * @return $this
     */
    public function setCacheTime(int $cacheTime): static
    {
        $this->cacheTime = $cacheTime;
        return $this;
    }

    /**
     * 缓存key
     * @param string $cacheKey
     * @return $this
     */
    public function setCacheKey(string $cacheKey): static
    {
        $this->cacheKey = md5($cacheKey);
        return $this;
    }

    /**
     * 获取原始数据
     * @return string
     */
    public function request(): string
    {
        // 判断是否有缓存
        $cacheKey = '';
        $cacheTime = $this->cacheTime;
        if ($cacheTime > 0) {
            if (!empty($this->cacheKey)) {
                $cacheKey = $this->cacheKey;
            } else {
                $cacheKey = md5($this->url);
            }
            $content = getCache($cacheKey);
            if (!empty($content)) {
                return $content;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, $this->outputHeader);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $this->returnTransfer);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        if (!empty($this->userAgent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        if (!empty($this->cookieFile)) {
            if (!file_exists($this->cookieFile)) {
                file_put_contents($this->cookieFile, '');
            }
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        }
        if (!empty($this->postFields)) {
            // 判断文件
            if (is_array($this->postFields)) {
                foreach ($this->postFields as $k2 => $v) {
                    if (is_array($v)) {
                        $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                        $this->postFields[$k2] = $v;
                    } else if (is_string($v)) {
                        if (preg_match('/^@/i', $v)) {
                            $this->setFile($k2, ltrim($v, '@'));
                        }
                    }
                }
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields);
        }
        if (!empty($this->ip)) {
            if (!empty($this->header)) {
                $this->header[] = 'X-FORWARDED-FOR:' . $this->ip;
                $this->header[] = 'CLIENT-IP:' . $this->ip;
            } else {
                $this->header = array(
                    'X-FORWARDED-FOR:' . $this->ip,
                    'CLIENT-IP:' . $this->ip
                );
            }
        }
        if (!empty($this->header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        }
        if (!empty($this->referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
        if (!empty($this->proxyIp)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyIp);
            curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyType);
        }
        if (defined('CURLOPT_DOH_URL') && !empty($this->dohUrl)) {
            curl_setopt($ch, CURLOPT_DOH_URL, $this->dohUrl);
        }
        if (!empty($this->protocols)) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, $this->protocols);
        }
        if (!empty($this->sslOptions)) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, $this->sslOptions);
        }
        curl_setopt($ch, CURLOPT_ENCODING, $this->encoding);
        $content = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->errno = curl_errno($ch);
        $this->curlInfo = curl_getinfo($ch);
        if (PHP_VERSION < '8.0.0') {
            curl_close($ch);
        }
        if (false === $content) {
            return '';
        }
        if ($this->outputHeader && !empty($this->curlInfo['header_size'])) {
            $this->headerContent = substr($content, 0, $this->curlInfo['header_size']);
            $content = substr($content, $this->curlInfo['header_size']);
        }
        if (!empty($this->srcCharset) && strtolower($this->srcCharset) != 'utf-8') {
            $content = mb_convert_encoding($content, 'utf-8', $this->srcCharset);
        }
        $this->initVar();
        if (!empty($cacheKey) && !empty($content) && !empty($this->curlInfo['http_code']) && 200 === $this->curlInfo['http_code']) {
            setCache($cacheKey, $content, $cacheTime);
        }
        return $content;
    }

    /**
     * 发送body请求
     * @param array|string $body 发送的数据,json格式，数组会自动转为json格式
     * @return string|array
     */
    public function sendBody(array|string $body): array|string
    {
        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        return $this->setHeader(array_merge($this->header, ['Content-Type: application/json', 'Content-Length:' . strlen($body)]))
            ->setPostFields($body)
            ->request();
    }

    /**
     * 设置谷歌浏览器useragent
     * @return static
     */
    public function setPcUserAgent(): static
    {
        return $this->setUserAgent('Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36');
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandPcUserAgent(): static
    {
        $userAgents = UserAgent::getPcUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandMobileUserAgent(): static
    {
        $userAgents = UserAgent::getMobileUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandMobileSpiderUserAgent(): static
    {
        $userAgents = UserAgent::getMobileSpiderUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandPcSpiderUserAgent(): static
    {
        $userAgents = UserAgent::getPcSpiderUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandSpiderUserAgent(): static
    {
        $userAgents = UserAgent::getSpiderUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置浏览器useragent
     * @return static
     */
    public function setRandUserAgent(): static
    {
        $userAgents = UserAgent::getUserAgent();
        shuffle($userAgents);
        return $this->setUserAgent($userAgents[0]);
    }

    /**
     * 设置手机浏览器useragent
     * @return static
     */
    public function setMobileUserAgent(): static
    {
        return $this->setUserAgent('Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Mobile Safari/537.36');
    }

    /**
     * 设置代理ip
     * @param string|array $ip 数组格式的会随机选择一个作为代理IP。形如 111.11.2.113:8080
     * @param int $proxyType 代理类型，0 http,2 https
     * @return static
     */
    public function setProxyIp(array|string $ip, int $proxyType = 0): static
    {
        if (is_array($ip)) {
            shuffle($ip);
            $this->proxyIp = $ip[0];
        } else {
            $this->proxyIp = $ip;
        }
        $this->proxyType = $proxyType;
        return $this;
    }

    /**
     * 添加上传的文件
     * @param string $field 上传字段名称
     * @param string $file 上传文件
     * @return static
     */
    public function setFile(string $field, string $file): static
    {
        if (file_exists($file)) {
            if (!empty($this->postFields) && is_string($this->postFields)) {
                $this->postFields = json_decode($this->postFields, true);
            }
            $mimeType = mime_content_type($file);
            $this->postFields[$field] = new CURLStringFile(file_get_contents($file), $file, $mimeType);
        }
        return $this;
    }

    /**
     * 设置cookie
     * @param array $cookie 键值对数组
     * @return static
     */
    public function setCookie(array $cookie): static
    {
        $cookieStr = '';
        foreach ($cookie as $k => $v) {
            if (empty($cookieStr)) {
                $cookieStr = $k . '=' . $v;
            } else {
                $cookieStr .= '; ' . $k . '=' . $v;
            }
        }
        if (!empty($cookieStr)) {
            if (!empty($this->header)) {
                $this->header[] = ['Cookie: ' . $cookieStr];
            } else {
                $this->header = ['Cookie: ' . $cookieStr];
            }
        }
        return $this;
    }

    /**
     *  获取一个cURL连接资源句柄的信息
     * @return array
     */
    public function getCurlInfo(): array
    {
        return $this->curlInfo;
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
     * 获取错误码
     * @return int
     */
    public function getErrno(): int
    {
        return $this->errno;
    }

    /**
     * 获取请求头信息，包含换行
     * @return string
     */
    public function getHeaderContent(): string
    {
        return $this->headerContent;
    }
}