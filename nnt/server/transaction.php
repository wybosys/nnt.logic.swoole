<?php

namespace Nnt\Server;

use Nnt\Core\DateTime;
use Nnt\Core\IRouter;
use Nnt\Core\STATUS;
use Nnt\Logger\Logger;
use Nnt\Manager\Config;

abstract class Transaction
{
    function __construct()
    {
        $this->time = DateTime::Now();
        $this->info = new TransactionInfo();
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

    // 是否暴露接口（通常只有登录会设置为true)
    public $expose;

    // 此次的时间
    public $time;

    // 是否已经授权
    abstract function auth(): bool;

    /**
     * 环境信息
     * @var TransactionInfo
     */
    public $info;

    // 同步模式会自动提交，异步模式需要手动提交
    public $implSubmit;
    private $_submited;
    private $_submited_timeout;
    private $_timeout;

    protected function waitTimeout()
    {
        $this->_timeout = swoole_timer_after(Config::$TRANSACTION_TIMEOUT * 1000, function () {
            $this->_cbTimeout();
        });
    }

    private function _cbTimeout()
    {
        Logger::Warn("$this->_action 超时");
        $this->status = STATUS::TIMEOUT;
        $this->submit();
    }

    // 部分api本来时间就很长，所以存在自定义timeout的需求
    function timeout($seconds)
    {
        if ($this->_timeout) {
            swoole_timer_clear($this->_timeout);
            $this->_timeout = null;
        }
        if ($seconds == -1)
            return;
        $this->_timeout = swoole_timer_after($seconds * 1000, function () {
            $this->_cbTimeout();
        });
    }

    // 当提交的时候修改
    public $hookSubmit;

    function submit(TransactionSubmitOption $opt = null)
    {
        if ($this->_submited) {
            if (!$this->_submited_timeout)
                Logger::Warn("数据已经发送");
            return;
        }
        if ($this->_timeout) {
            swoole_timer_clear($this->_timeout);
            $this->_timeout = null;
            $this->_submited_timeout = true;
        }
        $this->_submited = true;
        $this->_outputed = true;
        if ($this->hookSubmit) {
            try {
                ($this->hookSubmit)();
            } catch (\Throwable $err) {
                Logger::Exception($err);
            }
        }
        ($this->implSubmit)($this, $opt);
    }

    // 是否把sid返回客户端
    public $responseSessionId = false;

    function modelize(IRouter $r): int
    {
        return STATUS::OK;
    }

    // 恢复上下文，涉及到数据的恢复，所以是异步模式
    function collect()
    {

    }

    // 验证
    function needAuth(): bool
    {
        return false;
    }

}

class EmptyTransaction extends Transaction
{

    function waitTimeout()
    {
        // pass
    }

    function sessionId(): string
    {
        return null;
    }

    function auth(): bool
    {
        return false;
    }
}

class TransactionInfo
{
    // 客户端
    public $agent;

    // 访问的主机
    public $host;
    public $origin;

    // 客户端的地址
    public $addr;

    // 来源
    public $referer;
    public $path;
}

class TransactionSubmitOption
{
    // 仅输出模型
    public $model;

    // 直接输出数据
    public $raw;

    // 输出的类型
    public $type;
}
