<?php

namespace Nnt\Server;

use Nnt\Core\ClassT;
use Nnt\Core\DateTime;
use Nnt\Core\MapT;
use Nnt\Core\STATUS;
use Nnt\Logger\Logger;
use Nnt\Render\Render;

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

    /**
     * @param $params
     * @param $req \Swoole\Http\Request
     * @param $rsp \Swoole\Http\Response
     */
    function invoke($params, $req, $rsp)
    {
        if (!isset($params["action"])) {
            $rsp->status(400);
            $rsp->end();
            return;
        }

        $action = $params["action"];
        $t = $this->instanceTransaction();
        try {
            $t->server = $this;
            $t->params = $params;
            $t->setAction($action);

            // 从请求中保存下信息
            if ($req) {
                if (isset($params["_agent"]))
                    $t->info->agent = $params["_agent"];
                else
                    $t->info->agent = $req->header["user-agent"];
                $t->info->host = $req->header["host"];
                $t->info->addr = $req->server["remote_addr"];
                $t->info->path = $req->server["path_info"];
            }

            $this->onBeforeInvoke($t);
            $this->doInvoke($t, $params, $req, $rsp);
            $this->onAfterInvoke($t);
        } catch (\Throwable $err) {
            Logger::Exception($err);
            $t->status = STATUS::EXCEPTION;
            $t->submit();
        }
    }

    protected $_routers;

    function routers(): Routers
    {
        return $this->_routers;
    }

    protected function onBeforeInvoke(Transaction $trans)
    {
    }

    protected function onAfterInvoke(Transaction $trans)
    {
    }

    protected function doInvoke(Transaction $t, $params, \Swoole\Http\Request $req, \Swoole\Http\Response $rsp)
    {
        $t->payload = new RestTransactionPayload($req, $rsp);
        $t->implSubmit = function ($t, $opt) {
            TransactionSubmit($t, $opt);
        };
        $t->implOutput = function ($t, $type, $obj) {
            TransactionOutput($t, $type, $obj);
        };
        $this->_routers->process($t);
    }
}

const RESPONSE_SID = "X-NntLogic-SessionId";

function TransactionSubmit(Transaction $t, TransactionSubmitOption $opt = null)
{
    $pl = $t->payload;
    $render = Render::Find(isset($t->params["render"]) ? $t->params["render"] : "json");
    if ($t->responseSessionId)
        $pl->rsp->header(RESPONSE_SID, $t->sessionId());
    $pl->rsp->status(200);
    $pl->rsp->header("Content-Type", ($opt && $opt->type) ? $opt->type : $render->type());
    $pl->rsp->end($render->render($t, $opt));
}

function TransactionOutput(Transaction $t, string $type, $obj)
{
    $pl = $t->payload;
    $pl->rsp->header("Content-Type", $type);
    if ($t->gzip)
        $pl->rsp->header("Content-Encoding", "gzip");
    if ($obj instanceof RespFile) {
        $pl->rsp->header("Content-Length", (string)$obj->length());
        if ($obj->cachable()) {
            // 只有文件对象才可以增加过期控制
            if (@$pl->req->header["if-modified-since"]) {
                // 判断下请求的文件有没有改变
                if ($obj->stat('mtime') == $pl->req->header["if-modified-since"]) {
                    $pl->rsp->status(304);
                    $pl->rsp->end("Not Modified");
                    return;
                }
            }
            $pl->rsp->header("Expires", $obj->expire());
            $pl->rsp->header("Cache-Control", "max-age=" . DateTime::WEEK);
            $pl->rsp->header("Last-Modified", $obj->stat('mtime'));
        }
        // 如果是提供下载
        if ($obj->download()) {
            $pl->rsp->header('Accept-Ranges', 'bytes');
            $pl->rsp->header('Accept-Length', $obj->length());
            $pl->rsp->header('Content-Disposition', 'attachment; filename=' . $obj->file());
            $pl->rsp->header('Content-Description', "File Transfer");
            $pl->rsp->header('Content-Transfer-Encoding', 'binary');
        }
        $pl->rsp->status(200);
        $pl->rsp->sendfile($obj->file());
    } else {
        $pl->rsp->status(200);
        $pl->rsp->end((string)$obj);
    }
}

class RestTransactionPayload
{
    function __construct(\Swoole\Http\Request $req, \Swoole\Http\Response $rsp)
    {
        $this->req = $req;
        $this->rsp = $rsp;
    }

    /**
     * @var \Swoole\Http\Request
     */
    public $req;

    /**
     * @var \Swoole\Http\Response
     */
    public $rsp;
}