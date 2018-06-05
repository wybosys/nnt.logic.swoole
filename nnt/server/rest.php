<?php

namespace Nnt\Server;

use Nnt\Core\ClassT;
use Nnt\Logger\Logger;

class Rest extends Server implements IRouterable, IHttpServer
{
    function __construct()
    {
        $this->_routers = new Routers();
    }

    // 用来构造请求事物的类型
    protected function instanceTransaction(): Transaction
    {
        return new EmptyTransaction();
    }

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;

        if (!isset($cfg->port))
            return false;
        $this->listen = null;
        if (isset($cfg->listen) && $cfg->listen != "*")
            $this->listen = $cfg->listen;
        $this->port = $cfg->port;
        if (isset($cfg->router)) {
            if (is_array($cfg->router)) {
                foreach ($cfg->router as $e) {
                    $router = ClassT::Instance(ClassT::Entry2Class($e));
                    if (!$router) {
                        Logger::Warn("没有找到该实例类型 $e");
                        return false;
                    } else {
                        $this->_routers->register($router);
                    }
                }
            } else {
                foreach ($cfg->router as $e => $subcfg) {
                    $router = ClassT::Instance(ClassT::Entry2Class($e));
                    if (!$router || (isset($router->config) && !$router->config($subcfg))) {
                        Logger::Warn("没有找到该实例类型 $e");
                        return false;
                    } else {
                        $this->_routers->register($router);
                    }
                }
            }
        }
        $this->router = $cfg->router;
        return true;
    }

    public $listen;
    public $port;
    public $router;

    function start(callable $cb)
    {

    }

    protected $_routers;

    function routers(): Routers
    {
        return $this->_routers;
    }
}