<?php

namespace library\mysmarty;

/**
 * 文件上传类
 */
class Upload extends Container
{
    private array $limitType = [];
    private int $limitSize = 0;
    private array $limitExt = [];

    /**
     * 移动文件
     * @param string $name 表单提交文件名字段
     * @return boolean|array|string 成功返回路径，失败返回false
     */
    public function move(string $name): bool|array|string
    {
        $files = $_FILES[$name] ?? '';
        if (empty($files)) {
            return false;
        }
        if (is_array($files['name'])) {
            $result = [];
            $len = count($files['name']);
            for ($i = 0; $i < $len; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $result[] = $this->myMove($file);
            }
            return $result;
        }
        return $this->myMove($files);
    }

    /**
     * 设置上传文件类型
     * @param array|string $type 如：['image/png']
     * @return static
     */
    public function setLimitType(array|string $type): static
    {
        if (!is_array($type)) {
            $type = explode(',', $type);
        }
        $this->limitType = $type;
        return $this;
    }

    /**
     * 设置上传文件大小
     * @param int $size 字节， 1kb = 1024b
     * @return static
     */
    public function setLimitSize(int $size): static
    {
        $this->limitSize = $size;
        return $this;
    }

    /**
     * 设置上传文件后缀，不包括.
     * @param array|string $ext 如：png
     * @return static
     */
    public function setLimitExt(array|string $ext): static
    {
        if (!is_array($ext)) {
            $ext = explode(',', $ext);
        }
        $this->limitExt = $ext;
        return $this;
    }

    /**
     * @param array $file
     * @return bool|string
     */
    private function myMove(array $file): bool|string
    {
        if ($file['error'] !== 0) {
            return false;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        if (!empty($this->limitType) && !in_array($file['type'], $this->limitType, true)) {
            return false;
        }
        // 检测图片文件的合法性
        if (str_starts_with($file['type'], 'image')) {
            if (!isImage($file['tmp_name'])) {
                return false;
            }
        }
        if (!empty($this->limitSize) && $file['size'] > $this->limitSize) {
            return false;
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (empty($ext)) {
            return false;
        }
        if (!empty($this->limitExt) && !in_array($ext, $this->limitExt, true)) {
            return false;
        }
        $pathDir = '/upload/' . date('Ymd');
        $dir = PUBLIC_DIR . $pathDir;
        if (!createDir($dir)) {
            return false;
        }
        $filename = getRandomString() . '.' . $ext;
        $this->initVariable();
        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            return $pathDir . '/' . $filename;
        }
        return false;
    }

    /**
     * 初始化变量
     */
    private function initVariable(): void
    {
        $this->limitExt = [];
        $this->limitSize = 0;
        $this->limitType = [];
    }
}
