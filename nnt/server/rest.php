<?php

namespace Nnt\Server;

use Nnt\Core\ClassT;
use Nnt\Core\MapT;
use Nnt\Logger\Logger;

class Rest extends Server implements IRouterable, IHttpServer, IConsoleServer
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
    protected $_hdl;

    function start(callable $cb)
    {
        $hdl = new \Swoole\Http\Server($this->listen ? $this->listen : "0.0.0.0", $this->port);
        $hdl->on('request', function (\Swoole\Http\Request $req, \Swoole\Http\Response $rsp) {
            $this->doWorker($req, $rsp);
        });
        $hdl->start();
    }

    protected function doWorker(\Swoole\Http\Request $req, \Swoole\Http\Response $rsp)
    {
        // 打开跨域支持
        $rsp->header("Access-Control-Allow-Origin", "*");
        $rsp->header("Access-Control-Allow-Credentials", "true");
        $rsp->header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");

        // 直接对option进行成功响应
        if ($req->server["request_method"] == "OPTIONS") {
            if (isset($req->header["access-control-request-headers"]))
                $rsp->header("Access-Control-Allow-Headers", $req->header["access-control-request-headers"]);
            if (isset($req->header["access-control-request-method"]) && $req->header["access-control-request-method"] == "POST")
                $rsp->header("Content-Type", "multipart/form-data");
            $rsp->status(204);
            $rsp->end();
            return;
        }

        // 处理url请求
        Logger::Log($req->server["request_uri"] . "?" . $req->server["query_string"]);

        // 合并post、get请求
        $params = MapT::Merge($req->get, $req->post, $req->files);

        $this->invoke($params, $req, $rsp);
    }

    function invoke($params, $req, $rsp)
    {
        
    }

    protected $_routers;

    function routers(): Routers
    {
        return $this->_routers;
    }
}