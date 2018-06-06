<?php

namespace Nnt\Core;

class ActionInfo
{
    // 动作名称
    public $name;

    // 绑定的模型类型
    public $clazz;

    // 限制debug可用
    public $debug;

    // 限制develop可用
    public $develop;

    // 限制local可用
    public $local;

    // 限制devops可用
    public $devops;

    // 限制devopsdevelop可用
    public $devopsdevelop;

    // 限制devopsrelease可用
    public $devopsrelease;

    // 注释
    public $comment;

    // 暴露接口
    public $expose;
}

class RouterInfo
{
    public $actions = [];
}

class Router
{
    private static $_clazzes = [];

    /**
     * 解析路由的定义信息
     * @param $obj
     * @return RouterInfo 模型信息
     */
    static function Get($obj): RouterInfo
    {
        $clazz = is_object($obj) ? get_class($obj) : $obj;
        if ($clazz === false)
            return null;
        if (isset(self::$_clazzes[$clazz]))
            return self::$_clazzes[$clazz];
        $info = self::ParseClass($clazz);
        self::$_clazzes[$clazz] = $info;
        return $info;
    }

    static function ParseClass($clazz): RouterInfo
    {
        $reflect = new \ReflectionClass($clazz);
        $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);

        return null;
    }
}