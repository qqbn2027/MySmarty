<?php

namespace library\mysmarty;
/**
 * 模板解析
 */
class Template extends Container
{
    // 模板目录
    private string $templateDir;
    // 编译目录
    private string $compileDir;
    // 存储分配变量的数组
    private array $data = [];
    // 左分隔符
    private string $leftDelimiter = '{';
    // 右分隔符
    private string $rightDelimiter = '}';
    // 函数合法开始标签
    private array $funStartRegTags = ['foreach', 'if', 'elseif', 'else', 'php', 'config_load', 'url', 'href', 'captcha', 'css', 'js'];
    // 函数合法结束标签
    private array $funEndRegTags = ['foreach', 'if', 'php'];
    // 替换标签
    private array $repRegTags = ['literal'];
    // 是否开启编译检查
    private bool $compileCheck = true;
    // 是否开启强制编译
    private bool $forceCompile = true;
    // 是否开启缓存
    private bool $caching = false;
    // 是否格式化页面
    private bool $formatHtml = true;
    // 缓存key
    private string $cachingKey = '';

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->compileCheck = \config\Template::COMPILE_CHECK;
        $this->forceCompile = \config\Template::FORCE_COMPILE;
        $this->caching = \config\Template::CACHE;
        $this->cachingKey = getCacheKey();
    }

    /**
     * 设置模板目录
     * @param string $templateDir 模板目录
     */
    public function setTemplateDir(string $templateDir): void
    {
        if (!createDir($templateDir)) {
            error('模板目录不存在或无法创建');
        }
        $this->templateDir = realpath($templateDir);
    }

    /**
     * 获取模板目录
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * 设置编译目录
     * @param string $compileDir 编译目录
     */
    public function setCompileDir(string $compileDir): void
    {
        if (!createDir($compileDir)) {
            error('编译目录不存在或无法创建');
        }
        $this->compileDir = realpath($compileDir);
    }

    /**
     * 获取编译目录
     * @return string
     */
    public function getCompileDir(): string
    {
        return $this->compileDir;
    }

    /**
     * 分配变量值
     * @param string $key 变量key
     * @param mixed $value 变量值
     */
    public function assign(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * 显示模板
     * @param string $template 模板文件
     */
    public function display(string $template): void
    {
        // 编译文件key
        $compileKey = md5($template);
        // 编译文件
        $compileFile = $this->compileDir . '/' . $compileKey . '.php';
        if (!file_exists($compileFile) || $this->forceCompile || ($this->compileCheck && (filemtime($compileFile) < filemtime($this->templateDir . '/' . $template)))) {
            // 强制编译
            $templateData = $this->compile($template);
            file_put_contents($compileFile, $templateData);
        }
        ob_start();
        extract($this->data);
        echoHtmlHeader();
        require_once $compileFile;
        $content = ob_get_contents();
        // 是否格式化为一行
        if ($this->formatHtml) {
            $content = formatHtml($content);
        }
        if (200 === http_response_code() && $this->caching) {
            setCache($this->cachingKey, $content, \config\Template::CACHE_LIFE_TIME);
        }
        ob_end_clean();
        echo $content;
        exit();
    }

    /**
     * 显示缓存
     */
    public function showCache(): void
    {
        if (!$this->caching) {
            return;
        }
        $cacheData = getCache($this->cachingKey, false);
        if (false !== $cacheData) {
            echoHtmlHeader();
            echo $cacheData;
            exit();
        }
    }

    /**
     * 模板文件编译
     * @param string $template 模板文件
     * @return string
     */
    private function compile(string $template): string
    {
        $templateData = file_get_contents($this->templateDir . '/' . ltrim($template, '/'));
        if ($templateData === false) {
            error('模板文件不存在');
        }
        return $this->compileStr($templateData);
    }

    /**
     * 替换block标签
     * @param string $templateData 模板数据
     * @return string
     */
    private function getBlockData(string $templateData): string
    {
        $blockData = [];
        $blockPosition = [];
        $replaceData = [];
        $leftBlockReg = '/' . $this->leftDelimiter . 'block[\s]+name=([a-z0-9_]+)' . $this->rightDelimiter . '/iUs';
        if (preg_match_all($leftBlockReg, $templateData, $leftMat, PREG_OFFSET_CAPTURE)) {
            foreach ($leftMat[1] as $k => $item) {
                $name = 'block_' . md5($item[0]);
                $blockData[] = $name;
                $blockPosition[] = $item[1];
                $replaceData[] = $leftMat[0][$k][0];
            }
        }
        $rightBlockReg = '~' . $this->leftDelimiter . '/block' . $this->rightDelimiter . '~iU';
        if (preg_match_all($rightBlockReg, $templateData, $rightMat, PREG_OFFSET_CAPTURE)) {
            $len = count($blockData);
            foreach ($rightMat[0] as $v) {
                $position = $v[1];
                for ($i = $len - 1; $i >= 0; $i--) {
                    if (!isset($blockPosition[$i])) {
                        continue;
                    }
                    if ($position > $blockPosition[$i]) {
                        // 替换左标签
                        $templateData = str_ireplace($replaceData[$i], $blockData[$i], $templateData);
                        // 替换右标签
                        $name = $blockData[$i];
                        $templateData = preg_replace('~' . $v[0] . '~i', $name, $templateData, 1);
                        unset($blockPosition[$i]);
                        unset($replaceData[$i]);
                        unset($blockData[$i]);
                        break;
                    }
                }
            }
        }
        return $templateData ?: '';
    }

    /**
     * 编译模板字符串
     * @param string $templateData
     * @return string
     */
    private function compileStr(string $templateData): string
    {
        // 处理 include 标签
        $includeReg = '/' . $this->leftDelimiter . 'include[\s]*([^' . $this->rightDelimiter . ']+)?[\s]*' . $this->rightDelimiter . '/is';
        $templateData = preg_replace_callback($includeReg, function ($mat) {
            $params = $this->paramToArr($mat[1], true);
            return $this->compile($params['file']);
        }, $templateData);
        if (preg_match('/' . $this->leftDelimiter . 'extends[\s]+file=[\'"]([^\'"]+)[\'"][\s]*' . $this->rightDelimiter . '/iU', $templateData, $mat)) {
            // 解析模板继承表达式
            $parentTemplateData = file_get_contents($this->templateDir . '/' . ltrim($mat[1], '/'));
            if (false === $parentTemplateData) {
                error('父模板文件不存在');
            }
            // 子模板
            $templateData = $this->getBlockData($templateData);
            // 将子模版中的标签数据解析到数组
            $blockData = [];
            $blockReg = '~(block_[\w]{32})(.*)\1~iUs';
            while (true) {
                if (preg_match_all($blockReg, $templateData, $matchs)) {
                    foreach ($matchs[1] as $k => $v) {
                        $blockData[$v] = $matchs[2][$k];
                        $templateData = str_ireplace($v, '', $templateData);
                    }
                } else {
                    break;
                }
            }
            // 父模板
            $parentTemplateData = $this->getBlockData($parentTemplateData);
            // 用子模版中的数据来替换模板中的数据
            foreach ($blockData as $k => $v) {
                $parentTemplateData = preg_replace('~(' . $k . ')(.*)\1~iUs', '\1' . $v . '\1', $parentTemplateData);
            }
            return $this->compileStr($parentTemplateData);
        }
        // 去掉无用的block标签
        $templateData = preg_replace('~block_[\w]{32}~iU', '', $templateData);
        // 解析普通表达式
        // 替换标签
        $repData = [];
        $repRegStr = implode('|', $this->repRegTags);
        $templateData = preg_replace_callback('~' . $this->leftDelimiter . '(' . $repRegStr . ')[\s]*([^' . $this->rightDelimiter . ']+)?[\s]*' . $this->rightDelimiter . '(.*)' . $this->leftDelimiter . '/\1' . $this->rightDelimiter . '~iUs', function ($matchs) use (&$repData) {
            $funCode = '';
            if ($matchs[1] == 'literal') {
                $key = 'literal_' . md5('literal' . time() . mt_rand(1000, 9999));
                $repData[$key] = $matchs[3];
                $funCode .= $key;
            }
            return $funCode;
        }, $templateData);
        // 处理foreach等函数的开始标签
        $funStartRegStr = implode('|', $this->funStartRegTags);
        $funStartReg = '/' . $this->leftDelimiter . '(' . $funStartRegStr . ')[\s]*([^' . $this->rightDelimiter . ']+)?[\s]*' . $this->rightDelimiter . '/is';
        $templateData = preg_replace_callback($funStartReg, function ($matchs) {
            $funCode = '';
            switch ($matchs[1]) {
                case 'foreach':
                    // foreach 标签处理
                    // 先判断是不是php语法
                    if (preg_match('/[\s]+as[\s]+/iU', $matchs[2])) {
                        // 使用的是php语法
                        $funCode .= '<?php foreach(' . $matchs[2] . ') {?>';
                    } else {
                        // 使用的不是php语法
                        $paramData = $this->paramToArr($matchs[2]);
                        $from = $paramData['from'];
                        $item = '$' . ltrim($paramData['item'], '$');
                        $key = '$' . ltrim(($paramData['key'] ?? 'index'), '$');
                        $funCode .= '<?php foreach(' . $from . ' as ' . $key . ' => ' . $item . ') {?>';
                    }
                    break;
                case 'if':
                    $funCode .= '<?php if(' . $matchs[2] . '){?>';
                    break;
                case 'elseif':
                    $funCode .= '<?php } else if(' . $matchs[2] . '){?>';
                    break;
                case 'else':
                    $funCode .= '<?php } else {?>';
                    break;
                case 'php':
                    $funCode .= '<?php' . PHP_EOL;
                    break;
                case 'url':
                    $funCode .= '<?php echo getAbsoluteUrl();?>';
                    break;
                case 'href':
                    $funCode .= '<?php echo getHref();?>';
                    break;
                case 'captcha':
                    $paramData = $this->paramToArr($matchs[2], true);
                    $src = getAbsoluteUrl() . '/' . trim($paramData['src'], '/');
                    $funCode .= '<img src="' . $src . '" alt="验证码" style="cursor: pointer;" title="点击图片切换验证码" onclick="this.src=\'' . $src . '?i=\'+Math.random()+\'\'" />';
                    break;
            }
            return $funCode;
        }, $templateData);
        // 处理foreach等函数的结束标签
        $funEndRegStr = implode('|', $this->funEndRegTags);
        $funEndReg = '~' . $this->leftDelimiter . '/(' . $funEndRegStr . ')' . $this->rightDelimiter . '~iU';
        $templateData = preg_replace_callback($funEndReg, function ($matchs) {
            return match ($matchs[1]) {
                'foreach', 'if' => '<?php }?>',
                'php' => PHP_EOL . '?>',
            };
        }, $templateData);
        // 函数输出
        $reg = '~' . $this->leftDelimiter . '[:]?([a-z0-9_]+\([^)]*\))' . $this->rightDelimiter . '~i';
        $templateData = preg_replace_callback($reg, function ($matchs) {
            return '<?php echo ' . $matchs[1] . ';?>';
        }, $templateData);
        // 输出变量
        $reg = '/' . $this->leftDelimiter . '(\$[^\s' . $this->rightDelimiter . '|]+)[\s]*(\|[^' . $this->rightDelimiter . ']+)*[\s]*' . $this->rightDelimiter . '/i';
        $templateData = preg_replace_callback($reg, function ($matchs) {
            $len = count($matchs);
            if (2 === $len) {
                return '<?php echo ' . $matchs[1] . ';?>';
            } else if (3 === $len) {
                $formatMethodCode = '';
                $formatMethods = explode('|', $matchs[2]);
                foreach ($formatMethods as $formatMethod) {
                    $formatMethod = trim($formatMethod);
                    if (empty($formatMethod)) {
                        continue;
                    }
                    $formatMethodParams = explode(':', $formatMethod);
                    $paramLen = count($formatMethodParams);
                    $formatMethodParams[0] = trim($formatMethodParams[0]);
                    if (1 == $paramLen) {
                        $formatMethodCode .= '<?php ' . $matchs[1] . ' = call_user_func(\'' . $formatMethodParams[0] . '\', ' . $matchs[1] . ');?>';
                    } else {
                        $paramArr = '[' . $matchs[1];
                        for ($i = 1; $i < $paramLen; $i++) {
                            $paramArr .= ',' . $formatMethodParams[$i];
                        }
                        $paramArr .= ']';
                        $formatMethodCode .= '<?php ' . $matchs[1] . ' = call_user_func_array(\'' . $formatMethodParams[0] . '\',' . $paramArr . ');?>';
                    }
                }
                return $formatMethodCode . '<?php echo ' . $matchs[1] . ';?>';
            }
            return '';
        }, $templateData);
        // 将替换标签的内容替换回来
        if (!empty($repData)) {
            foreach ($repData as $key => $val) {
                $templateData = str_ireplace($key, $val, $templateData);
            }
        }
        // 是否格式化为一行
        if ($this->formatHtml) {
            $templateData = formatHtml($templateData);
        }
        return $templateData;
    }

    /**
     * 获取左分隔符
     * @return string
     */
    public function getLeftDelimiter(): string
    {
        return $this->leftDelimiter;
    }

    /**
     * 设置左分隔符
     * @param string $leftDelimiter 左分隔符
     */
    public function setLeftDelimiter(string $leftDelimiter): void
    {
        $this->leftDelimiter = $leftDelimiter;
    }

    /**
     * 获取右分隔符
     * @return string
     */
    public function getRightDelimiter(): string
    {
        return $this->rightDelimiter;
    }

    /**
     * 设置右分隔符
     * @param string $rightDelimiter 右分隔符
     */
    public function setRightDelimiter(string $rightDelimiter): void
    {
        $this->rightDelimiter = $rightDelimiter;
    }

    /**
     * 将字符串的参数转为数组
     * @param string $param 字符串参数
     * @param bool $trimQm 是否去除字符串上的引号
     * @return array
     */
    private function paramToArr(string $param, bool $trimQm = false): array
    {
        $data = [];
        $param = trim($param);
        $paramArr = preg_split('/[\s]+/', $param);
        foreach ($paramArr as $v) {
            $v = trim($v);
            $vArr = explode('=', $v);
            if (2 === count($vArr)) {
                $val = $vArr[1];
                if ($trimQm) {
                    $val = preg_replace('/[\'"]/', '', $val);
                }
                $data[$vArr[0]] = $val;
            }
        }
        return $data;
    }

    /**
     * 获取是否开启缓存
     * @return bool
     */
    public function getCaching(): bool
    {
        return $this->caching;
    }

    /**
     * 设置缓存是否开启
     * @param bool $caching
     */
    public function setCaching(bool $caching): void
    {
        $this->caching = $caching;
    }

    /**
     * 删除模板缓存文件目录
     * @return bool
     */
    public function clearTemplateDirCache(): bool
    {
        return removeDir(RUNTIME_DIR . '/templates_c');
    }

    /**
     * 清空内容缓存，包括配置、路由缓存
     * @return bool
     */
    public function clearCache(): bool
    {
        return removeDir(RUNTIME_DIR . '/cache');
    }

    /**
     * 获取当前页面是否格式化
     * @return bool
     */
    public function isFormatHtml(): bool
    {
        return $this->formatHtml;
    }

    /**
     * 设置当前页面是否格式化
     * @param bool $formatHtml
     */
    public function setFormatHtml(bool $formatHtml): void
    {
        $this->formatHtml = $formatHtml;
    }
}