<?php

namespace config;
/**
 * 应用配置
 */
class App
{
    // 调试，false 关闭，true 开启
    public const DEBUG = true;
    // 加密 key，定义之后不要修改，否则会导致之前加密的数据无法解密
    public const ENCRYPTION_KEY = '';
    // 默认时区
    public const DEFAULT_TIMEZONE = 'Asia/Shanghai';
    // 部署路径，如果部署在二级（或多级）目录下，请设置此目录名称
    public const DEPLOY_PATH = '';
    // 应用初始化执行方法
    public const APP_INIT = '';
    // 应用网站域名，例如：auth.ggdoc.cn
    public const APP_DOMAIN = '';
}
