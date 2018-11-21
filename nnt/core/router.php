<?php

namespace Nnt\Core;

/**
 * Class ActionInfo 可以调用的动作信息
 */
class ActionInfo
{
    /**
     * 动作名称
     * @var string
     */
    public $name;

    /**
     * 绑定的模型类型
     * @var string
     */
    public $model;

    /**
     * 限制debug可用
     * @var boolean
     */
    public $debug;

    /**
     * 不导出
     * @var boolean
     */
    public $noexport;

    /**
     * 限制develop可用
     * @var boolean
     */
    public $develop;

    /**
     * 限制local可用
     * @var boolean
     */
    public $local;

    /**
     * 限制devops可用
     * @var boolean
     */
    public $devops;

    /**
     * 限制devopsdevelop可用
     * @var boolean
     */
    public $devopsdevelop;

    /**
     * 限制devopsrelease可用
     * @var boolean
     */
    public $devopsrelease;

    /**
     * 注释
     * @var string
     */
    public $comment;

    /**
     * 暴露接口
     * @var boolean
     */
    public $expose;
}

/**
 * Class RouterInfo 路由对象信息
 */
class RouterInfo
{
    /**
     * @var array ActionInfo
     * Map<string, ActionInfo>
     */
    public $actions = [];

    /**
     * @return ActionInfo
     */
    function find($name)
    {
        return ObjectT::Get($this->actions, $name);
    }

    /**
     * @return array string
     */
    function names()
    {
        return array_keys($this->actions);
    }

    /**
     * #return array ActionInfo
     */
    function infos()
    {
        return array_values($this->actions);
    }
}

function action($model, $options = null, $comment = null): ActionInfo
{
    $ret = new ActionInfo();
    $ret->model = $model;
    if (is_string($options)) {
        $comment = $options;
        $options = null;
    }
    if ($options) {
        $ret->debug = in_array('debug', $options);
        $ret->develop = in_array('develop', $options);
        $ret->local = in_array('local', $options);
        $ret->devops = in_array('devops', $options);
        $ret->devopsdevelop = in_array('devopsdevelop', $options);
        $ret->devopsrelease = in_array('devopsrelease', $options);
        $ret->expose = in_array('expose', $options);
        $ret->noexport = in_array('noexport', $options);
    }
    $ret->comment = $comment;
    return $ret;
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
        $ret = new RouterInfo();
        $reflect = new \ReflectionClass($clazz);
        $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $plain = $method->getDocComment();

            // 处理action开头的注释
            if (!preg_match('/@action\(([a-zA-Z\\\\]+)(.*)\)/', $plain, $matches))
                continue;
            // 直接执行注释函数
            $res = null;
            eval("\$res = call_user_func('\Nnt\Core\action', '$matches[1]' $matches[2]);");

            $res->name = $method->name;
            $ret->actions[$res->name] = $res;
        }
        return $ret;
    }
}
