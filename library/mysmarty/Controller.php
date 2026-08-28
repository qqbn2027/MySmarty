<?php

namespace library\mysmarty;

class Controller
{
    // 是否开启缓存
    protected bool $caching = false;
    // 是否格式化页面
    protected bool $formatHtml = true;
    // 模板对象
    private Template $mySmarty;

    public function __construct()
    {
        $this->initSmarty();
    }

    /**
     * 初始化smarty
     */
    final protected function initSmarty(): void
    {
        // 初始化变量
        $this->mySmarty = Template::getInstance();
        // 模板文件目录
        $templateDir = APP_DIR . '/view/';
        $this->mySmarty->setTemplateDir($templateDir);
        // 编译文件目录
        $compileDir = RUNTIME_DIR . '/templates_c/' . strtolower(str_ireplace('\\', '/', Start::$controller));
        $this->mySmarty->setCompileDir($compileDir);
        // 缓存配置
        $caching = $this->caching && \config\Template::CACHE;
        $this->mySmarty->setCaching($caching);
        // 格式化页面设置
        $formatHtml = $this->formatHtml && \config\Template::FORMAT_TO_LINE;
        $this->mySmarty->setFormatHtml($formatHtml);
    }

    /**
     * 显示模板
     * @param string $template
     */
    final protected function display(string $template = ''): void
    {
        if (empty($template)) {
            $template = $this->getMyTemplate();
        } else {
            if (!str_contains($template, '/')) {
                $tmp = toDivideName(str_ireplace('\\', '/', Start::$controller), '/') . '/' . $template;
                // 判断是否包含模板后缀
                if (!str_contains($tmp, '.')) {
                    $tmp .= '.html';
                }
                if (file_exists(APP_DIR . '/view/' . $tmp)) {
                    $template = $tmp;
                }
            } else {
                // 判断是否包含模板后缀
                if (!str_contains($template, '.')) {
                    $template .= '.html';
                }
            }
        }
        $this->mySmarty->display($template);
    }

    /**
     * 返回自动生成的模板文件
     * @return string
     */
    final protected function getMyTemplate(): string
    {
        return toDivideName(str_ireplace('\\', '/', Start::$controller), '/') . '/' . toDivideName(Start::$action) . '.html';
    }

    /**
     * 分配变量
     * @param string $key 变量名称
     * @param mixed $value 变量值
     */
    final protected function assign(string $key, mixed $value): void
    {
        $this->mySmarty->assign($key, $value);
    }

    /**
     * 删除模板缓存文件目录
     * @return bool
     */
    final protected function clearTemplateDirCache(): bool
    {
        return $this->mySmarty->clearTemplateDirCache();
    }

    /**
     * 清空内容缓存，包括配置、路由缓存
     * @return bool
     */
    final protected function clearCache(): bool
    {
        return $this->mySmarty->clearCache();
    }

    /**
     * 当前是否开启了缓存
     * @return bool
     */
    final protected function isCaching(): bool
    {
        return $this->caching;
    }

    /**
     * 设置缓存
     * @param bool $caching
     */
    final protected function setCaching(bool $caching): void
    {
        $this->caching = $caching;
    }

    /**
     * 成功输出信息
     * @param string $msg 信息
     * @param string $url 跳转链接
     * @return void
     */
    final protected function success(string $msg, string $url = ''): void
    {
        if (empty($url)) {
            $redirectUrl = getServerValue('HTTP_REFERER');
            if (str_contains($redirectUrl, getAbsoluteUrl())) {
                $url = $redirectUrl;
            } else {
                $url = 'javascript:history.go(-1);';
            }
        }
        tip($msg, $url, formatUrl: true, icon: 'success');
    }

    /**
     * 失败输出信息
     * @param string $msg 信息
     * @param string $url 跳转链接
     * @return void
     */
    final protected function error(string $msg, string $url = ''): void
    {
        if (empty($url)) {
            $url = 'javascript:history.go(-1);';
        }
        tip($msg, $url, formatUrl: true);
    }
}
