<?php

namespace library\mysmarty;

/**
 * 单一实例
 */
class Container
{
    // 保存单一实例变量的数组
    private static array $_instance = [];

    /**
     * 获取单一实例
     * @return static
     */
    final public static function getInstance(): static
    {
        $class = static::class;
        if (false === array_key_exists($class, static::$_instance)) {
            static::$_instance[$class] = new static;
            static::$_instance[$class]->_initialize();
        }
        return static::$_instance[$class];
    }

    /**
     * 实例化后，调用初始化
     */
    public function _initialize()
    {
    }
}