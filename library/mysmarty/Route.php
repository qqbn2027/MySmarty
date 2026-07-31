<?php

namespace library\mysmarty;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    // 路由规则
    private string $uri;
    // 路由变量规则
    private array $pattern;
    // 高级别
    const LEVEL_HIGN = 9;
    // 中级别
    const LEVEL_MIDDLE = 5;
    // 低级别
    const LEVEL_LOW = 1;
    // 匹配级别，越大则越靠前
    private int $level;
    // 是否缓存
    private bool $caching;
    // 路由前缀
    private string $prefix;
    // 是否为首页
    private bool $home;

    /**
     * 注解路由
     * 请不要随意调整参数（位置或名称），因为这会影响路由文件生成
     * @param string $uri 路由地址，为空则使用默认路由
     * @param array $pattern 路由变量规则
     * @param int $level 路由匹配级别
     * @param bool $caching 是否缓存
     * @param string $prefix 路由前缀，用{}包围的值，将会从路由配置中读取
     * @param bool $home 是否为主页
     */
    public function __construct(string $uri = '', array $pattern = [], int $level = self::LEVEL_MIDDLE, bool $caching = true, string $prefix = '', bool $home = false)
    {
        $this->uri = $uri;
        $this->pattern = $pattern;
        $this->level = $level;
        $this->caching = $caching;
        $this->prefix = $prefix;
        $this->home = $home;
    }

    /**
     * 获取路由地址
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * 获取变量规则
     * @return array
     */
    public function getPattern(): array
    {
        return $this->pattern;
    }


    /**
     * 获取路由级别
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * 是否缓存
     * @return bool
     */
    public function isCaching(): bool
    {
        return $this->caching;
    }

    /**
     * 获取路由前缀
     * @return string
     */
    public function getPrefix(): string
    {
        $prefix = $this->prefix;
        if (!empty($prefix)) {
            $prefix = preg_replace_callback('/{(\w+)}/i', function ($mat) {
                return config('Route', strtoupper($mat[1]), $mat[1]);
            }, $prefix);
        }
        return $prefix;
    }

    /**
     * 获取当前路由是否为主页
     * @return bool
     */
    public function getHome(): bool
    {
        return $this->home;
    }
}