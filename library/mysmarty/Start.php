<?php

namespace library\mysmarty;
/**
 * 应用启动类
 */
class Start
{
    /**
     * 当前控制器
     * @var string
     */
    public static string $controller = '';

    /**
     * 当前执行方法
     * @var string
     */
    public static string $action = '';

    /**
     * 当前执行路由数据
     * @var array
     */
    public static array $route = [];

    /**
     * 初始化引入
     */
    public static function initCommon(): void
    {
        define('APP_DIR', ROOT_DIR . '/app');
        if (!defined('PUBLIC_DIR')) {
            define('PUBLIC_DIR', ROOT_DIR . '/public');
        }
        define('RUNTIME_DIR', ROOT_DIR . '/runtime');
        define('LIBRARY_DIR', ROOT_DIR . '/library');
        // 自动加载
        spl_autoload_register(function ($class) {
            $classFile = ROOT_DIR . '/' . str_ireplace('\\', '/', $class) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
            }
        });
        // 引入核心函数库
        require_once LIBRARY_DIR . '/function.php';
        require_once APP_DIR . '/common.php';
        // 初始化配置
        if (!\config\App::DEBUG) {
            error_reporting(0);
        }
        date_default_timezone_set(\config\App::DEFAULT_TIMEZONE);
        // 加载第三方库
        if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
            require_once ROOT_DIR . '/vendor/autoload.php';
        }
        // 移除X-Powered-By信息
        header_remove('X-Powered-By');
        // 执行初始化方法
        if (!empty(\config\App::APP_INIT) && function_exists(\config\App::APP_INIT)) {
            call_user_func(\config\App::APP_INIT);
        }
    }

    /**
     * 执行控制器方法
     */
    public static function forward(): void
    {
        self::initCommon();
        if (isCliMode()) {
            global $argv;
            if (!empty($argv[1])) {
                define('URI_PATH', trim($argv[1], '/'));
            }
        }
        $uri = getPath();
        $mat = [];
        $route = App::getInstance()->getRouteData();
        if (!empty($uri)) {
            foreach ($route as $v) {
                if (!$v['methodHome']) {
                    // 匹配当前规则，获取()内的内容
                    if ($uri === $v['uri'] || preg_match('#^' . $v['uri'] . '$#iU', $uri, $mat)) {
                        self::runRoute($v, $mat);
                        break;
                    }
                }
            }
            error('页面不存在', 404);
        } else {
            if (!empty($route['home'])) {
                self::runRoute($route['home'], $mat);
            } else {
                error('页面不存在', 404);
            }
        }
    }

    /**
     * 调用模块方法
     * @param array $params 请求参数
     */
    public static function go(array $params = []): void
    {
        $controllerNamespace = 'app\controller\\' . Start::$controller;
        $obj = new $controllerNamespace();
        $result = call_user_func_array(array(
            $obj,
            Start::$action
        ), $params);
        if (!is_null($result)) {
            if (is_array($result) || is_object($result)) {
                $result = json_encode($result);
            }
            if (isRequestJson()) {
                echoJsonHeader();
            } else {
                echoHtmlHeader();
            }
            echo $result;
        }
        exit();
    }

    /**
     * 执行路由规则
     * @param array $route 匹配到的路由
     * @param array $mat 匹配到的结果
     */
    public static function runRoute(array $route, array $mat): void
    {
        if (empty($route)) {
            error('页面不存在', 404);
        }
        // 方法执行需要的参数
        $params = [];
        foreach ($route['methodParams'] as $param) {
            if (isset($mat[$param])) {
                $params[$param] = $mat[$param];
            }
        }
        $controller = $route['controller'];
        $action = $route['methodName'];
        self::$controller = $controller;
        self::$action = $action;
        self::$route = $route;
        // 执行缓存
        if ($route['caching'] && \config\Template::CACHE) {
            Template::getInstance()->showCache();
        }
        self::go(params: $params);
    }
}