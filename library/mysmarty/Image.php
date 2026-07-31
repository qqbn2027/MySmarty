<?php

namespace library\mysmarty;

/**
 * 图像处理类
 */
class Image extends Container
{
    /**
     * 当前图片文件
     * @var string
     */
    private string $imageFile;

    /**
     * 当前图片资源
     * @var resource
     */
    private $im;

    /**
     * 当前图片宽度
     * @var int
     */
    private int $width;

    /**
     * 当前图片高度
     * @var int
     */
    private int $height;

    /**
     * 当前图片类型
     * @var int
     */
    private int $type = 2;

    /**
     * quality 范围从0（最低质量，最小文件体积）到100 （最好质量, 最大文件体积）。
     * @var int
     */
    private int $jpgQuality = 100;

    /**
     * quality 范围从0（最低质量，最小文件体积）到100 （最好质量, 最大文件体积）。
     * @var int
     */
    private int $webpQuality = 100;

    /**
     * Compression level: from 0 (no compression) to 9.The default (-1) uses the zlib compression default
     * @var int
     */
    private int $pngQuality = 9;

    /**
     * Whether the BMP should be compressed with run-length encoding (RLE), or not.
     * @var bool
     */
    private bool $bmpCompressed = true;

    /**
     * 设置图片类型，1 = GIF，2 = JPG，3 =PNG，6 = BMP，18 = WEBP
     * @param int $type
     * @return static
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置保存图片的质量
     * @param int $jpgQuality 范围从0（最低质量，最小文件体积）到100 （最好质量, 最大文件体积）
     * @return static
     */
    public function setJpgQuality(int $jpgQuality): static
    {
        $this->jpgQuality = $jpgQuality;
        return $this;
    }

    /**
     * 设置保存图片的质量
     * @param int $webpQuality 范围从0（最低质量，最小文件体积）到100 （最好质量, 最大文件体积）
     * @return static
     */
    public function setWebpQuality(int $webpQuality): static
    {
        $this->webpQuality = $webpQuality;
        return $this;
    }

    /**
     * 设置png图片压缩级别
     * @param int $pngQuality 0 - 9
     * @return static
     */
    public function setPngQuality(int $pngQuality): static
    {
        $this->pngQuality = $pngQuality;
        return $this;
    }

    /**
     * Whether the BMP should be compressed with run-length encoding (RLE), or not.
     * @param bool $bmpCompressed
     * @return static
     */
    public function setBmpCompressed(bool $bmpCompressed): static
    {
        $this->bmpCompressed = $bmpCompressed;
        return $this;
    }

    /**
     * 从图片文件创建图片资源，如果处理gif格式的图片，该图片处理完后将无法动起来
     * @param string $imageFile
     * @return static
     */
    public function createImFromFile(string $imageFile): static
    {
        if (isImage($imageFile)) {
            $this->imageFile = $imageFile;
            $info = getimagesize($imageFile);
            $this->width = $info[0];
            $this->height = $info[1];
            $this->type = $info[2];
            $this->im = match ($this->type) {
                1 => imagecreatefromgif($imageFile),
                2 => imagecreatefromjpeg($imageFile),
                3 => imagecreatefrompng($imageFile),
                6 => imagecreatefrombmp($imageFile),
                18 => imagecreatefromwebp($imageFile),
                default => false
            };
        }
        return $this;
    }

    /**
     * 创建图片资源
     * @param int $width 宽度
     * @param int $height 高度
     * @return static
     */
    public function createIm(int $width, int $height): static
    {
        $this->width = $width;
        $this->height = $height;
        $this->im = imagecreatetruecolor($width, $height);
        return $this;
    }

    /**
     * 保存资源到图片
     * @param bool $replaceImage 是否替换原图片
     * @return bool|string
     */
    public function saveImage(bool $replaceImage = false): bool|string
    {
        if (empty($this->im)) {
            return false;
        }
        $result = false;
        if (!$replaceImage) {
            $filename = '';
            $pathDir = '/upload/' . date('Ymd');
            $dir = PUBLIC_DIR . $pathDir;
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    return false;
                }
            }
            switch ($this->type) {
                case 1:
                    $filename = getRandomString() . '.gif';
                    $result = imagegif($this->im, $dir . '/' . $filename);
                    break;
                case 2:
                    $filename = getRandomString() . '.jpg';
                    $result = imagejpeg($this->im, $dir . '/' . $filename, $this->jpgQuality);
                    break;
                case 3:
                    $filename = getRandomString() . '.png';
                    $result = imagepng($this->im, $dir . '/' . $filename, $this->pngQuality, PNG_ALL_FILTERS);
                    break;
                case 6:
                    $filename = getRandomString() . '.bmp';
                    $result = imagebmp($this->im, $dir . '/' . $filename, $this->bmpCompressed);
                    break;
                case 18:
                    $filename = getRandomString() . '.webp';
                    $result = imagewebp($this->im, $dir . '/' . $filename, $this->webpQuality);
                    break;
            }
            if ($result) {
                return $pathDir . '/' . $filename;
            }
        } else {
            if (!empty($this->imageFile)) {
                switch ($this->type) {
                    case 1:
                        $result = imagegif($this->im, $this->imageFile);
                        break;
                    case 2:
                        $result = imagejpeg($this->im, $this->imageFile, $this->jpgQuality);
                        break;
                    case 3:
                        $result = imagepng($this->im, $this->imageFile, $this->pngQuality, PNG_ALL_FILTERS);
                        break;
                    case 6:
                        $result = imagebmp($this->im, $this->imageFile, $this->bmpCompressed);
                        break;
                    case 18:
                        $result = imagewebp($this->im, $this->imageFile, $this->webpQuality);
                        break;
                }
                if ($result) {
                    return str_ireplace(PUBLIC_DIR, '', $this->imageFile);
                }
            }
        }
        return false;
    }

    /**
     * 缩放图片
     * @param int $width 缩放到指定宽度
     * @param int $height 缩放到指定高度
     * @param bool $replaceImage 是否替换原图片
     * @return string|bool
     */
    public function zoom(int $width, int $height, bool $replaceImage = false): bool|string
    {
        if (empty($this->im)) {
            return false;
        }
        $dstIm = imagecreatetruecolor($width, $height);
        $srcIm = $this->im;
        $dstX = 0;
        $dstY = 0;
        $dstWidth = $width;
        $dstHeight = $height;
        if ($this->width < $width) {
            $dstX = (int)(($width - $this->width) / 2);
            $dstWidth = $this->width;
        }
        if ($this->height < $height) {
            $dstY = (int)(($height - $this->height) / 2);
            $dstHeight = $this->height;
        }
        imagefill($dstIm, 0, 0, imagecolorallocate($dstIm, 255, 255, 255));
        if (imagecopyresampled($dstIm, $srcIm, $dstX, $dstY, 0, 0, $dstWidth, $dstHeight, $this->width, $this->height)) {
            $this->im = $dstIm;
            return $this->saveImage($replaceImage);
        }
        return false;
    }

    /**
     * 按照图片宽度等比缩放
     * @param int $width 缩放到指定宽度
     * @param bool $replaceImage 是否替换原图片
     * @return bool|string
     */
    public function zoomWidth(int $width, bool $replaceImage = false): bool|string
    {
        $bili = $width / $this->width;
        $height = (int)($bili * $this->height);
        return $this->zoom($width, $height, $replaceImage);
    }

    /**
     * 按照图片高度等比缩放
     * @param int $height 缩放到指定高度
     * @param bool $replaceImage 是否替换原图片
     * @return bool|string
     */
    public function zoomHeight(int $height, bool $replaceImage = false): bool|string
    {
        $bili = $height / $this->height;
        $width = (int)($bili * $this->width);
        return $this->zoom($width, $height, $replaceImage);
    }

    /**
     * 截取一部分图像
     * @param int $width 截取宽度
     * @param int $height 截取高度
     * @param int $srcX 截取开始x坐标
     * @param int $srcY 截取开始y坐标
     * @param bool $replaceImage 是否替换原图片
     * @return string|boolean
     */
    public function cut(int $width, int $height, int $srcX = 0, int $srcY = 0, bool $replaceImage = false): bool|string
    {
        if (empty($this->im)) {
            return false;
        }
        if ($width > ($this->width + $srcX)) {
            $width = $this->width;
        }
        if ($height > ($this->height + $srcY)) {
            $height = $this->height;
        }
        $dstIm = imagecreatetruecolor($width, $height);
        $srcIm = $this->im;
        if (imagecopy($dstIm, $srcIm, 0, 0, $srcX, $srcY, $width, $height)) {
            $this->im = $dstIm;
            return $this->saveImage($replaceImage);
        }
        return false;
    }

    /**
     * 设置水印文字
     * @param string $text 水印文字
     * @param int $font 文字大小
     * @param int|null $angle 旋转角度
     * @param int|null $startX 水印开始x坐标
     * @param int|null $startY 水印开始y坐标
     * @param array $rgba 颜色值，r g b a
     * @param bool $replaceImage 是否替换原图片
     * @return string|bool
     */
    public function water(string $text, int $font = 13, int $angle = null, int $startX = null, int $startY = null, array $rgba = [], bool $replaceImage = false): bool|string
    {
        if (empty($this->im)) {
            return false;
        }
        $fontFile = LIBRARY_DIR . '/font/noto-sans-sc.otf';
        if (empty($rgba)) {
            $imagecolorallocateId = imagecolorallocatealpha($this->im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 100));
        } else {
            $imagecolorallocateId = imagecolorallocatealpha($this->im, $rgba[0] ?? mt_rand(0, 255), $rgba[1] ?? mt_rand(0, 255), $rgba[2] ?? mt_rand(0, 255), $rgba[3] ?? mt_rand(0, 100));
        }
        if (is_null($angle)) {
            $angle = mt_rand(0, 360);
        }
        if (empty($startX) || empty($startY)) {
            $bbox = imagettfbbox($font, $angle, $fontFile, $text);
            if (empty($startX)) {
                $width = abs($bbox[0]) + abs($bbox[2]);
                $startX = (int)(($this->width - $width) / 2);
            }
            if (empty($startY)) {
                $height = abs($bbox[1]) + abs($bbox[5]);
                $startY = (int)(($this->height - $height) / 2);
            }
        }
        if (false !== imagefttext($this->im, $font, $angle, $startX, $startY, $imagecolorallocateId, $fontFile, $text)) {
            return $this->saveImage($replaceImage);
        }
        return false;
    }
}