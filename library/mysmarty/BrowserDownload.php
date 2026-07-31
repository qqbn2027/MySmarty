<?php

namespace library\mysmarty;

use Exception;

/**
 * 浏览器下载
 */
class BrowserDownload extends Container
{
    // 文件数据
    private string $data = '';
    // 文件类型
    private string $mimeType = '';
    // 当前文件
    private string $file = '';
    // 响应过期时间
    private int $expire = 360;
    // 下载文件名
    private string $downloadFileName = '';
    // 内存限制，例如：512M
    private string $memoryLimit = '';
    // 超时设置
    private int $timeLimit = -1;

    /**
     * 设置文件数据
     * @param string $data
     * @return static
     */
    public function setData(string $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置文件所在位置
     * @param string $file
     * @return static
     */
    public function setFile(string $file): static
    {
        if (!file_exists($file)) {
            error('文件不存在');
        }
        $this->mimeType = mime_content_type($file);
        $this->downloadFileName = pathinfo($file, PATHINFO_BASENAME);
        $this->file = $file;
        return $this;
    }

    /**
     * 设置文件类型
     * @param string $mimeType
     * @return static
     */
    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * 设置响应过期时间
     * @param int $expire 单位：秒
     * @return static
     */
    public function setExpire(int $expire): static
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * 输出文件
     * @param string $downloadFileName 文件下载名
     */
    public function output(string $downloadFileName = ''): void
    {
        if (empty($downloadFileName)) {
            if (!empty($this->downloadFileName)) {
                $downloadFileName = $this->downloadFileName;
            } else {
                $downloadFileName = md5(time() . mt_rand(1000, 9999));
            }
        }
        if (!empty($this->memoryLimit)) {
            ini_set('memory_limit', $this->memoryLimit);
        }
        if ($this->timeLimit >= 0) {
            set_time_limit($this->timeLimit);
        }
        if (!empty($this->file)) {
            // 大文件下载
            $this->streamDownloadFile($this->file, $downloadFileName);
        } else if (!empty($this->data)) {
            header_remove();
            header('Pragma: public');
            header('Content-Type: ' . ($this->mimeType ?? 'application/octet-stream'));
            header('Cache-control: max-age=' . $this->expire);
            header('Content-Disposition: attachment; filename="' . rawurlencode($downloadFileName) . '"');
            header('Content-Length: ' . strlen($this->data));
            header('Content-Transfer-Encoding: binary');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->expire) . ' GMT');
            echo $this->data;
            exit();
        } else {
            error('下载文件为空');
        }
    }

    /**
     * 支持断点续传的大文件流式下载函数
     * @param string $filePath 要下载的文件路径
     * @param string $fileName 下载时显示的文件名（可选，默认使用原文件名）
     * @param int $chunkSize 每次读取的字节数，默认8192字节（8KB）
     * @return void
     */
    private function streamDownloadFile(string $filePath, string $fileName = '', int $chunkSize = 8192): void
    {
        if (empty($fileName)) {
            $fileName = basename($filePath);
        }
        $fileSize = filesize($filePath); // 获取文件总大小
        $offset = 0; // 默认从文件开头读取
        $length = $fileSize; // 默认读取整个文件

        ob_end_clean();
        ob_implicit_flush(true);
        set_time_limit(0);

        try {
            // 处理断点续传请求（解析HTTP_RANGE）
            if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches)) {
                $offset = intval($matches[1]); // 起始偏移量
                // 处理结束位置（如果未指定则读取到文件末尾）
                $length = isset($matches[2]) ? (intval($matches[2]) - $offset + 1) : ($fileSize - $offset);

                // 验证偏移量合法性
                if ($offset >= $fileSize || $offset < 0) {
                    http_response_code(416); // 请求的范围不满足
                    header('Content-Range: bytes */' . $fileSize);
                    exit();
                }

                // 设置断点续传响应头
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes ' . $offset . '-' . ($offset + $length - 1) . '/' . $fileSize);
            } else {
                // 普通下载响应头
                header('HTTP/1.1 200 OK');
            }

            // 通用下载响应头
            header('Content-Type: ' . ($this->mimeType ?? 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $length); // 输出实际要传输的字节数

            // 打开文件并定位到指定偏移量
            $fileHandle = fopen($filePath, 'rb');
            if (!$fileHandle) {
                throw new Exception("无法打开文件");
            }
            fseek($fileHandle, $offset); // 定位到断点续传的起始位置

            // 流式读取并输出文件（分段处理）
            $remaining = $length;
            while ($remaining > 0 && !feof($fileHandle)) {
                // 每次读取不超过chunkSize，也不超过剩余未读取的字节数
                $readSize = min($chunkSize, $remaining);
                $buffer = fread($fileHandle, $readSize);

                if ($buffer === false) {
                    throw new Exception("读取文件内容失败");
                }

                echo $buffer;
                flush(); // 立即输出到浏览器
                unset($buffer);

                $remaining -= $readSize;
            }

            // 清理资源
            fclose($fileHandle);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo "下载失败：" . $e->getMessage();
            if (isset($fileHandle) && is_resource($fileHandle)) {
                fclose($fileHandle);
            }
            exit();
        }
    }
}