<?php

namespace config;

// MySmarty模板配置
class Template
{
    // 检查模板文件是否修改过，线上环境最好设置为false
    public const COMPILE_CHECK = true;
    // 强制编译，线上环境最好设置为false。开启缓存时，必须设置为false
    public const FORCE_COMPILE = true;
    // 是否开启缓存
    public const CACHE = false;
    // 缓存时间,单位秒
    public const CACHE_LIFE_TIME = 3600;
    // 输出过滤器,格式化页面，将源代码输出到一行，节省页面大小，false 不格式化，true 格式化（开启后，代码中尽量不要有注释符号，js,css代码要规范，用分号 `;` 隔开每一行）
    public const FORMAT_TO_LINE = false;
}