<?php

namespace library\mysmarty;

use GdImage;

/**
 * 验证码类
 */
class Captcha extends Container
{

    /**
     * 图像高度
     * @var int
     */
    private int $height = 50;

    /**
     * 图像上显示的字符
     * @var string
     */
    private string $code = '';

    /**
     * 验证码类型
     * @var \library\enum\Captcha
     */
    private \library\enum\Captcha $codeStyle = \library\enum\Captcha::NUMBER_AND_LETTER;

    /**
     * 验证码长度
     * @var int
     */
    private int $codeSize = 4;

    /**
     * 字体大小
     * @var int
     */
    private int $font = 25;

    /**
     * 字体文件
     * @var string
     */
    private string $fontFile = '';

    /**
     * 验证码session名称
     * @var string
     */
    private static string $sessionName = 'code';

    /**
     * 是否为ajax请求验证码
     * @var bool
     */
    private static bool $ajax = false;

    /**
     * ajax请求验证码名称
     * @var string
     */
    private static string $ajaxName = '';

    /**
     * 过期时间，单位秒
     * @var int
     */
    private int $expireTime = 300;

    /**
     * 设置字体文件
     * @param string $fontFile 字体文件所在位置
     * @return static
     */
    public function setFontFile(string $fontFile): static
    {
        $this->fontFile = $fontFile;
        return $this;
    }

    /**
     * 设置字体大小
     * @param int $font
     * @return static
     */
    public function setFont(int $font): static
    {
        $this->font = $font;
        return $this;
    }

    /**
     * 设置验证码session名称
     * @param string $sessionName
     * @return static
     */
    public function setSessionName(string $sessionName): static
    {
        self::$sessionName = $sessionName;
        return $this;
    }

    /**
     * 设置ajax请求验证码
     * @param bool $ajax 是否为ajax请求验证码
     * @return static
     */
    public function setAjax(bool $ajax = false): static
    {
        self::$ajax = $ajax;
        return $this;
    }

    /**
     * 设置验证码高度
     * @param int $height
     * @return static
     */
    public function setHeight(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 设置验证码字符
     * @param string $code
     * @return static
     */
    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置验证码类型
     * @param \library\enum\Captcha $codeStyle
     * @return  static
     */
    public function setCodeStyle(\library\enum\Captcha $codeStyle = \library\enum\Captcha::NUMBER_AND_LETTER): static
    {
        $this->codeStyle = $codeStyle;
        return $this;
    }

    /**
     * 设置验证码长度
     * @param int $codeSize
     * @return static
     */
    public function setCodeSize(int $codeSize): static
    {
        $this->codeSize = $codeSize;
        return $this;
    }

    /**
     * 设置过期时间
     * @param int $expireTime 单位：秒
     * @return static
     */
    public function setExpireTime(int $expireTime): static
    {
        $this->expireTime = $expireTime;
        return $this;
    }

    /**
     * 获取随机字符串
     * @return string
     */
    private function getCode(): string
    {
        $str = '0123456789qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return $this->getCodeByStr($str);
    }

    /**
     * 获取随机一个字符串
     * @return string
     */
    private function getOneCode(): string
    {
        $str = '0123456789qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return substr(str_shuffle($str), 0, 1);
    }

    /**
     * 获取指定的字符串数据
     * @param string $str
     * @return string
     */
    private function getCodeByStr(string $str): string
    {
        return substr(str_shuffle($str), 0, $this->codeSize);
    }

    /**
     * 获取数字验证码
     * @return string
     */
    private function getNumCode(): string
    {
        $str = '0123456789';
        return $this->getCodeByStr($str);
    }

    /**
     * 获取字母验证码
     * @return string
     */
    private function getLetterCode(): string
    {
        $str = 'qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return $this->getCodeByStr($str);
    }

    /**
     * 获取中文验证码
     * @return string
     */
    private function getZhCode(): string
    {
        return getZhChar($this->codeSize);
    }

    /**
     * 设置字体
     * @param int $font
     * @return static
     */
    public function font(int $font): static
    {
        return $this->setFont($font);
    }

    /**
     * 输出或者返回验证码数据
     * @return array|null
     */
    public function output(): null|array
    {
        if (self::$ajax) {
            self::$ajaxName = getRandomString();
            return [
                'token' => self::$ajaxName,
                'code' => self::getBase64Image()
            ];
        } else {
            header('Content-Type: image/webp');
            $im = $this->generateImage();
            imagewebp($im);
            exit();
        }
    }

    /**
     * 生成验证码
     * @return false|GdImage
     */
    private function generateImage(): GdImage|bool
    {
        $kWidth = 20;
        if (empty($this->code)) {
            switch ($this->codeStyle) {
                case \library\enum\Captcha::NUMBER_AND_LETTER:
                    $this->code = $this->getCode();
                    break;
                case \library\enum\Captcha::NUMBER:
                    $this->code = $this->getNumCode();
                    break;
                case \library\enum\Captcha::LETTER:
                    $this->code = $this->getLetterCode();
                    break;
                case \library\enum\Captcha::ZH:
                    $this->code = $this->getZhCode();
                    $kWidth = 30;
                    break;
            }
        }
        $cacheData = [
            'code' => strtolower($this->code),
            'expireTime' => time() + $this->expireTime
        ];
        if (self::$ajax) {
            setCache(self::$ajaxName, $cacheData, $this->expireTime);
        } else {
            setSession(self::$sessionName, $cacheData);
        }
        // 判断验证码
        if (empty($this->fontFile)) {
            $this->fontFile = LIBRARY_DIR . '/font/noto-sans-sc.otf';
        }
        // 创建画布
        $codeArr = preg_split('//u', $this->code);
        $codeLen = count($codeArr);
        $width = $codeLen * $kWidth;
        $im = @imagecreatetruecolor($width, $this->height);
        // 背景颜色
        $backgroundColor = imagecolorallocatealpha($im, 243, 251, 254, 0);
        imagefilledrectangle($im, 0, 0, $width - 1, $this->height - 1, $backgroundColor);
        // 计算坐标
        $imgInfo = imagettfbbox($this->font, 0, $this->fontFile, $this->code);
        //开始y位置
        $y = (int)(($this->height - $imgInfo[3] - $imgInfo[5]) / 2);
        // 写字符串
        for ($i = 0; $i < $codeLen; $i++) {
            $angle = mt_rand(-20, 20);
            $text_color = imagecolorallocate($im, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
            // 画验证码
            $x = (int)($i * $kWidth);
            imagefttext($im, $this->font, $angle, $x, $y, $text_color, $this->fontFile, $codeArr[$i]);
            $text_color = imagecolorallocate($im, mt_rand(150, 255), mt_rand(150, 255), mt_rand(150, 255));
            imagestring($im, 5, $x, $y + mt_rand(-1 * $y, 5), $this->getOneCode(), $text_color);
            // 画干扰线
            imageline($im, mt_rand(0, $width), mt_rand(0, $this->height), mt_rand(0, $width), mt_rand(0, $this->height), $text_color);
        }
        return $im;
    }

    /**
     * 生成验证码的base64编码
     * @return string
     */
    public function getBase64Image(): string
    {
        $im = $this->generateImage();
        $fileName = RUNTIME_DIR . '/captcha/captcha.png';
        createDirByFile($fileName);
        imagepng($im, $fileName);
        $imgContent = file_get_contents($fileName);
        $imgEncode = base64_encode($imgContent);
        $imgInfo = getimagesize($fileName);
        return "data:{$imgInfo['mime']};base64," . $imgEncode;
    }

    /**
     * 验证验证码
     * @param string $code 输入的字符
     * @param string $name 验证码存在session/ajax中的名称
     * @param bool $delete 是否验证完成后删除验证码
     * @return bool
     */
    public static function check(string $code, string $name = '', bool $delete = true): bool
    {
        if (self::$ajax) {
            if (empty($name)) {
                return false;
            }
            $codeData = getCache($name);
        } else {
            if (empty($name)) {
                $name = self::$sessionName;
            }
            $codeData = getSession($name);
        }
        if ($delete) {
            self::deleteCode($name);
        }
        $expireTime = $codeData['expireTime'] ?? 0;
        if ($expireTime < time()) {
            return false;
        }
        $cacheCode = $codeData['code'] ?? '';
        if (!empty($cacheCode) && $cacheCode === strtolower($code)) {
            return true;
        }
        return false;
    }

    /**
     * 删除验证码
     * @param string $name 验证码存在session/ajax中的名称
     * @return void
     */
    public static function deleteCode(string $name = ''): void
    {
        if (self::$ajax) {
            if (!empty($name)) {
                deleteCache($name);
            }
        } else {
            if (empty($name)) {
                $name = self::$sessionName;
            }
            deleteSession($name);
        }
    }
}