<?php

namespace library\mysmarty;

/**
 * 文件下载
 */
class Download extends Container
{
    private string $downloadUrl = '';
    private string $fileExtension = '';
    private string $saveDir = '';
    private string $saveFilename = '';
    // 期望的响应类型
    private string $contentType = '';
    private int $timeOut = 60;

    /**
     * 获取下载地址
     * @return string
     */
    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }

    /**
     * 获取保存文件后缀
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * 获取保存文件夹
     * @return string
     */
    public function getSaveDir(): string
    {
        return $this->saveDir;
    }

    /**
     * 获取保存文件名
     * @return string
     */
    public function getSaveFilename(): string
    {
        return $this->saveFilename;
    }

    /**
     * 获取超时时间
     * @return int
     */
    public function getTimeOut(): int
    {
        return $this->timeOut;
    }

    /**
     * 设置下载链接
     * @param string $downloadUrl
     * @return static
     */
    public function setDownloadUrl(string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;
        return $this;
    }

    /**
     * 设置下载文件的后缀，不包括 .
     * @param string $fileExtension
     * @return static
     */
    public function setFileExtension(string $fileExtension): static
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }

    /**
     * 设置下载文件的保存目录
     * @param string $saveDir
     * @return static
     */
    public function setSaveDir(string $saveDir): static
    {
        $this->saveDir = $saveDir;
        return $this;
    }

    /**
     * 设置保存的文件名，包含文件名后缀
     * @param string $saveFilename
     * @return static
     */
    public function setSaveFilename(string $saveFilename): static
    {
        $this->saveFilename = $saveFilename;
        return $this;
    }

    /**
     * 获取期望的响应类型
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * 设置期望的详情类型，支持部分匹配
     * @param string $contentType 例如：image\image/png
     * @return $this
     */
    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * 设置下载超时时间
     * @param int $timeOut 单位，秒
     * @return static
     */
    public function setTimeOut(int $timeOut): static
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    /**
     * 开始下载文件
     * @return bool|string 下载成功返回保存的文件名
     */
    public function download(): bool|string
    {
        if (0 !== stripos($this->downloadUrl, "http")) {
            return false;
        }
        $fileData = Query::getInstance()
            ->setPcUserAgent()
            ->setUrl($this->downloadUrl)
            ->setTimeOut($this->timeOut)
            ->request();
        if (empty($fileData)) {
            return false;
        }
        $curlInfo = Query::getInstance()->getCurlInfo();
        // 验证响应类型
        if (!empty($this->contentType) && false === str_contains($curlInfo['content_type'], $this->contentType)) {
            return false;
        }
        if (empty($this->saveDir)) {
            $this->saveDir = PUBLIC_DIR . '/upload/' . date('Ymd');
        }
        // 创建文件夹
        if (!createDir($this->saveDir)) {
            return false;
        }
        // 设置文件后缀，从响应类型
        if (empty($this->fileExtension)) {
            if (!empty($curlInfo['content_type'])) {
                $this->fileExtension = $this->getExtensionFromMime($curlInfo['content_type']);
            }
        }
        // 设置文件后缀，从下载链接
        if (empty($this->fileExtension)) {
            $parseArr = parse_url($this->downloadUrl);
            if (!empty($parseArr['path'])) {
                $this->fileExtension = pathinfo($parseArr['path'], PATHINFO_EXTENSION);
            }
        }
        // 保存文件名称
        if (empty($this->saveFilename)) {
            $this->saveFilename = md5(time() . rand(1000, 9999) . $this->downloadUrl);
        }
        if (!empty($this->fileExtension)) {
            $this->saveFilename .= '.' . $this->fileExtension;
        }
        $filename = rtrim($this->saveDir, '/') . '/' . $this->saveFilename;
        $this->initVar();
        if (file_put_contents($filename, $fileData)) {
            return str_ireplace(PUBLIC_DIR, '', $filename);
        }
        return false;
    }

    /**
     * 根据MIME类型获取文件扩展名
     * @param string $contentType MIME类型
     * @return string 文件扩展名
     */
    private function getExtensionFromMime(string $contentType): string
    {
        if (empty($contentType)) {
            return '';
        }
        // 移除分号后面的参数（如 charset）
        if (str_contains($contentType, ';')) {
            $contentType = strstr($contentType, ';', true);
        }
        $contentType = trim(strtolower($contentType));

        // 图片 MIME 类型到扩展名的映射表
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',      // 旧式 JPEG
            'image/png' => 'png',
            'image/x-png' => 'png',      // 旧式 PNG
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',      // Windows BMP
            'image/tiff' => 'tif',      // 或 tiff
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/avif' => 'avif',
        ];

        return $mimeToExt[$contentType] ?? '';
    }

    /**
     * 初始化变量
     */
    public function initVar(): void
    {
        $this->downloadUrl = '';
        $this->fileExtension = '';
        $this->saveDir = '';
        $this->saveFilename = '';
        $this->timeOut = 60;
        $this->contentType = '';
    }
}