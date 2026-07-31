# MySmarty 框架技术使用文档

> 本文档基于项目源码整理，全面介绍 MySmarty 框架的目录结构、启动流程、配置、路由、控制器、模板引擎、数据库 ORM、缓存、Session/Cookie、JWT、加密、验证码、文件上传/下载、HTTP 请求、邮件、图像处理、分页、全局函数、CLI 模式与部署等模块。

---

## 目录

1. [框架简介](#1-框架简介)
2. [目录结构](#2-目录结构)
3. [环境要求与安装](#3-环境要求与安装)
4. [启动流程](#4-启动流程)
5. [配置说明](#5-配置说明)
6. [路由系统](#6-路由系统)
7. [控制器（Controller）](#7-控制器controller)
8. [模板引擎（Template）](#8-模板引擎template)
9. [数据库 ORM（Model）](#9-数据库-ormmodel)
10. [缓存（Cache）](#10-缓存cache)
11. [Session 与 Cookie](#11-session-与-cookie)
12. [JWT 认证](#12-jwt-认证)
13. [加密与解密（Encrypt）](#13-加密与解密encrypt)
14. [验证码（Captcha）](#14-验证码captcha)
15. [文件上传（Upload）](#15-文件上传upload)
16. [文件下载（Download）](#16-文件下载download)
17. [HTTP 请求（Query）](#17-http-请求query)
18. [邮件发送（Smtp）](#18-邮件发送smtp)
19. [图像处理（Image）](#19-图像处理image)
20. [分页（Page）](#20-分页page)
21. [全局函数](#21-全局函数)
22. [CLI 命令行模式](#22-cli-命令行模式)
23. [部署建议](#23-部署建议)
24. [附录：常见问题](#24-附录常见问题)

---

## 1. 框架简介

MySmarty 是一个轻量级 PHP MVC 框架，特点如下：

- **PHP 8+ 原生注解路由**：使用 `#[Route]` 注解定义路由，无需手动维护路由表
- **自研模板引擎**：兼容 Smarty 风格语法，支持变量、函数、`foreach`、`if`、`include`、`extends` 继承等
- **链式 ORM**：基于 PDO，支持链式查询、事务、关联、分页、原生 SQL 等
- **多驱动缓存**：内置文件缓存与 Redis 缓存
- **完整工具集**：内置 Session、Cookie、JWT、加密、验证码、上传、下载、HTTP 请求、SMTP 邮件、图像处理等
- **单一实例容器**：通过 `Container::getInstance()` 实现单例，初始化方法 `_initialize()` 自动调用

---

## 2. 目录结构

```
MySmarty/
├── app/                         # 应用业务目录
│   ├── controller/              # 控制器目录
│   │   └── Index.php
│   ├── view/                    # 视图模板目录
│   │   └── index/
│   │       └── index.html
│   └── common.php               # 应用公共函数（可选，需自行创建）
├── config/                      # 配置目录
│   ├── App.php                  # 应用配置
│   ├── Database.php             # 数据库配置
│   ├── Template.php             # 模板引擎配置
│   ├── Session.php              # Session 配置
│   ├── Cookie.php               # Cookie 配置
│   ├── Cors.php                 # 跨域配置
│   ├── Jwt.php                  # JWT 配置
│   └── Mail.php                 # 邮件配置
├── library/                     # 框架核心库
│   ├── enum/                    # 枚举
│   │   ├── Captcha.php
│   │   └── HttpMethod.php
│   ├── font/                    # 字体文件（验证码用）
│   │   └── noto-sans-sc.otf
│   ├── mysmarty/                # 框架核心类
│   │   ├── App.php              # 应用与路由初始化
│   │   ├── Start.php            # 启动类
│   │   ├── Container.php        # 单例容器
│   │   ├── Route.php            # 路由注解
│   │   ├── Controller.php       # 控制器基类
│   │   ├── Template.php         # 模板引擎
│   │   ├── Model.php            # 数据库 ORM
│   │   ├── PdoConnection.php    # PDO 连接
│   │   ├── Cache.php            # 缓存代理
│   │   ├── FileCache.php        # 文件缓存
│   │   ├── RedisCache.php       # Redis 缓存
│   │   ├── Session.php          # Session 操作
│   │   ├── Cookie.php           # Cookie 操作
│   │   ├── Jwt.php              # JWT 编解码
│   │   ├── Encrypt.php          # 加解密
│   │   ├── Captcha.php          # 验证码
│   │   ├── Upload.php           # 文件上传
│   │   ├── Download.php         # 文件下载
│   │   ├── BrowserDownload.php  # 浏览器下载
│   │   ├── Query.php            # HTTP 请求
│   │   ├── Smtp.php             # SMTP 邮件
│   │   ├── Image.php            # 图像处理
│   │   ├── Page.php             # 分页
│   │   └── UserAgent.php        # UA 池
│   ├── tpl/                     # 内置模板
│   │   ├── tip.html             # 提示页
│   │   └── alert.html           # 弹窗页
│   └── function.php             # 全局函数库
├── public/                      # Web 根目录
│   └── index.php                # 应用入口
├── runtime/                     # 运行时目录（自动生成）
│   ├── cache/                   # 文件缓存
│   ├── templates_c/             # 模板编译文件
│   └── route/                   # 路由缓存文件
├── docs/                        # 文档目录
└── mysmarty                     # CLI 入口脚本
```

> **提示**：`runtime/` 目录由框架自动创建，无需手动维护。

---

## 3. 环境要求与安装

### 3.1 环境要求

- PHP **8.0+**（使用了 `enum`、命名参数、`#[Attribute]` 注解、`str_starts_with` 等特性）
- 扩展：`pdo_mysql`、`gd`（验证码与图像处理）、`curl`（HTTP 请求）、`openssl`（JWT/加解密）、`mbstring`、`fileinfo`
- 可选扩展：`redis`（启用 Redis 缓存时）
- 数据库：MySQL 5.7+ / MariaDB 10+

### 3.2 安装

将项目放置到 Web 服务器目录，Web 根指向 `public/`。无需 Composer 即可运行；若引入第三方库，可使用 Composer，框架会自动加载 `vendor/autoload.php`。

### 3.3 入口文件

`public/index.php`：

```php
<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/library/mysmarty/Start.php';

use library\mysmarty\Start;

Start::forward();
```

### 3.4 路由缓存

首次运行时，框架会扫描 `app/controller/` 下所有控制器，生成路由缓存文件：

```
runtime/route/route_<ENCRYPTION_KEY>.php
```

修改控制器或路由注解后，需将 `config/App.php` 的 `DEBUG` 设为 `true` 或手动删除 `runtime/route/` 目录以重新生成。

---

## 4. 启动流程

`Start::forward()` 执行流程：

1. `initCommon()`：
   - 定义常量 `APP_DIR`、`PUBLIC_DIR`、`RUNTIME_DIR`、`LIBRARY_DIR`
   - 注册 `spl_autoload_register` 自动加载（按命名空间映射文件路径）
   - 引入 `library/function.php` 与 `app/common.php`
   - 读取 `config/App.php`，设置时区、错误级别
   - 加载 `vendor/autoload.php`（若存在）
   - 移除 `X-Powered-By` 响应头
   - 执行 `APP_INIT` 配置的初始化函数（若存在）
2. 解析当前 URI（支持 CLI 模式参数与二级目录部署）
3. 通过 `App::getInstance()->getRouteData()` 获取路由表
4. 遍历匹配路由，命中后调用 `runRoute()`
5. `runRoute()`：
   - 提取路由参数
   - 设置 `Start::$controller`、`Start::$action`、`Start::$route`
   - 若开启缓存则先尝试读取页面缓存
   - 调用 `Start::go()` 实例化控制器并执行方法
6. `go()`：根据方法返回值自动以 JSON 或 HTML 形式输出

---

## 5. 配置说明

所有配置位于 `config/` 目录，以类常量形式定义。

### 5.1 `config/App.php` 应用配置

```php
namespace config;

class App
{
    public const DEBUG = true;                  // 调试模式，关闭后不显示错误
    public const ENCRYPTION_KEY = '';           // 加密 key，定义后不要修改
    public const DEFAULT_TIMEZONE = 'Asia/Shanghai';
    public const DEPLOY_PATH = '';              // 二级目录部署路径，如 'mysmarty'
    public const APP_INIT = '';                 // 应用初始化函数名
    public const APP_DOMAIN = '';               // 站点域名，如 'example.com'
}
```

> **重要**：`ENCRYPTION_KEY` 一旦定义并使用后不可修改，否则已加密数据无法解密。

### 5.2 `config/Database.php` 数据库配置

```php
public const HOST = 'localhost';
public const USER = 'root';
public const PASSWORD = '123456';
public const PORT = 3306;
public const DATABASE = 'test';
public const CHARSET = 'utf8mb4';
public const OPTIONS = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
];
```

`OPTIONS` 支持 PDO 标准选项，详见 [PDO 设置属性](https://www.php.net/manual/zh/pdo.setattribute.php)。

### 5.3 `config/Template.php` 模板配置

```php
public const COMPILE_CHECK = true;   // 检查模板是否修改
public const FORCE_COMPILE = true;   // 强制编译（开启缓存时必须为 false）
public const CACHE = false;          // 是否开启页面缓存
public const CACHE_LIFE_TIME = 3600; // 缓存时间，秒
public const FORMAT_TO_LINE = false; // 输出压缩为一行
```

### 5.4 `config/Session.php`

```php
public const NAME = '';        // session 名称
public const LIFETIME = 604800;
public const PATH = '/';
public const DOMAIN = '';
public const SECURE = false;
public const HTTPONLY = false;
```

### 5.5 `config/Cookie.php`

```php
public const EXPIRE = 604800;
public const PATH = '/';
public const DOMAIN = '';
public const SECURE = false;
public const HTTPONLY = false;
```

### 5.6 `config/Cors.php` 跨域

```php
public const ACCESS_CONTROL_ALLOW_ORIGIN = '*';
public const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'true';
public const ACCESS_CONTROL_ALLOW_METHODS = '';
public const ACCESS_CONTROL_ALLOW_HEADERS = '';
public const ACCESS_CONTROL_EXPOSE_HEADERS = '';
public const ACCESS_CONTROL_MAX_AGE = 0;
```

### 5.7 `config/Jwt.php`

```php
public const KEY = '';                  // 密钥
public const ALG = 'HS256';             // 算法：ES384/ES256/HS256/HS384/HS512/RS256/RS384/RS512/EdDSA
```

### 5.8 `config/Mail.php` 邮件

```php
public const HOSTNAME = '';
public const PORT = 465;
public const USESSL = true;
public const SENDEMAILUSER = '';
public const SENDEMAILPASS = '';     // 密码或授权码
public const SHOWEMAIL = '';
public const TIMEOUT = 5;
public const READTIMEOUT = 3;
```

### 5.9 缓存配置（需自行创建）

> ⚠️ 框架代码引用了 `\config\Cache::TYPE` 与 `\config\Cache::REDIS`，但项目未提供该文件。使用缓存前请新建 `config/Cache.php`：

```php
namespace config;

class Cache
{
    public const TYPE = 'file';   // file 或 redis
    public const REDIS = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'db' => 0,
        'timeout' => 2.0
    ];
}
```

### 5.10 读取配置

通过全局函数 `config()` 读取：

```php
$debug = \config\App::DEBUG;                                  // 直接访问常量
$value = config('App', 'DEBUG');                              // 通过函数
$redisHost = config('Cache', 'REDIS')['host'] ?? '127.0.0.1'; // 带默认值
```

---

## 6. 路由系统

### 6.1 路由注解 `#[Route]`

定义于 `library/mysmarty/Route.php`，可标注在类或方法上。

```php
#[Route(
    uri: '',                       // 路由地址，为空使用默认
    pattern: [],                   // 路由变量正则规则
    level: Route::LEVEL_MIDDLE,    // 匹配级别
    caching: true,                 // 是否缓存
    prefix: '',                    // 路由前缀，支持 {key} 占位符
    home: false                    // 是否为首页路由
)]
```

**级别常量**：
- `LEVEL_HIGN = 9`：高，匹配优先
- `LEVEL_MIDDLE = 5`：中（默认）
- `LEVEL_LOW = 1`：低

### 6.2 默认路由规则

若未显式定义 `uri`，框架将自动按"控制器名/方法名"生成小写下划线风格的路由。

- 控制器 `app\controller\UserCenter` → `user_center`
- 方法 `getUserList` → `user_center/get_user_list`
- 控制器 `app\controller\Index` 的 `index` 方法 → `index/index`

> 类名与方法名通过 `toDivideName()` 转换为大写转下划线小写的形式。

### 6.3 控制器级路由

```php
namespace app\controller;

use library\mysmarty\Controller;
use library\mysmarty\Route;

#[Route(uri: 'admin')]
class Admin extends Controller
{
    // 访问：/admin/index
    public function index(): void
    {
        $this->display();
    }

    // 访问：/admin/list
    public function list(): void
    {
        // ...
    }
}
```

### 6.4 方法级路由

```php
class Article extends Controller
{
    // 绝对路由（以 / 开头）
    #[Route(uri: '/article/{id}')]
    public function detail(int $id): void
    {
        // 访问：/article/123
    }

    // 相对路由（不以 / 开头，会拼接控制器路径）
    #[Route(uri: 'view/{id}')]
    public function view(int $id): void
    {
        // 访问：/article/view/123
    }
}
```

### 6.5 路由变量与正则约束

通过 `pattern` 参数约束变量格式：

```php
#[Route(uri: '/article/{id}', pattern: ['id' => '\d+'])]
public function detail(int $id): void
{
    // 仅匹配数字
}
```

默认匹配规则为 `[a-z0-9_]+`。

### 6.6 路由前缀

```php
#[Route(prefix: 'api/v1')]  // 整个控制器加前缀
class User extends Controller
{
    public function info(): void
    {
        // 访问：/api/v1/user/info
    }
}
```

支持占位符（从 `config('Route', ...)` 读取）：

```php
#[Route(prefix: '{version}')]  // 会读取 config('Route', 'VERSION')
class User extends Controller { /* ... */ }
```

### 6.7 主页路由

整个应用必须定义一个主页路由，否则报错"未定义主页路由"：

```php
class Index extends Controller
{
    #[Route(home: true)]
    public function index(): void
    {
        // 访问：/
    }
}
```

### 6.8 缓存控制

- `caching: true` 表示该方法的结果页面可被缓存（同时要求 `config\Template::CACHE = true`）
- 控制器基类有 `protected bool $caching = false` 属性，子类可覆盖

### 6.9 URL 生成

通过 `buildUrl()` / `buildUri()` 反向生成 URL：

```php
$url = buildUrl('app\controller\Article', 'detail', ['id' => 123]);
// 输出：http://域名/article/123

$uri = buildUri('app\controller\Article', 'detail', ['id' => 123]);
// 输出：/article/123
```

---

## 7. 控制器（Controller）

控制器位于 `app/controller/`，需继承 `library\mysmarty\Controller`。

### 7.1 基本结构

```php
namespace app\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function index(): void
    {
        $this->assign('name', '果果开发');
        $this->display();
    }
}
```

### 7.2 模板渲染

#### `assign($key, $value)` 分配变量

```php
$this->assign('title', '首页');
$this->assign('user', ['id' => 1, 'name' => 'Tom']);
```

#### `display($template = '')` 显示模板

- 不传参：自动定位 `view/<控制器下划线名>/<方法下划线名>.html`
  - 控制器 `Index`、方法 `getUserList` → `view/index/get_user_list.html`
- 传文件名（不含 `/`）：在当前控制器视图目录下查找
  - `$this->display('edit')` → `view/index/edit.html`
- 传完整路径（含 `/`）：相对 `view/` 目录
  - `$this->display('article/detail.html')` → `view/article/detail.html`

### 7.3 成功/失败提示

```php
$this->success('保存成功', '/user/list');   // 成功提示并跳转
$this->error('保存失败');                    // 失败提示，返回上一页
```

### 7.4 控制器属性

```php
class Article extends Controller
{
    protected bool $caching = true;        // 开启页面缓存
    protected bool $formatHtml = false;    // 关闭 HTML 压缩
}
```

### 7.5 清理缓存

```php
$this->clearTemplateDirCache();  // 删除模板编译目录
$this->clearCache();             // 清空内容缓存
```

### 7.6 返回 JSON

控制器方法返回非 `null` 的数组或对象时，框架自动 JSON 输出：

```php
public function api(): array
{
    return ['status' => 1, 'data' => [1, 2, 3]];
}
```

也可显式调用：

```php
echoJson(1, ['id' => 1], '成功');   // status=1, data=..., msg=...
json(['foo' => 'bar']);              // 直接输出 JSON
```

### 7.7 控制器全局信息

- `Start::$controller`：当前控制器名（相对 `app\controller\`）
- `Start::$action`：当前方法名
- `Start::$route`：当前匹配的路由信息数组

---

## 8. 模板引擎（Template）

模板文件位于 `app/view/`，默认分隔符 `{` 和 `}`。

### 8.1 变量输出

```html
{$name}
{$user.name}
{$user['id']}
```

### 8.2 变量修饰符（管道）

通过 `|` 调用函数处理变量：

```html
{$title|upper}
{$content|len:30}
{$price|number_format:2}
```

支持多级：

```html
{$str|trim|upper}
```

### 8.3 函数调用

```html
{:date('Y-m-d')}
{:config('App', 'DEBUG')}
```

### 8.4 条件判断

```html
{if $user.id > 0}
    已登录
{elseif $user.id === 0}
    游客
{else}
    未知
{/if}
```

### 8.5 循环

#### Smarty 风格

```html
{foreach from=$list item=v key=k}
    {$k}: {$v}
{/foreach}
```

#### PHP 风格

```html
{foreach $list as $k => $v}
    {$k}: {$v}
{/foreach}
```

### 8.6 PHP 代码块

```html
{php}
    $a = 1 + 2;
    echo $a;
{/php}
```

### 8.7 引入文件

```html
{include file="header.html"}
{include file="common/nav.html"}
```

被引入的文件会先被编译。

### 8.8 模板继承

父模板 `layout.html`：

```html
<html>
<body>
{block name=header}默认头部{/block}
{block name=content}默认内容{/block}
</body>
</html>
```

子模板：

```html
{extends file="layout.html"}
{block name=header}我的头部{/block}
{block name=content}我的内容{/block}
```

### 8.9 系统标签

| 标签 | 说明 | 输出 |
|------|------|------|
| `{url}` | 输出站点绝对 URL | `http://域名` |
| `{href}` | 输出当前访问路径 | `http://域名/当前路径` |

### 8.10 验证码标签

```html
{captcha src="/captcha"}
```

生成 `<img>` 标签，点击可切换验证码。

### 8.11 `literal` 原样输出

避免内部内容被模板引擎解析（如 JS 模板字符串）：

```html
{literal}
    <script>
        const s = `Hello ${name}`;
    </script>
{/literal}
```

### 8.12 编译与缓存

- 编译文件：`runtime/templates_c/<控制器名>/<md5(template)>.php`
- 页面缓存：基于 `REQUEST_URI` 生成缓存 key，存储于 `runtime/cache/`
- 强制编译：`config\Template::FORCE_COMPILE = true` 时每次重新编译
- 缓存校验：`COMPILE_CHECK = true` 时模板修改后自动重新编译

---

## 9. 数据库 ORM（Model）

### 9.1 定义模型

模型位于 `app/model/`（建议），继承 `library\mysmarty\Model`：

```php
namespace app\model;

use library\mysmarty\Model;

class User extends Model
{
    protected string $database = 'test';   // 可选，默认使用配置
    protected string $table = 'user';      // 可选，默认按类名生成
}
```

类名自动转换：`User` → 表 `user`；`UserLog` → 表 `user_log`。

### 9.2 单例获取

```php
$userModel = \app\model\User::getInstance();
```

### 9.3 链式查询

```php
$users = $userModel
    ->field('id, name, created_at')
    ->where('status', 1)
    ->where('age', 18, '>=')
    ->order('id', 'desc')
    ->limit(0, 20)
    ->select();
```

### 9.4 WHERE 条件

#### 多种写法

```php
// 字符串字段 + 值 + 操作符
$m->where('id', 1);
$m->where('name', 'Tom', '=');
$m->where('age', 18, '>');

// 数组批量
$m->where([
    'id'   => 1,
    'name' => 'Tom'
]);

// 数组指定操作符与连接符
$m->where([
    'id'    => [1, '='],          // [值, 操作符]
    'age'   => [18, '>=', 'AND'], // [值, 操作符, 连接符]
]);

// OR 条件
$m->whereOr('status', 0);
```

#### 便捷方法

| 方法 | 说明 |
|------|------|
| `eq(field, value)` | `=` |
| `neq(field, value)` | `!=` |
| `gt(field, value)` | `>` |
| `egt(field, value)` | `>=` |
| `lt(field, value)` | `<` |
| `elt(field, value)` | `<=` |
| `like(field, value)` | `LIKE` |
| `notLike(field, value)` | `NOT LIKE` |
| `between(field, start, end)` | `BETWEEN` |
| `notBetween(field, start, end)` | `NOT BETWEEN` |
| `in(field, values)` | `IN`，值可为数组或逗号字符串 |
| `notIn(field, values)` | `NOT IN` |
| `findInSet(field, value)` | `FIND_IN_SET` |
| `null(field)` | `IS NULL` |
| `notNull(field)` | `IS NOT NULL` |
| `whereRaw(sql, bindings, union)` | 原生 WHERE |

#### 复杂条件（嵌套）

```php
$m->where([
    'status' => 1,
    [                        // 嵌套组
        ['name' => ['Tom', 'LIKE', 'OR']],
        ['name' => ['Jerry', 'LIKE', 'OR']]
    ]
]);
```

### 9.5 字段控制

```php
$m->field('id, name');          // 指定字段
$m->distinct(true);             // 去重
```

### 9.6 JOIN

```php
$m->leftJoin('order o', 'u.id = o.user_id');
$m->rightJoin('order o', 'u.id = o.user_id');
$m->innerJoin('order o', 'u.id = o.user_id');
$m->join('order o', 'u.id = o.user_id', 'left join');
```

### 9.7 排序/分组/分页/锁

```php
$m->order('id desc');
$m->group('user_id');
$m->having('count > 5');
$m->limit(0, 10);            // limit 偏移, 数量
$m->page(2, 10);             // 第 2 页，每页 10 条
$m->lock(true);              // FOR UPDATE
$m->lock('LOCK IN SHARE MODE');
$m->forceIndex('idx_user');  // 强制索引
$m->using('o');              // USING
$m->extra('DELAYED');        // INSERT/UPDATE 修饰符
```

### 9.8 UNION

```php
$m->union('SELECT * FROM user_backup');
$m->union(['SELECT ...', 'SELECT ...'], 1); // 1 = UNION ALL
```

### 9.9 查询单条/多条

```php
$row  = $m->find();          // 单条，无结果返回 []
$list = $m->select();        // 多条
$val  = $m->value('name');   // 取单个字段
```

### 9.10 聚合查询

```php
$count = $m->count();
$count = $m->count('id');
$max   = $m->max('age');
$min   = $m->min('age');
$avg   = $m->avg('age');
$sum   = $m->sum('money');
```

### 9.11 添加数据

```php
$id = $m->add([
    'name'  => 'Tom',
    'email' => 'tom@test.com'
]);
// 返回 lastInsertId

$m->addAll([
    ['name' => 'A'],
    ['name' => 'B'],
]); // 批量插入

$m->replace($data);  // REPLACE INTO
```

### 9.12 字段过滤

```php
$m->allowField(['name', 'age'])->add($data);     // 仅允许指定字段
$m->allowField('name,age')->add($data);          // 字符串
$m->allowField(true)->add($data);                // 仅允许表中存在的字段
```

### 9.13 更新数据

```php
$affected = $m->where('id', 1)->update([
    'name' => 'New Name'
]);

$m->where('id', 1)->setField('status', 1);   // 更新单字段
$m->where('id', 1)->setInc('views', 1);      // 自增
$m->where('id', 1)->setDec('stock', 1);      // 自减
```

### 9.14 删除数据

```php
$m->where('id', 1)->delete();   // 按 where 删除
$m->delete(1);                  // 按主键删除
$m->delete('1,2,3');            // 批量删除
```

### 9.15 事务

```php
try {
    $m->startTrans();
    $m->add([...]);
    $m->where('id', 1)->update([...]);
    $m->commit();
} catch (\Throwable $e) {
    $m->rollback();
}
```

### 9.16 分页查询

```php
$result = $m->where('status', 1)->order('id desc')->paginate(10);
// 返回：
// [
//   'curPage'  => 当前页,
//   'count'    => 总数,
//   'totalPage'=> 总页数,
//   'size'     => 每页数,
//   'pageData' => 显示页码数组,
//   'data'     => 当前页数据
// ]
```

参数：
- `size`：每页条数，默认 10
- `limitTotalPage`：限制总页数（防深度翻页），`false` 不限制
- `limitPage`：分页显示个数，`false` 不返回 `pageData`
- `varPage`：URL 分页变量名，默认 `page`

### 9.17 关联查询

```php
// 等同于 leftJoin，自动拼接外键关系
$m->with('order', 'user_id', 'id', 'order.id, order.amount')
  ->select();
```

### 9.18 全文搜索

```php
// NATURAL LANGUAGE MODE
$m->match('title,content', 'PHP 框架')->select();

// BOOLEAN MODE
$m->match('title,content', '+PHP -Java', true)->select();

// 同时返回相关度
$m->matchField('title,content', 'PHP', 'relativity')
  ->order('relativity desc')
  ->select();
```

### 9.19 原生 SQL

```php
$rows = $m->query('SELECT * FROM user WHERE id > ?', [10]);
$affected = $m->execute('UPDATE user SET status = ? WHERE id = ?', [1, 5]);
$id = $m->getLastInsertId();
$sql = $m->getLastSql();   // 最近执行的 SQL（含绑定参数）
```

### 9.20 表操作

```php
$m->truncate();                       // 清空当前表
$m->truncate('other_table');          // 清空指定表
$m->changeDatabase('other_db');       // 切换数据库
$m->setDatabase('db')->setTable('t'); // 临时切换
$m->getTableInfo();                   // 表字段信息
```

### 9.21 错误信息

```php
$m->getErrorCode();    // SQLSTATE 错误码
$m->getErrorInfo();    // 详细错误信息
$m->getRowCount();     // 受影响行数
```

---

## 10. 缓存（Cache）

### 10.1 静态代理

`Cache` 类根据 `config\Cache::TYPE` 自动分发到 `FileCache` 或 `RedisCache`。

```php
use library\mysmarty\Cache;

Cache::set('key', 'value', 3600);           // 设置，3600 秒
$v = Cache::get('key', '默认值');           // 获取
Cache::delete('key');                       // 删除
Cache::clear(0);                            // 清空所有
```

### 10.2 全局函数

```php
setCache('key', $data, 3600);
$v = getCache('key');
deleteCache('key');
clearCache();
```

### 10.3 文件缓存

- 存储位置：`runtime/cache/`
- 文件名：`md5(key + ENCRYPTION_KEY)`
- 内部使用 `serialize` 序列化，包含过期时间

### 10.4 Redis 缓存

- 需安装 `redis` 扩展
- 连接配置见 `config\Cache::REDIS`
- key 自动加 `md5(key + ENCRYPTION_KEY)` 前缀

### 10.5 页面缓存

开启 `config\Template::CACHE = true` 后，控制器中 `caching = true` 的方法会将页面内容缓存到 `CACHE_LIFE_TIME` 秒。下次请求时直接输出缓存。

---

## 11. Session 与 Cookie

### 11.1 Session

```php
use library\mysmarty\Session;

$sess = Session::getInstance();
$sess->set('user_id', 123);
$uid = $sess->get('user_id', 0);
$sess->delete('user_id');
$sess->clear();
$all = $sess->getAll();
$sid = $sess->getSessionId();
```

全局函数：

```php
startSession();
setSession('k', 'v');
$v = getSession('k');
deleteSession('k');
clearAllSession();
```

### 11.2 Cookie

```php
use library\mysmarty\Cookie;

$cookie = Cookie::getInstance();
$cookie->set('token', 'abc', 3600);   // 名, 值, 过期秒数
$v = $cookie->get('token', '');
$cookie->delete('token');
$cookie->clear();
$all = $cookie->getAll();

// 链式配置
$cookie->setDomain('.example.com')->setSecure(true)->set('token', 'abc');
```

全局函数：

```php
setLocalCookie('k', 'v', 3600);
$v = getLocalCookie('k');
deleteCookie('k');
clearAllCookie();
```

---

## 12. JWT 认证

### 12.1 配置

在 `config/Jwt.php` 设置 `KEY` 与 `ALG`。

支持的算法：`ES384`、`ES256`、`HS256`、`HS384`、`HS512`、`RS256`、`RS384`、`RS512`、`EdDSA`。

### 12.2 编码

```php
use library\mysmarty\Jwt;

$jwt = Jwt::getInstance();
$token = $jwt->encode([
    'user_id' => 123,
    'role'    => 'admin'
], 7200);   // 过期 7200 秒
```

自动注入标准声明：
- `iss`、`aud`：默认为当前站点 URL
- `iat`：签发时间
- `nbf`：生效时间
- `exp`：过期时间

### 12.3 解码

```php
$payload = $jwt->decode($token);
if (false === $payload) {
    echo $jwt->getError();   // 错误信息
}
```

### 12.4 自定义配置

```php
$jwt->setKey('custom-key')
    ->setAlg('HS512')
    ->setHeader(['kid' => 'key1'])
    ->encode($payload);
```

---

## 13. 加密与解密（Encrypt）

基于 `openssl_encrypt` / `openssl_decrypt`。

```php
use library\mysmarty\Encrypt;

$enc = Encrypt::getInstance();

$cipher = $enc->encode('明文');               // 使用 config\App::ENCRYPTION_KEY
$plain  = $enc->decode($cipher);

// 自定义
$enc->setMethod('AES-256-CBC')
    ->setIv(str_repeat('0', 16))
    ->encode('data', 'my-key');
```

默认密钥取 `config\App::ENCRYPTION_KEY`，默认方法取 `openssl_get_cipher_methods()[0]`。

---

## 14. 验证码（Captcha）

### 14.1 输出验证码图片

```php
use library\mysmarty\Captcha;
use library\enum\Captcha as CaptchaStyle;

Captcha::getInstance()
    ->setCodeSize(4)
    ->setCodeStyle(CaptchaStyle::NUMBER_AND_LETTER)
    ->setHeight(50)
    ->setFont(25)
    ->setFontFile('/path/to/font.otf')
    ->setSessionName('login_code')
    ->setExpireTime(300)
    ->output();
```

`Captcha` 枚举：
- `NUMBER_AND_LETTER`：数字+字母（默认）
- `NUMBER`：纯数字
- `LETTER`：纯字母
- `ZH`：中文

### 14.2 Ajax 验证码

返回 base64 图片与 token：

```php
$data = Captcha::getInstance()->setAjax(true)->output();
// ['token' => 'xxx', 'code' => 'data:image/png;base64,...']
```

### 14.3 校验

```php
// Session 模式
$ok = Captcha::check('用户输入', 'login_code');

// Ajax 模式
$ok = Captcha::check('用户输入', $token);
```

### 14.4 模板中使用

```html
{captcha src="/captcha"}
```

---

## 15. 文件上传（Upload）

```php
use library\mysmarty\Upload;

$upload = Upload::getInstance()
    ->setLimitType(['image/png', 'image/jpeg'])
    ->setLimitExt('png,jpg,jpeg')
    ->setLimitSize(2 * 1024 * 1024);   // 2MB

// 单文件
$path = $upload->move('avatar');

// 多文件（表单名相同，如 name="files[]"）
$paths = $upload->move('files');
```

返回结果：
- 单文件成功：`/upload/20260101/xxxx.png`
- 多文件成功：`['/upload/...', '/upload/...']`
- 失败：`false`

文件保存到 `public/upload/<日期>/<随机串>.<ext>`，图片文件会通过 `finfo` 扩展做合法性校验。

---

## 16. 文件下载（Download）

### 16.1 远程文件下载

```php
use library\mysmarty\Download;

$saved = Download::getInstance()
    ->setDownloadUrl('https://example.com/file.zip')
    ->setSaveDir(PUBLIC_DIR . '/download')
    ->setSaveFilename('myfile.zip')
    ->setTimeOut(120)
    ->setContentType('application/zip')
    ->download();
// 返回保存后的相对路径
```

### 16.2 浏览器输出下载

`BrowserDownload` 类用于将本地文件以附件形式推送给浏览器（详见源码）。

---

## 17. HTTP 请求（Query）

基于 cURL 实现的链式 HTTP 客户端。

```php
use library\mysmarty\Query;

$resp = Query::getInstance()
    ->setUrl('https://api.example.com/users')
    ->setPcUserAgent()
    ->setRandIp()
    ->setTimeOut(20)
    ->setHeader(['Authorization: Bearer xxx'])
    ->setPostFields(['name' => 'Tom'])
    ->request();

$info = Query::getInstance()->getCurlInfo();
```

常用方法：
- `setUrl($url)`
- `setPcUserAgent()` / `setMobileUserAgent()` / `setSpiderUserAgent()`：从 UA 池随机选取
- `setRandIp()`：随机伪造 `X-Forwarded-For`
- `setHeader(array)` / `setReferer($url)`
- `setPostFields(array|string)`：POST 数据
- `setTimeOut(int)`
- `setProxy($ip, $type)`
- `setEncoding('gzip')`
- `setVerifypeer(false)`：关闭 SSL 证书校验
- `request()`：执行请求，返回响应体
- `getCurlInfo()`：获取 cURL 元信息

---

## 18. 邮件发送（Smtp）

配置 `config/Mail.php` 后：

```php
use library\mysmarty\Smtp;

$smtp = new Smtp();
// 调用发送方法（具体方法请查看 Smtp.php 源码：send 系列方法）
if (!empty($smtp->getError())) {
    echo $smtp->getError();
}
```

支持 SSL、超时控制，构造时自动连接 SMTP 服务器。

---

## 19. 图像处理（Image）

基于 GD 库，支持常见图片格式（GIF/JPG/PNG/BMP/WEBP）。

```php
use library\mysmarty\Image;

$img = Image::getInstance();
// 详见 Image.php：缩略图、水印、格式转换、质量设置等
$img->setJpgQuality(80)
    ->setWebpQuality(90)
    ->setPngQuality(6);
```

类型常量：`1=GIF`、`2=JPG`、`3=PNG`、`6=BMP`、`18=WEBP`。

---

## 20. 分页（Page）

`Page` 类与 `Model::paginate()` 配合使用，也可独立调用：

```php
use library\mysmarty\Page;

$p = Page::getInstance()->paginate(100, 10, false, 5, 'page');
// [
//   'curPage' => 1,
//   'count'   => 100,
//   'totalPage' => 10,
//   'size'    => 10,
//   'pageData'=> [1,2,3,4,5]
// ]
```

`Model::paginate()` 已自动包含当前页数据：

```php
$data = $m->paginate(10);
```

---

## 21. 全局函数

`library/function.php` 提供大量全局函数。

### 21.1 请求相关

| 函数 | 说明 |
|------|------|
| `isGet() / isPost() / isPut() / isDelete() / isHead() / isPatch() / isOptions()` | 判断请求方法 |
| `isCliMode() / isCgiMode()` | 是否 CLI/CGI 模式 |
| `input($name, $defValue, $httpMethod)` | 通用参数获取 |
| `getString($name, $defValue, $trim)` | GET 字符串 |
| `getInt($name, $defValue)` / `getFloat` / `getNumeric` | GET 数值 |
| `getAarray($name, $defValue)` | GET 数组 |
| `getPostString / getPostInt / getPostFloat / getPostNumeric / getPostAarray` | POST 参数 |
| `getFiles($name)` | 上传文件 |
| `getRequestBodyContent()` | 原始 body |
| `getServerValue($name, $defValue)` | `$_SERVER` 值 |
| `getPath()` | 当前 URI path |
| `isRequestHtml() / isRequestJson()` | 请求类型 |

### 21.2 URL 与跳转

| 函数 | 说明 |
|------|------|
| `getAbsoluteUrl()` | 站点绝对 URL |
| `getHref()` | 当前完整 URL |
| `getDomain()` | 当前域名（含端口） |
| `getFixedUrl($url)` | 修正为绝对 URL |
| `generateUrl($path)` | 生成 URL |
| `redirect($url, $code)` | 重定向 |
| `refresh($url, $time)` | meta 刷新 |
| `tip($msg, $url, $code, $formatUrl, $icon)` | 提示并跳转 |
| `error($msg, $code)` | 错误提示 |
| `notFound()` | 404 |
| `alert(...)` | 弹窗提示页 |

### 21.3 输出

| 函数 | 说明 |
|------|------|
| `echoJson($status, $data, $msg)` | 标准 JSON 响应 |
| `json($data)` | 直接 JSON 输出（自动加 CORS 头） |
| `echoJsonHeader() / echoHtmlHeader()` | 响应头 |

### 21.4 验证

| 函数 | 说明 |
|------|------|
| `isEmail($email)` | 邮箱 |
| `isPhone($phone)` | 手机号（1 开头 11 位） |
| `isUrl($url)` | URL |
| `isValidIp($ip, $type)` / `isIp` | IP |
| `isDomain($domain, $strict)` / `isMainDomain` | 域名 |
| `isMobile()` | 是否手机端 |
| `isBot()` | 是否爬虫 |
| `isZh($str)` / `hasZh($str)` | 中文判断 |
| `isImage($filePath)` | 是否真实图片 |

### 21.5 字符串/数组

| 函数 | 说明 |
|------|------|
| `myTrim($str)` | 去除空白与 `<br>` |
| `len($str, $len)` | 截取 |
| `toDivideName($name, $splitStr)` | 大写转下划线 |
| `toHumpName($str)` | 下划线转大写 |
| `formatController($c)` / `formatAction($a)` | 命名转换 |
| `getRandomString($len, $type, $specialString)` | 随机字符串 |
| `getZhChar($num)` | 随机中文字符 |
| `xmlToArray($xml)` / `arrayToXml($data)` | XML 互转 |
| `formatHtml($html)` / `formatCss($css)` / `formatJs($js)` | 代码压缩 |

### 21.6 文件/目录

| 函数 | 说明 |
|------|------|
| `createDir($dir)` | 创建目录 |
| `createDirByFile($file)` | 通过文件路径创建目录 |
| `removeDir($dir, $deleteDir)` | 递归删除 |
| `arrFormatFile($data)` | 数组转为 PHP 文件 |
| `requireArrData($file)` | 从 PHP 文件读数组 |
| `formatFileSize($size, $decimals)` | 格式化字节 |

### 21.7 时间/系统

| 函数 | 说明 |
|------|------|
| `getCurrentMicroTime()` | 微秒时间 |
| `formatTime($time)` | 友好时间（"3 分钟前"） |
| `formatToTime($time, $format)` | 格式化时间 |
| `isWin()` / `getPlatformName()` | 操作系统判断 |
| `getMemoryUsage()` | 当前内存占用 |
| `getMemInfo() / getMemFreeRate()` | 内存信息（Linux） |

### 21.8 其他

| 函数 | 说明 |
|------|------|
| `config($class, $property, $defValue)` | 读取配置 |
| `getNamespaceClass($dir)` | 扫描目录类名 |
| `buildUri($class, $method, $params)` | 生成 URI |
| `buildUrl($class, $method, $params, $prefixUrl)` | 生成完整 URL |
| `downloadImg($imgSrc)` | 下载远程图片 |
| `getUserAgent()` | 当前 UA |

---

## 22. CLI 命令行模式

框架支持 CLI 模式执行。入口脚本 `mysmarty`（项目根目录）：

```bash
./mysmarty <路由路径>
```

或直接使用 PHP：

```bash
php public/index.php <路由路径>
```

例如执行 `/index/index`：

```bash
php public/index.php index/index
```

- 框架在 CLI 模式下自动从 `$argv[1]` 提取路由
- `tip()` 等输出函数在 CLI 下会改为 `echoCliMsg()` 输出纯文本
- 适合用于定时任务、队列消费、脚本工具

---

## 23. 部署建议

### 23.1 Nginx 配置示例

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/MySmarty/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问敏感目录
    location ~ ^/(app|config|library|runtime|docs)/ {
        deny all;
    }
}
```

### 23.2 二级目录部署

若部署在 `https://example.com/mysmarty/` 下，设置：

```php
// config/App.php
public const DEPLOY_PATH = 'mysmarty';
```

框架在解析 URI 时会自动去掉该前缀。

### 23.3 生产环境优化

- `config\App::DEBUG = false`：关闭调试，启用路由缓存
- `config\Template::COMPILE_CHECK = false`：关闭编译检查
- `config\Template::FORCE_COMPILE = false`：关闭强制编译
- `config\Template::CACHE = true`：开启页面缓存
- `config\Template::FORMAT_TO_LINE = true`：开启 HTML 压缩
- 移除 `X-Powered-By` 已自动处理
- 将 `runtime/` 设为可写：`chmod -R 755 runtime`

### 23.4 安全建议

- 所有用户输入通过 `getString` / `getInt` 等强类型函数获取
- 数据库操作全部使用 PDO 预处理，避免 SQL 注入
- 上传文件做 MIME 与扩展名双重校验，图片通过 `finfo` 校验
- `ENCRYPTION_KEY` 与 JWT `KEY` 使用复杂随机串
- Cookie 在生产环境建议开启 `HTTPONLY` 与 `SECURE`

---

## 24. 附录：常见问题

### 24.1 修改路由后不生效？

路由缓存于 `runtime/route/`。修改控制器后请：
1. 开启 `DEBUG = true`，或
2. 删除 `runtime/route/` 目录

### 24.2 模板修改后未更新？

- `COMPILE_CHECK = true` 时会自动检测模板修改时间
- 或删除 `runtime/templates_c/` 目录
- `FORCE_COMPILE = true` 时每次都重新编译

### 24.3 如何使用 Redis 缓存？

1. 安装 `redis` 扩展
2. 创建 `config/Cache.php`，设置 `TYPE = 'redis'` 与连接信息
3. 通过 `Cache::set/get` 或 `setCache/getCache` 使用

### 24.4 如何自定义全局函数？

在 `app/common.php` 中定义（需自行创建该文件，框架会自动引入）。

### 24.5 如何在应用启动时执行初始化逻辑？

```php
// config/App.php
public const APP_INIT = 'my_app_init';

// app/common.php
function my_app_init() {
    // 例如：注册错误处理、加载助手、初始化日志等
}
```

### 24.6 控制器返回值规则？

- 返回 `null` 或 `void`：通常配合 `$this->display()` 输出模板
- 返回数组/对象：自动 JSON 输出
- 返回字符串：根据请求头决定 HTML 或 JSON 输出

### 24.7 命名约定

| 类型 | 规则 | 示例 |
|------|------|------|
| 控制器类名 | 大驼峰 | `UserCenter` |
| 控制器文件 | 与类名一致 | `UserCenter.php` |
| 方法名 | 大驼峰 | `getUserList` |
| 默认 URL | 小写下划线 | `user_center/get_user_list` |
| 模型类名 | 大驼峰 | `UserLog` |
| 默认表名 | 小写下划线 | `user_log` |
| 模板文件 | 小写下划线 | `user_log/get_list.html` |

### 24.8 命名空间与自动加载

框架通过 `spl_autoload_register` 自动加载，规则：

```
类名 app\controller\Index  →  app/controller/Index.php
类名 library\mysmarty\App  →  library/mysmarty/App.php
类名 config\App            →  config/App.php
```

新增类只需保证命名空间与目录一致即可。

---

## 结语

MySmarty 致力于在保持轻量的同时提供完整的 Web 开发能力。本文件覆盖了框架所有核心模块的用法，遇到问题可结合源码注释深入理解。祝开发顺利！
