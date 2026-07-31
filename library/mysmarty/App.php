<?php

namespace library\mysmarty;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class App extends Container
{
    // route文件配置
    private string $routeFile = RUNTIME_DIR . '/route/route_' . \config\App::ENCRYPTION_KEY . '.php';
    private array $routeData = [];

    /**
     * 初始化
     */
    public function _initialize()
    {
        $this->initData();
    }

    /**
     * 初始化数据
     */
    private function initData()
    {
        if (\config\App::DEBUG || !file_exists($this->routeFile)) {
            $this->initRoute();
        } else {
            $this->routeData = requireArrData($this->routeFile);
        }
    }

    /**
     * 初始化路由
     */
    public function initRoute()
    {
        $controllerDir = APP_DIR . '/controller';
        $classData = getNamespaceClass($controllerDir);
        $routeData = [];
        $sortLevelData = [];
        $sortLenData = [];
        try {
            foreach ($classData as $class) {
                // 获取类上的路由设置
                $obj = new ReflectionClass($class);
                // 路由参数
                $topRoute = '';
                $topPattern = [];
                $topLevel = Route::LEVEL_MIDDLE;
                $topCaching = true;
                $topPrefix = '';
                // 已设置属性
                $setArguments = [];
                // 从父类获取未设置的路由属性
                $parentObj = $obj;
                while (true) {
                    $attributes = $parentObj->getAttributes(Route::class);
                    if (1 === count($attributes)) {
                        $arguments = $attributes[0]->getArguments();
                        // 定义了路由
                        $topRouteObj = $attributes[0]->newInstance();
                        if (!isset($setArguments['uri']) && (isset($arguments['uri']) || isset($arguments[0]))) {
                            $topRoute = $topRouteObj->getUri();
                            $setArguments['uri'] = $topRoute;
                        }
                        if (!isset($setArguments['pattern']) && (isset($arguments['pattern']) || isset($arguments[1]))) {
                            $topPattern = $topRouteObj->getPattern();
                            $setArguments['pattern'] = $topPattern;
                        }
                        if (!isset($setArguments['level']) && (isset($arguments['level']) || isset($arguments[3]))) {
                            $topLevel = $topRouteObj->getLevel();
                            $setArguments['level'] = $topLevel;
                        }
                        if (!isset($setArguments['caching']) && (isset($arguments['caching']) || isset($arguments[4]))) {
                            $topCaching = $topRouteObj->isCaching();
                            $setArguments['caching'] = $topCaching;
                        }
                        if (!isset($setArguments['prefix']) && (isset($arguments['prefix']) || isset($arguments[5]))) {
                            $topPrefix = $topRouteObj->getPrefix();
                            $setArguments['prefix'] = $topPrefix;
                        }
                    }
                    // 从父类获取
                    $parentObj = $parentObj->getParentClass();
                    if (false === $parentObj) {
                        break;
                    }
                }
                if ($topCaching) {
                    $defaultProperties = $obj->getDefaultProperties();
                    if (!isset($defaultProperties['caching']) || false === $defaultProperties['caching']) {
                        $topCaching = false;
                    }
                }
                $controllerPath = str_ireplace('app\controller\\', '', $class);
                // 获取方法上的路由设置
                $methods = $obj->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    // 方法路由参数列表
                    $methodRoute = '';
                    $methodPattern = $topPattern;
                    $methodLevel = $topLevel;
                    $methodCaching = $topCaching;
                    $methodPrefix = '';
                    $methodHome = false;
                    // 去掉_开头的方法
                    $methodName = $method->getName();
                    if (str_starts_with($methodName, '_')) {
                        continue;
                    }
                    $methodAttributes = $method->getAttributes(Route::class);
                    if (1 === count($methodAttributes)) {
                        // 已设置参数
                        $methodArguments = $methodAttributes[0]->getArguments();
                        // 方法使用了路由
                        $methodRouteObj = $methodAttributes[0]->newInstance();
                        if (isset($methodArguments['uri']) || isset($methodArguments[0])) {
                            $methodRoute = $methodRouteObj->getUri();
                        }
                        if (isset($methodArguments['pattern']) || isset($methodArguments[1])) {
                            $methodPattern = array_merge($methodPattern, $methodRouteObj->getPattern());
                        }
                        if (isset($methodArguments['level']) || isset($methodArguments[3])) {
                            $methodLevel = $methodRouteObj->getLevel();
                        }
                        if (isset($methodArguments['caching']) || isset($methodArguments[4])) {
                            $methodCaching = $methodRouteObj->isCaching();
                        }
                        if (isset($methodArguments['prefix']) || isset($methodArguments[5])) {
                            $methodPrefix = $methodRouteObj->getPrefix();
                        }
                        if (isset($methodArguments['home']) || isset($methodArguments[7])) {
                            $methodHome = $methodRouteObj->getHome();
                        }
                    }
                    if (empty($methodRoute)) {
                        // 转为普通访问方式
                        $methodRoute = toDivideName($methodName);
                    }
                    if (!str_starts_with($methodRoute, '/')) {
                        $tmpTopRoute = $topRoute;
                        if (empty($tmpTopRoute)) {
                            // 转为普通访问方式
                            $tmp = str_ireplace('\\', '/', $controllerPath);
                            $tmp = toDivideName($tmp, '/');
                            $tmpTopRoute = '/' . $tmp;
                        }
                        if (!empty($topPrefix)) {
                            $tmpTopRoute = trim($topPrefix, '/') . '/' . trim($tmpTopRoute, '/');
                        }
                        if (!empty($methodPrefix)) {
                            $methodRoute = trim($methodPrefix, '/') . '/' . trim($methodRoute, '/');
                        }
                        $methodRoute = trim($tmpTopRoute, '/') . '/' . $methodRoute;
                    } else {
                        if (!empty($methodPrefix)) {
                            $methodRoute = trim($methodPrefix, '/') . '/' . trim($methodRoute, '/');
                        }
                        if (!empty($topPrefix)) {
                            $methodRoute = trim($topPrefix, '/') . '/' . trim($methodRoute, '/');
                        }
                    }
                    $methodRoute = preg_replace('/[\\\]/', '/', $methodRoute);
                    $methodRoute = preg_replace('/[\/]+/', '/', $methodRoute);
                    $methodParameters = $method->getParameters();
                    $methodParams = [];
                    foreach ($methodParameters as $methodParameter) {
                        $methodParams[] = $methodParameter->getName();
                    }
                    // 处理路由文件
                    $uri = trim($methodRoute, '/');
                    $uri = preg_quote($uri);
                    // 替换正则表达式
                    $reg = '/\\\{([a-z0-9_]+)\\\}/iU';
                    $uri = preg_replace_callback($reg, function ($match) use ($methodPattern) {
                        return '(?P<' . $match[1] . '>' . ($methodPattern[$match[1]] ?? '[a-z0-9_]+') . ')';
                    }, $uri);
                    // 排序
                    $sortLevelData[] = $methodLevel;
                    $sortLenData[] = mb_strlen($uri, 'utf-8');
                    // 处理缓存
                    if (false === $topCaching && true === $methodCaching) {
                        $methodCaching = false;
                    }
                    $routeData[] = [
                        'class' => $class,
                        'methodName' => $methodName,
                        'methodParams' => $methodParams,
                        'methodLevel' => $methodLevel,
                        'uri' => $uri,
                        'methodPattern' => $methodPattern,
                        'methodHome' => $methodHome,
                        'controller' => $controllerPath,
                        'caching' => $methodCaching
                    ];
                }
            }
            array_multisort($sortLevelData, SORT_DESC, $sortLenData, SORT_DESC, $routeData);
            // 处理主页
            foreach ($routeData as $k => $v) {
                if ($v['methodHome']) {
                    $routeData['home'] = $v;
                    unset($routeData[$k]);
                    break;
                }
            }
            if (empty($routeData['home'])) {
                error('未定义主页路由');
            }
            $this->routeData = $routeData;
            if (createDirByFile($this->routeFile)) {
                if (!file_put_contents($this->routeFile, arrFormatFile($routeData))) {
                    error('路由文件保存失败');
                }
            } else {
                error('无法创建路由文件');
            }
        } catch (ReflectionException $e) {
            error('路由文件生成失败');
        }
    }

    /**
     * 返回所有的路由
     * @return array
     */
    public function getRouteData(): array
    {
        return $this->routeData;
    }
}