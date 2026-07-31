<?php

use config\Cors;
use library\enum\HttpMethod;
use library\mysmarty\App;
use library\mysmarty\Cache;
use library\mysmarty\Cookie;
use library\mysmarty\Query;
use library\mysmarty\Session;

/**
 * 格式化字节单位
 * @param int $size 多少字节
 * @param int $decimals 小数点保留几位
 * @return string
 */
function formatFileSize(int $size, int $decimals = 2): string
{
    if ($size <= 0) {
        $str = 0;
    } else if ($size < 1024) {
        $str = $size . 'bytes';
    } else if ($size < 1048576) {
        $str = number_format($size / 1024, $decimals, '.', '') . 'KB';
    } else if ($size < 1073741824) {
        $str = number_format($size / 1048576, $decimals, '.', '') . 'MB';
    } else if ($size < 1099511627776) {
        $str = number_format($size / 1073741824, $decimals, '.', '') . 'GB';
    } else {
        $str = number_format($size / 1099511627776, $decimals, '.', '') . 'TB';
    }
    return $str;
}

/**
 * 是否为GET请求
 * @return bool
 */
function isGet(): bool
{
    return getServerValue('REQUEST_METHOD') === 'GET';
}

/**
 * 是否为POST请求
 * @return bool
 */
function isPost(): bool
{
    return getServerValue('REQUEST_METHOD') === 'POST';
}

/**
 * 是否为PUT请求
 * @return bool
 */
function isPut(): bool
{
    return getServerValue('REQUEST_METHOD') === 'PUT';
}

/**
 * 是否为DELTE请求
 * @return bool
 */
function isDelete(): bool
{
    return getServerValue('REQUEST_METHOD') === 'DELETE';
}

/**
 * 是否为HEAD请求
 * @return bool
 */
function isHead(): bool
{
    return getServerValue('REQUEST_METHOD') === 'HEAD';
}

/**
 * 是否为PATCH请求
 * @return bool
 */
function isPatch(): bool
{
    return getServerValue('REQUEST_METHOD') === 'PATCH';
}

/**
 * 是否为OPTIONS请求
 * @return bool
 */
function isOptions(): bool
{
    return getServerValue('REQUEST_METHOD') === 'OPTIONS';
}

/**
 * 判断当前是否为cgi模式
 * @return bool
 */
function isCgiMode(): bool
{
    return str_starts_with(PHP_SAPI, 'cgi');
}

/**
 * 获取当前分配的php内存，字节
 * 1字节(B) = 8 位(bit)
 * 1 kb = 1024 字节
 * 1 mb = 1024 kb
 * @return int
 */
function getMemoryUsage(): int
{
    return memory_get_usage();
}

/**
 * 获取当前时间，微秒
 * 1 毫秒 = 1000 微秒
 * 1 秒 = 1000 毫秒
 * @return float
 */
function getCurrentMicroTime(): float
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 判断服务器是否是windows操作系统
 * @return bool
 */
function isWin(): bool
{
    if (stripos(PHP_OS, 'WIN') === 0) {
        return true;
    }
    return false;
}

/**
 * 下载图片
 * @param string $imgSrc
 * @return bool|string
 */
function downloadImg(string $imgSrc): string|bool
{
    if (0 === stripos($imgSrc, '//')) {
        $imgSrc = 'https:' . $imgSrc;
    }
    if (0 === stripos($imgSrc, 'http')) {
        if (preg_match('/\.jpg/i', $imgSrc)) {
            $hz = 'jpg';
        } else if (preg_match('/\.jpeg/i', $imgSrc)) {
            $hz = 'jpeg';
        } else if (preg_match('/\.gif/i', $imgSrc)) {
            $hz = 'gif';
        } else {
            $hz = 'png';
        }
        $data = Query::getInstance()->setPcUserAgent()
            ->setRandIp()
            ->setUrl($imgSrc)
            ->request();
    } else if (0 === stripos($imgSrc, 'data:image')) {
        if (preg_match('~^data:image/(.+);base64,~i', $imgSrc, $mat)) {
            if (false !== stripos($mat[1], 'icon')) {
                $hz = 'ico';
            } else if (false !== stripos($mat[1], 'jpg')) {
                $hz = 'jpg';
            } else if (false !== stripos($mat[1], 'jpeg')) {
                $hz = 'jpeg';
            } else if (false !== stripos($mat[1], 'gif')) {
                $hz = 'gif';
            } else {
                $hz = 'png';
            }
            $data = base64_decode(str_ireplace($mat[0], '', $imgSrc));
        } else {
            return false;
        }
    } else {
        return $imgSrc;
    }
    if (empty($data)) {
        return false;
    }
    $pathDir = '/upload/' . date('Ymd');
    $dir = ROOT_DIR . '/public' . $pathDir;
    if (!createDir($dir)) {
        return false;
    }
    $filename = md5(time() . $imgSrc) . '.' . $hz;
    if (file_put_contents($dir . '/' . $filename, $data)) {
        return $pathDir . '/' . $filename;
    }
    return false;
}

/**
 * 获取当前主域，包含端口
 * @return string
 */
function getDomain(): string
{
    $domain = \config\App::APP_DOMAIN ?: getServerValue('HTTP_HOST');
    if (empty($domain)) {
        $serverName = getServerValue('SERVER_NAME');
        if (!empty($serverName)) {
            $serverPort = getServerValue('SERVER_PORT', 80);
            if (!in_array($serverPort, [80, 443])) {
                $domain = $serverName . ':' . $serverPort;
            } else {
                $domain = $serverName;
            }
        }
    }
    return $domain;
}

/**
 * 去掉空格
 * @param string $str
 * @return string
 */
function myTrim(string $str): string
{
    $str = preg_replace('/^(&nbsp;|\s|<br[\s]*[\/]?>|[\x{200B}-\x{200D}])+|(&nbsp;|\s|<br[\s]*[\/]?>|[\x{200B}-\x{200D}])+$/iu', '', $str);
    return trim($str);
}

/**
 * 在控制台输出一条消息并换行
 * @param string $msg
 */
function echoCliMsg(string $msg): void
{
    echo $msg . PHP_EOL;
}

/**
 * 获取中文字符串
 * @param int $num 多少个
 * @return string
 */
function getZhChar(int $num = 1): string
{
    $char = '';
    for ($i = 0; $i < $num; $i++) {
        $tmp = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
        $char .= iconv('GB2312', 'UTF-8', $tmp);
    }
    return $char;
}

/**
 * 获取body请求的数据
 * @return false|string
 */
function getRequestBodyContent(): string|bool
{
    return file_get_contents('php://input');
}

/**
 * 刷新页面
 * @param string $url 刷新的网址
 * @param int $refreshTime 刷新间隔时间，单位秒
 */
function refresh(string $url = '', int $refreshTime = 1)
{
    $url = getFixedUrl($url);
    echo '<meta http-equiv="refresh" content="' . $refreshTime . ';url=' . $url . '">';
    exit();
}

/**
 * 是否为控制台模式
 * @return bool
 */
function isCliMode(): bool
{
    return PHP_SAPI === 'cli';
}

/**
 * 获取剩余内存占比
 * @return int 0 - 100
 */
function getMemFreeRate(): int
{
    $data = getMemInfo();
    if (empty($data)) {
        return 0;
    }
    return (int)(100 * $data['MemFree'] / $data['MemTotal']);
}

/**
 * 设置缓存
 * @param string $name 键
 * @param mixed $value 值
 * @param int $expire 过期时间
 * @return bool
 */
function setCache(string $name, mixed $value, int $expire = 3600): bool
{
    return Cache::set($name, $value, $expire);
}

/**
 * 获取缓存
 * @param string $name 键
 * @param mixed $defValue 默认值
 * @return mixed
 */
function getCache(string $name, mixed $defValue = ''): mixed
{
    return Cache::get($name, $defValue);
}

/**
 * 删除缓存
 * @param string $name 键
 * @return bool
 */
function deleteCache(string $name): bool
{
    return Cache::delete($name);
}

/**
 * 清空缓存文件
 * @param int $expire 多久前创建的缓存文件，单位秒
 * @return int 返回清空的文件数
 */
function clearCache(int $expire = 0): int
{
    return Cache::clear($expire);
}

/**
 * 获取浏览器useragent
 * @return string
 */
function getUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * 获取server值
 * @param string $name
 * @param string $defValue
 * @return string
 */
function getServerValue(string $name, string $defValue = ''): string
{
    return $_SERVER[$name] ?? $defValue;
}

/**
 * 格式化js
 * @param string $js
 * @return string
 */
function formatJs(string $js): string
{
    // 替换 /* */
    $js = preg_replace('/\/\*.*\*\//Uis', '', $js);
    $js = preg_replace('/([^:\'"\\\=])\/\/.*([\n]|[\r\n])?/i', '$1', $js);
    // 替换换行
    $js = preg_replace('/([\n]|[\r\n])/', '', $js);
    $js = preg_replace('/[\t]+/', ' ', $js);
    // 替换两个空格及以上空格 为一个
    $js = preg_replace('/[ ]{2,}/', ' ', $js);
    return myTrim($js);
}

/**
 * 格式化css
 * @param string $css
 * @return string
 */
function formatCss(string $css): string
{
    $css = preg_replace('/\/\*.*\*\//Uis', '', $css);
    // 替换换行
    $css = preg_replace('/([\n]|[\r\n])/', '', $css);
    $css = preg_replace('/[\t]+/', ' ', $css);
    // 替换两个空格及以上空格 为一个
    return preg_replace('/[ ]{2,}/', ' ', $css);
}

/**
 * 格式化html
 * @param string $html html代码的字符串
 * @return string
 */
function formatHtml(string $html): string
{
    // 不替换pre内的内容
    $preData = [];
    if (preg_match_all('/<pre[^>]*>(.*)<\/pre>/iUs', $html, $mat)) {
        foreach ($mat[1] as $k => $v) {
            $key = 'pre_' . md5($k);
            $preData[$key] = $v;
            $html = str_ireplace($v, $key, $html);
        }
    }
    // 页面中的代码注释
    $html = preg_replace('/<!--.*-->/Us', '', $html);
    // 页面中匹配到js代码
    $reg = '/<script[^>]*>(.*)<\/script>/iUs';
    $html = preg_replace_callback($reg, function ($matchs) {
        $js = preg_replace('/\/\*.*\*\//Uis', '', $matchs[0]);
        return preg_replace('/([^:\'"\\\=])\/\/.*([\n]|[\r\n])?/i', '$1', $js);
    }, $html);
    // 页面中匹配到css代码
    $reg = '/<style[^>]*>(.*)<\/style>/iUs';
    $html = preg_replace_callback($reg, function ($matchs) {
        return preg_replace('/\/\*.*\*\//Uis', '', $matchs[0]);
    }, $html);
    // 替换换行
    $html = preg_replace('/([\n]|[\r\n])/', '', $html);
    $html = preg_replace('/[\t]+/', ' ', $html);
    // 替换两个空格及以上空格 为一个
    $html = preg_replace('/[ ]{2,}/', ' ', $html);
    foreach ($preData as $k => $v) {
        $html = str_ireplace($k, $v, $html);
    }
    return myTrim($html);
}

/**
 * 获取内存信息，单位，字节（kb）
 * 仅支持在Linux系统运行
 * @return array
 */
function getMemInfo(): array
{
    $data = [];
    if (getPlatformName() === 'linux') {
        exec('cat /proc/meminfo', $output);
        if (!empty($output)) {
            foreach ($output as $o) {
                $oArr = explode(':', $o);
                $data[trim($oArr[0])] = intval($oArr[1]);
            }
        }
    }
    return $data;
}

/**
 * 获取操作系统平台
 * @return string
 */
function getPlatformName(): string
{
    return strtolower(PHP_OS);
}

/**
 * 500服务端错误
 * @param string $msg 错误信息
 * @param int $code 返回码
 */
function error(string $msg, int $code = 503): void
{
    $url = '/';
    if ($code >= 400 && $code < 500) {
        $url = 'javascript:history.go(-1);';
    }
    tip($msg, $url, $code);
}

/**
 * 文件未找到
 */
function notFound(): void
{
    tip('页面未找到', '/', 404);
}

/**
 * 重定向
 * @param string $url 跳转网址
 * @param int $code 状态码
 */
function redirect(string $url, int $code = 301): void
{
    $url = getFixedUrl($url);
    header('Location: ' . $url, true, $code);
    exit();
}

/**
 * 获取网站网址
 * @return string
 */
function getAbsoluteUrl(): string
{
    if (!defined('URL')) {
        define('URL', getServerValue('REQUEST_SCHEME', 'http') . '://' . getDomain());
    }
    return URL;
}

/**
 * 提示跳转
 * @param string $msg 提示消息
 * @param string $url 跳转网址
 * @param int $code 返回码
 * @param bool $formatUrl 是否格式化链接
 * @param string $icon 页面提示图标 success、fail、404、500等数字
 */
function tip(string $msg, string $url = '', int $code = 200, bool $formatUrl = true, string $icon = 'fail'): void
{
    http_response_code($code);
    if (isCliMode()) {
        echoCliMsg($msg);
        exit();
    }
    if ($formatUrl) {
        $url = getFixedUrl($url);
    }
    if (200 !== $code) {
        $icon = $code;
    }
    $html = file_get_contents(LIBRARY_DIR . '/tpl/tip.html');
    $html = str_ireplace('{$url}', $url, $html);
    $html = str_ireplace('{$msg}', $msg, $html);
    $html = str_ireplace('{$title}', '提示信息', $html);
    $html = str_ireplace('{$tip}', '温馨提示', $html);
    $html = str_ireplace('{$jump}', '立即跳转', $html);
    $html = str_ireplace('{$icon}', $icon, $html);
    echoHtmlHeader();
    echo $html;
    exit();
}

/**
 * 获取数据
 * @param string $name 字段名称
 * @param mixed|null $defValue 默认值
 * @param HttpMethod $httpMethod 获取方式
 * @return mixed
 */
function input(string $name, mixed $defValue = null, HttpMethod $httpMethod = HttpMethod::request): mixed
{
    return match ($httpMethod) {
        HttpMethod::get => $_GET[$name] ?? $defValue,
        HttpMethod::post => $_POST[$name] ?? $defValue,
        HttpMethod::request => $_REQUEST[$name] ?? $defValue,
        HttpMethod::files => $_FILES[$name] ?? $defValue,
        HttpMethod::cookie => $_COOKIE[$name] ?? $defValue,
        HttpMethod::session => $_SESSION[$name] ?? $defValue,
        HttpMethod::env => $_ENV[$name] ?? $defValue,
        HttpMethod::server => $_SERVER[$name] ?? $defValue,
        default => $defValue,
    };
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param string $defValue 默认值
 * @param bool $trim 是否去掉空格
 * @return string
 */
function getString(string $name, string $defValue = '', bool $trim = true): string
{
    $value = input($name, $defValue, HttpMethod::get);
    if (is_string($value)) {
        if ($trim) {
            $value = myTrim($value);
        }
        return $value;
    }
    return $defValue;
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param int $defValue 默认值
 * @return int
 */
function getInt(string $name, int $defValue = 0): int
{
    return (int)getNumeric($name, $defValue);
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param float $defValue 默认值
 * @return int
 */
function getFloat(string $name, float $defValue = 0): int
{
    return (float)getNumeric($name, $defValue);
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param int|float $defValue 默认值
 * @return int|float
 */
function getNumeric(string $name, int|float $defValue = 0): int|float
{
    $value = input($name, $defValue, HttpMethod::get);
    if (is_numeric($value)) {
        return $value;
    }
    return $defValue;
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param array $defValue
 * @return array
 */
function getAarray(string $name, array $defValue = []): array
{
    $value = input($name, $defValue, HttpMethod::get);
    if (is_array($value)) {
        return $value;
    }
    return $defValue;
}

/**
 * 获取上传文件表单的值
 * @param string $name
 * @param array $defValue
 * @return array
 */
function getFiles(string $name, array $defValue = []): array
{
    return input($name, $defValue, HttpMethod::files);
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param string $defValue
 * @param bool $trim 是否去掉空格
 * @return string
 */
function getPostString(string $name, string $defValue = '', bool $trim = true): string
{
    $value = input($name, $defValue, HttpMethod::post);
    if (is_string($value)) {
        if ($trim) {
            $value = myTrim($value);
        }
        return $value;
    }
    return $defValue;
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param int $defValue
 * @return int
 */
function getPostInt(string $name, int $defValue = 0): int
{
    return (int)getPostNumeric($name, $defValue);
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param float $defValue
 * @return float
 */
function getPostFloat(string $name, float $defValue = 0): float
{
    return (float)getPostNumeric($name, $defValue);
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param int|float $defValue
 * @return int|float
 */
function getPostNumeric(string $name, int|float $defValue = 0): int|float
{
    $value = input($name, $defValue, HttpMethod::post);
    if (is_numeric($value)) {
        return $value;
    }
    return $defValue;
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param array $defValue
 * @return array
 */
function getPostAarray(string $name, array $defValue = []): array
{
    $value = input($name, $defValue, HttpMethod::post);
    if (is_array($value)) {
        return $value;
    }
    return $defValue;
}

/**
 * 获取客户端ip
 * @param bool $getProxyIp 是否获取代理ip
 * @return string
 */
function getIp(bool $getProxyIp = false): string
{
    if ($getProxyIp) {
        $realIp = '';
        if (!empty(getServerValue('HTTP_X_FORWARDED_FOR'))) {
            $arr = explode(',', getServerValue('HTTP_X_FORWARDED_FOR'));
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip !== 'unknown') {
                    $realIp = $ip;
                    break;
                }
            }
        } else if (!empty(getServerValue('HTTP_CLIENT_IP'))) {
            $realIp = getServerValue('HTTP_CLIENT_IP');
        }
        if (isIp($realIp)) {
            return $realIp;
        }
    }
    return getServerValue('REMOTE_ADDR');
}

/**
 * 检测是否是合法的IP地址
 * @param string $ip IP地址
 * @param string $type IP地址类型 (ipv4, ipv6)
 * @return boolean
 */
function isValidIp(string $ip, string $type = ''): bool
{
    $flag = match (strtolower($type)) {
        'ipv4' => FILTER_FLAG_IPV4,
        'ipv6' => FILTER_FLAG_IPV6,
        default => 0,
    };
    return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
}

/**
 * 生成url
 * @param string $path url path部分
 * @return string
 */
function generateUrl(string $path = ''): string
{
    return getFixedUrl($path);
}

/**
 * 格式化控制器名称，转为每个单词首字母大写（将_分隔的小写控制器）
 * @param string $controller 控制器名称
 * @return string
 */
function formatController(string $controller): string
{
    return toHumpName($controller);
}

/**
 * 将下划线分割的字符串转为大写连接
 * @param string $str 字符串
 * @return string
 */
function toHumpName(string $str): string
{
    return str_ireplace('_', '', ucwords($str, '_'));
}

/**
 * 格式化方法
 * @param string $action 转为每个单词首字母大写，第一个字母转为小写（将_分隔的小写方法）
 * @return string
 */
function formatAction(string $action): string
{
    return lcfirst(str_ireplace('_', '', ucwords($action, '_')));
}

/**
 * 输出json数据
 * @param int $status
 * @param array|string $data
 * @param string $msg
 * @param int $type
 */
function echoJson(int $status = 1, array|string $data = [], string $msg = '', int $type = JSON_UNESCAPED_UNICODE): void
{
    json([
        'data' => $data,
        'status' => $status,
        'msg' => $msg
    ], $type);
}

/**
 * 输出json数据
 * @param array|string $data
 * @param int $type
 */
function json(string|array $data, int $type = JSON_UNESCAPED_UNICODE): void
{
    echoJsonHeader();
    $access_control_allow_origin = Cors::ACCESS_CONTROL_ALLOW_ORIGIN;
    if (!empty($access_control_allow_origin)) {
        header('Access-Control-Allow-Origin:' . $access_control_allow_origin);
    }
    $access_control_allow_credentials = Cors::ACCESS_CONTROL_ALLOW_CREDENTIALS;
    if (!empty($access_control_allow_credentials)) {
        header('Access-Control-Allow-Credentials:' . $access_control_allow_credentials);
    }
    $access_control_allow_methods = Cors::ACCESS_CONTROL_ALLOW_METHODS;
    if (!empty($access_control_allow_methods)) {
        header('Access-Control-Allow-Methods:' . $access_control_allow_methods);
    }
    $access_control_allow_headers = Cors::ACCESS_CONTROL_ALLOW_HEADERS;
    if (!empty($access_control_allow_headers)) {
        header('Access-Control-Allow-Headers:' . $access_control_allow_headers);
    }
    $access_control_expose_headers = Cors::ACCESS_CONTROL_EXPOSE_HEADERS;
    if (!empty($access_control_expose_headers)) {
        header('Access-Control-Expose-Headers:' . $access_control_expose_headers);
    }
    $access_control_max_age = Cors::ACCESS_CONTROL_MAX_AGE;
    if ($access_control_max_age > 0) {
        header('Access-Control-Max-Age:' . $access_control_max_age);
    }
    if (is_array($data)) {
        echo json_encode($data, $type);
    } else {
        echo $data;
    }
    exit();
}

/**
 * 将大写分割为_连接的小写字符串，如MyName -> my_name
 * @param string $name 待转换的字符串
 * @param string $splitStr 分割字符串
 * @return string
 */
function toDivideName(string $name, string $splitStr = ''): string
{
    if (empty($splitStr)) {
        $name = preg_replace('/([A-Z])/', '_$1', $name);
        $name = strtolower(trim($name, '_'));
    } else {
        $splitRegStr = preg_quote($splitStr);
        if (preg_match('#[' . $splitRegStr . ']#', $name)) {
            $tmp = preg_split('#[' . $splitRegStr . ']#', $name);
            $name = '';
            foreach ($tmp as $v) {
                if (empty($name)) {
                    $name = toDivideName($v);
                } else {
                    $name .= $splitStr . toDivideName($v);
                }
            }
        } else {
            $name = toDivideName($name);
        }
    }
    return $name;
}

/**
 * 效验邮箱是否正确
 * @param string $email 电子邮箱
 * @return boolean
 */
function isEmail(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return true;
}

/**
 * 验证手机号
 * @param string $phone 手机号
 * @return boolean
 */
function isPhone(string $phone): bool
{
    if (!preg_match('/^1\d{10}$/', $phone)) {
        return false;
    }
    return true;
}

/**
 * 验证url
 * @param string $url 网址
 * @return boolean
 */
function isUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    return true;
}

/**
 * ip是否有效
 * @param string $ip
 * @return bool
 */
function isIp(string $ip): bool
{
    return isValidIp($ip);
}

/**
 * 判断是否是域名
 * @param string $domain 域名
 * @param bool $strict 是否过滤掉IP域名
 * @return boolean
 */
function isDomain(string $domain, bool $strict = true): bool
{
    $result = boolval(filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME));
    if ($result) {
        if ($strict && isIp($domain)) {
            return false;
        }
        return true;
    }
    return false;
}

/**
 * 判断是否是主域名
 * @param string $domain 域名
 * @return boolean
 */
function isMainDomain(string $domain): bool
{
    $isDomain = isDomain($domain);
    if (!$isDomain) {
        return false;
    }
    $tmp = explode('.', $domain);
    $num = count($tmp);
    switch ($num) {
        case 2:
            return true;
        case 3:
            array_shift($tmp);
            $domain = strtolower(implode('.', $tmp));
            if (in_array($domain, [
                'com.cn',
                'net.cn',
                'org.cn',
                'ac.cn',
                'gov.cn',
                'ah.cn',
                'bj.cn',
                'cq.cn',
                'fj.cn',
                'gd.cn',
                'gx.cn',
                'gz.cn',
                'gs.cn',
                'he.cn',
                'hl.cn',
                'ha.cn',
                'hn.cn',
                'hi.cn',
                'hb.cn',
                'hk.cn',
                'jl.cn',
                'js.cn',
                'jx.cn',
                'ln.cn',
                'mo.cn',
                'nm.cn',
                'nx.cn',
                'qh.cn',
                'sh.cn',
                'sx.cn',
                'sd.cn',
                'sc.cn',
                'sn.cn',
                'tj.cn',
                'tw.cn',
                'xz.cn',
                'xj.cn',
                'yn.cn',
                'zj.cn'
            ])) {
                return true;
            }
            return false;
    }
    return false;
}

/**
 * 判断当前是不是手机端
 * @return boolean
 */
function isMobile(): bool
{
    if (preg_match('/mobile|android|iphone/i', getServerValue('HTTP_USER_AGENT'))) {
        return true;
    }
    return false;
}

/**
 * 获取修正后的url
 * @param string $url
 * @return string
 */
function getFixedUrl(string $url = ''): string
{
    if (empty($url)) {
        $url = getAbsoluteUrl();
    } else if (false === stripos($url, 'http') && false === stripos($url, 'javascript:')) {
        $url = getAbsoluteUrl() . '/' . trim($url, '/');
    }
    return $url;
}

/**
 * 获取session值
 * @param string $name
 * @return mixed
 */
function getSession(string $name): mixed
{
    return Session::getInstance()->get($name, false);
}

/**
 * 设置session
 * @param string $name
 * @param mixed $value
 */
function setSession(string $name, mixed $value): void
{
    Session::getInstance()->set($name, $value);
}

/**
 * 删除session
 * @param string $name
 */
function deleteSession(string $name): void
{
    Session::getInstance()->delete($name);
}

/**
 * 开启session
 */
function startSession(): void
{
    Session::getInstance()->startSession();
}

/**
 * 删除所有session
 */
function clearAllSession(): void
{
    Session::getInstance()->clear();
}

/**
 * 获取cookie值
 * @param string $name
 * @return string
 */
function getLocalCookie(string $name): string
{
    return Cookie::getInstance()->get($name, false);
}

/**
 * 设置cookie
 * @param string $name key
 * @param string $value 值
 * @param int|null $expire 多少秒后过期
 * @return bool
 */
function setLocalCookie(string $name, string $value, int|null $expire = null): bool
{
    return Cookie::getInstance()->set($name, $value, $expire);
}

/**
 * 删除cookie
 * @param string $name key
 * @return bool
 */
function deleteCookie(string $name): bool
{
    return Cookie::getInstance()->delete($name);
}

/**
 * 清除所有cookie
 */
function clearAllCookie(): void
{
    Cookie::getInstance()->clear();
}

/**
 * 格式化时间
 * @param int $time 时间，单位秒
 * @return string
 */
function formatTime(int $time): string
{
    $timeDifference = time() - $time;
    $units = [
        31536000 => '年',
        2592000 => '个月',
        604800 => '周',
        86400 => '天',
        3600 => '小时',
        60 => '分钟',
        1 => '秒'
    ];
    if ($timeDifference < 0) {
        return date('Y-m-d H:i:s', $time);
    } else if ($timeDifference < 60) {
        return '刚刚';
    }
    foreach ($units as $seconds => $unit) {
        $interval = floor($timeDifference / $seconds);
        if ($interval >= 1) {
            return $interval . $unit . '前';
        }
    }
    return date('Y-m-d H:i:s', $time);
}

/**
 * 将xml结构转为数组
 * @param string $xml
 * @return array
 */
function xmlToArray(string $xml): array
{
    try {
        $xml = preg_replace('/<!\[CDATA\[(.*)]]>/isU', '$1', $xml);
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 将数组转为标准的xml结构
 * @param array $data
 * @return string|bool
 * @throws Exception
 */
function arrayToXml(array $data): string|bool
{
    $xml = arrayToXmlStr($data);
    if (empty($xml)) {
        return false;
    }
    $xmlObj = new SimpleXMLElement($xml);
    return $xmlObj->asXML();
}

/**
 * 将数组转为xml字符串
 * @param array $data
 * @return string
 */
function arrayToXmlStr(array $data): string
{
    $xml = '';
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $xml .= '<' . $k . '>' . arrayToXmlStr($v) . '</' . $k . '>';
        } else {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
    }
    return $xml;
}

/**
 * 判断字符串是否为中文字符
 * @param string $str
 * @return bool
 */
function isZh(string $str): bool
{
    if (!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $str)) {
        return false;
    }
    return true;
}

/**
 * 判断字符串是否包含中文字符
 * @param string $str
 * @return bool
 */
function hasZh(string $str): bool
{
    if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $str)) {
        return true;
    }
    return false;
}

/**
 * 通过给定的文件创建目录
 * @param string $file 文件路径
 * @return bool
 */
function createDirByFile(string $file): bool
{
    if (file_exists($file)) {
        return true;
    }
    $dirname = pathinfo($file, PATHINFO_DIRNAME);
    return createDir($dirname);
}

/**
 * 创建文件夹
 * @param string $dir
 * @return bool
 */
function createDir(string $dir): bool
{
    if (!is_dir($dir)) {
        return mkdir($dir, 0777, true);
    }
    return true;
}

/**
 * 输出html响应头
 */
function echoHtmlHeader(): void
{
    header('content-type:text/html;charset=utf-8');
}

/**
 * 输出json响应头
 */
function echoJsonHeader(): void
{
    header('content-type:application/json;charset=utf-8');
}

/**
 * 截取字符串
 * @param string $str 原字符
 * @param int $len 截取长度
 * @return string
 */
function len(string $str, int $len = 30): string
{
    $str = strip_tags($str);
    $str = preg_replace('/^[\s　]+/', '', $str);
    if (mb_strlen($str, 'utf-8') < $len) {
        return $str;
    }
    return mb_substr($str, 0, $len) . '...';
}

/**
 * 格式化时间
 * @param string|int $time 时间戳或时间格式
 * @param string $format Y-m-d H:i:s
 * @return string
 */
function formatToTime(string|int $time, string $format = 'Y-m-d H:i:s'): string
{
    if (!is_int($time)) {
        $time = strtotime($time);
    }
    if (empty($time)) {
        return '';
    }
    return date($format, $time);
}

/**
 * 获取url的panthinfo，不包含请求参数
 * @return string
 */
function getPath(): string
{
    if (!defined('URI_PATH')) {
        $path = parse_url(getServerValue('REQUEST_URI'), PHP_URL_PATH);
        if (empty($path)) {
            error('路径格式无法解析');
        }
        $pathinfo = urldecode($path);
        // 是否部署在二级目录上
        $deployPath = \config\App::DEPLOY_PATH;
        if (!empty($deployPath)) {
            $deployPath = trim($deployPath, '/');
            $pathinfo = preg_replace('/^\/' . preg_quote($deployPath, '/') . '/i', '', $pathinfo);
        }
        define('URI_PATH', trim($pathinfo, '/'));
    }
    return URI_PATH;
}

/**
 * 获取当前页面唯一的缓存key
 * @return string
 */
function getCacheKey(): string
{
    if (!defined('CACHE_KEY')) {
        define('CACHE_KEY', getServerValue('REQUEST_URI'));
    }
    return CACHE_KEY;
}

/**
 * 判断当前请求是否为网页html请求
 * @return bool
 */
function isRequestHtml(): bool
{
    return str_contains(getServerValue('HTTP_ACCEPT'), 'text/html');
}

/**
 * 判断当前请求是否为json请求
 * @return bool
 */
function isRequestJson(): bool
{
    return str_contains(getServerValue('HTTP_ACCEPT'), 'json');
}

/**
 * 获取指定文件夹内class的命名空间地址
 * @param string $dir
 * @return array
 */
function getNamespaceClass(string $dir): array
{
    static $classData = [];
    static $prefix = ROOT_DIR . '/';
    if (file_exists($dir)) {
        //读取$dir目录下的配置
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($dir . '/' . $file)) {
                    getNamespaceClass($dir . '/' . $file);
                } else {
                    if (str_ends_with($file, '.php')) {
                        $classData[] = str_ireplace('/', '\\', str_ireplace($prefix, '', $dir . '/' . str_ireplace('.php', '', $file)));
                    }
                }
            }
        }
    }
    return $classData;
}

/**
 * 获取当前访问路径，不包含GET请求参数
 * @return string
 */
function getHref(): string
{
    $uri = getPath();
    if (!empty($uri)) {
        return getAbsoluteUrl() . '/' . $uri;
    }
    return getAbsoluteUrl();
}

/**
 * 判断指定的文件是否为真实的图片文件，需要打开fileinfo扩展
 * @param string $filePath 文件路径
 * @return bool
 */
function isImage(string $filePath): bool
{
    if (file_exists($filePath)) {
        return str_starts_with(mime_content_type($filePath), 'image');
    }
    return false;
}

/**
 * 递归删除文件夹内容
 * @param string $dir 文件夹
 * @param bool $deleteDir 是否删除文件夹
 * @return bool
 */
function removeDir(string $dir, bool $deleteDir = false): bool
{
    if (!is_dir($dir)) {
        return true;
    }
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $f = $dir . '/' . $file;
        if (is_dir($f)) {
            removeDir($f, $deleteDir);
            if ($deleteDir) {
                rmdir($dir);
            }
        } else {
            unlink($f);
        }
    }
    if ($deleteDir) {
        return rmdir($dir);
    }
    return true;
}

/**
 * 将数组转为PHP文件代码
 * @param array $data 待转换的数组
 * @return string
 */
function arrFormatFile(array $data): string
{
    return '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';';
}

/**
 * 从配置文件中读取数组数据
 * @param string $file 数组配置文件
 * @return array
 */
function requireArrData(string $file): array
{
    $data = require $file;
    if (is_array($data)) {
        return $data;
    }
    return [];
}

/**
 * 获取随机字符串
 * @param int $len 字符串长度
 * @param int $type 字符串类型：1 小写字符串 2 大写字符串 其它则为大小写混合字符串
 * @param string $specialString 特殊字符：默认随机字符串仅包含字母和数字
 * @return string
 */
function getRandomString(int $len = 32, int $type = 1, string $specialString = ''): string
{
    $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $specialString;
    $ret = '';
    $strlen = strlen($base);
    for ($i = 0; $i < $len; ++$i) {
        $ret .= $base[mt_rand(0, $strlen - 1)];
    }
    if (1 === $type) {
        $ret = strtolower($ret);
    } else if (2 === $type) {
        $ret = strtoupper($ret);
    }
    return $ret;
}

/**
 * 生成访问uri
 * @param string $class 访问地址的类位置，例如：app\controller\Index
 * @param string $method 访问地址的类方法名称
 * @param array $methodParams 访问地址的动态参数
 * @return string
 */
function buildUri(string $class, string $method, array $methodParams = []): string
{
    $routes = App::getInstance()->getRouteData();
    $uri = '';
    foreach ($routes as $v) {
        if ($class === $v['class'] && $method === $v['methodName']) {
            if (!$v['methodHome']) {
                $uri = $v['uri'];
            }
            break;
        }
    }
    if (!empty($uri) && !empty($methodParams)) {
        $uri = preg_replace_callback('#([(].+[)])#iU', function ($mat) use (&$methodParams) {
            $value = '';
            if (preg_match('#<([^>]+)>#iU', $mat[1], $keyMat)) {
                $key = $keyMat[1];
                if (isset($methodParams[$key])) {
                    $value = $methodParams[$key];
                } else {
                    $value = current($methodParams) ?: '';
                    next($methodParams);
                }
            }
            return $value;
        }, $uri);
    }
    return stripslashes($uri);
}

/**
 * 生成访问地址
 * @param string $class 访问地址的类位置，例如：app\controller\Index
 * @param string $method 访问地址的类方法名称
 * @param array $methodParams 访问地址的动态参数
 * @param string $prefixUrl 如果不是当前域名的访问地址，请提供域名（包含http、端口（非80、443））
 * @return string
 */
function buildUrl(string $class, string $method, array $methodParams = [], string $prefixUrl = ''): string
{
    $uri = buildUri($class, $method, $methodParams);
    if (str_starts_with($prefixUrl, 'http')) {
        return $prefixUrl . '/' . $uri;
    } else {
        return getAbsoluteUrl() . '/' . $uri;
    }
}

/**
 * 提示信息
 * @param string $url 确认按钮跳转url
 * @param string $content 提示内容
 * @param string $title 提示标题
 * @param string $confirmText 确认按钮文字
 * @param string $cancelUrl 取消按钮跳转url
 * @param string $cancelText 取消按钮文字
 * @param bool $showCancel 是否显示
 * @param int $type 类别：1 成功 2 失败 3 提示
 */
function alert(string $url, string $content, string $title = '温馨提示', string $confirmText = '确定', string $cancelUrl = '', string $cancelText = '取消', bool $showCancel = false, int $type = 1)
{
    if (empty($url)) {
        $url = 'javascript:void(0);';
    }
    if (empty($cancelUrl)) {
        $cancelUrl = 'javascript:void(0);';
    }
    $backgroundColor = match ($type) {
        1 => '#28a745',
        2 => '#dc3545',
        3 => '#007bff',
        default => '#ffc107',
    };
    $html = file_get_contents(LIBRARY_DIR . '/tpl/alert.html');
    $html = str_ireplace('{$title}', $title, $html);
    $html = str_ireplace('{$content}', $content, $html);
    $html = str_ireplace('{$url}', $url, $html);
    $html = str_ireplace('{$cancelUrl}', $cancelUrl, $html);
    $html = str_ireplace('{$confirmText}', $confirmText, $html);
    $html = str_ireplace('{$cancelText}', $cancelText, $html);
    $html = str_ireplace('{$backgroundColor}', $backgroundColor, $html);
    $html = str_ireplace('{$hideCancel}', $showCancel ? '' : ' hide-cancel', $html);
    echoHtmlHeader();
    echo $html;
    exit();
}

/**
 * 获取config配置值，区分大小写
 * @param string $configClass 配置文件的类名
 * @param string $property 配置文件的属性名
 * @param mixed $defValue 默认值
 * @return mixed
 */
function config(string $configClass, string $property, mixed $defValue = null): mixed
{
    if (defined('\app\config\\' . $configClass . '::' . $property)) {
        return constant('\app\config\\' . $configClass . '::' . $property);
    }
    if (defined('\config\\' . $configClass . '::' . $property)) {
        return constant('\config\\' . $configClass . '::' . $property);
    }
    return $defValue;
}

/**
 * 判断是否是爬虫
 * @return bool
 */
function isBot(): bool
{
    $userAgent = getServerValue('HTTP_USER_AGENT');
    if (preg_match('/(bot|spider)/i', $userAgent)) {
        return true;
    }
    return false;
}
