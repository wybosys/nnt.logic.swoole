<?php

namespace Nnt\Server;

use Nnt\Core\STATUS;

abstract class Transaction
{
    function __construct()
    {

    }

    // 返回事务用来区分客户端的id，通常业务中实现为sid
    abstract function sessionId(): string;

    // 获得同意个sid之下的客户端的id，和sid结合起来保证唯一性，即 sid.{cid}
    function clientId(): string
    {
        return $this->params["_cid"];
    }

    // client的访问地址
    public $clientAddress;
    public $clientPort;

    // 动作
    private $_action;

    function action(): string
    {
        return $this->_action;
    }

    function setAction($act)
    {
        $this->_action = $act;
        $p = explode(".", $this->_action);
        $this->router = strtolower((p[0] || "null"));
        $this->call = strtolower((p[1] || "null"));
    }

    // 映射到router的执行器中
    public $router;
    public $call;

    // 参数
    public $params;

    // 执行的结果
    public $status = STATUS::UNKNOWN;

    // 错误信息
    public $message;

    // 额外数据
    public $payload;

    // 输出和输入的model
    public $model;

    // 基于哪个服务器运行
    public $server;

}