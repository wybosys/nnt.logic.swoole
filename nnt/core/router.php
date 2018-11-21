<?php

namespace Nnt\Core;

include_once __DIR__ . "/proto_global.php";

use Nnt\Logger\Logger;

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
    public $debug = false;

    /**
     * 不导出
     * @var boolean
     */
    public $noexport = false;

    /**
     * 限制develop可用
     * @var boolean
     */
    public $develop = false;

    /**
     * 限制local可用
     * @var boolean
     */
    public $local = false;

    /**
     * 限制devops可用
     * @var boolean
     */
    public $devops = false;

    /**
     * 限制devopsdevelop可用
     * @var boolean
     */
    public $devopsdevelop = false;

    /**
     * 限制devopsrelease可用
     * @var boolean
     */
    public $devopsrelease = false;

    /**
     * 注释
     * @var string
     */
    public $comment = '';

    /**
     * 暴露接口
     * @var boolean
     */
    public $expose = false;

    /**
     * 缓存时间, =0 代表不缓存，>0代表缓存的时间秒
     * @var boolean
     */
    public $cachetime = 0;
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

function action_($model, $options = null, $comment = ''): ActionInfo
{
    $ret = new ActionInfo();
    $ret->model = $model;
    if (is_string($options)) {
        $comment = $options;
        $options = null;
    }
    if ($options) {
        foreach ($options as $option) {
            switch ($option) {
                case 'debug':
                    $ret->debug = true;
                    break;
                case 'develop':
                    $ret->develop = true;
                    break;
                case 'local':
                    $ret->local = true;
                    break;
                case 'devops':
                    $ret->devops = true;
                    break;
                case 'devops-develop':
                case 'devopsdevelop':
                    $ret->devopsdevelop = true;
                    break;
                case 'devops-release':
                case 'devopsrelease':
                    $ret->devopsrelease = true;
                    break;
                case 'expose':
                    $ret->expose = true;
                    break;
                case 'noexport':
                    $ret->noexport = true;
                    break;
                default:
                    if (strpos($option, 'cache') !== false) {
                        if (preg_match('/cache_(\d+)/', $option, $res) === false) {
                            Logger::Warn("缓存配置错误 " . $option);
                        } else {
                            $ret->cachetime = (int)$res[1];
                        }
                    }
                    break;
            }
        }
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
            eval("\$res = call_user_func('\Nnt\Core\action_', '$matches[1]' $matches[2]);");

            $res->name = $method->name;
            $ret->actions[$res->name] = $res;
        }
        return $ret;
    }
}
