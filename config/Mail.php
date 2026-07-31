<?php

namespace config;

/**
 * 发送电子邮件设置
 */
class Mail
{
    // 发送服务器
    public const HOSTNAME = '';
    // 端口
    public const PORT = 465;
    // 是否使用SSL
    public const USESSL = true;
    // 发送邮箱
    public const SENDEMAILUSER = '';
    // 发送邮箱密码/授权码
    public const SENDEMAILPASS = '';
    // 发送邮箱显示名称
    public const SHOWEMAIL = '';
    // 连接超时，单位秒
    public const TIMEOUT = 5;
    // 读取超时，单位秒
    public const READTIMEOUT = 3;
}