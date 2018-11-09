<?php

namespace Nnt\Server;

use Nnt\Core\DateTime;
use Nnt\Core\IRouter;
use Nnt\Core\ObjectT;
use Nnt\Core\Proto;
use Nnt\Core\Router;
use Nnt\Core\STATUS;
use Nnt\Logger\Logger;
use Nnt\Manager\Config;
use Nnt\Manager\Dbmss;

// 避免与定义的基础模型找不到
include_once DIR_NNT . '/core/models.php';

abstract class Transaction
{
    function __construct()
    {
        $this->time = DateTime::Now();
        $this->info = new TransactionInfo();
        $this->waitTimeout();
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
        $this->router = strtolower(ObjectT::Get($p, 0, "null"));
        $this->call = strtolower(ObjectT::Get($p, 1, "null"));
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

    // 是否是压缩数据
    public $gzip;

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

    // 输出文件
    public $implOutput;
    private $_outputed;

    // 事务结束
    protected function onCompleted()
    {
        if ($this->_dbs) {
            foreach ($this->_dbs as $db) {
                $db->repool();
            }
            $this->_dbs = null;
        }
    }

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
        $this->onCompleted();
    }

    function output(string $type, $obj)
    {
        if ($this->_outputed) {
            Logger::Warn("api已经发送");
            return;
        }
        if ($this->_timeout) {
            swoole_timer_clear($this->_timeout);
            $this->_timeout = null;
        }
        $this->_outputed = true;
        $this->_submited = true;
        ($this->implOutput)($this, $type, $obj);
        $this->onCompleted();
    }

    // 是否把sid返回客户端
    public $responseSessionId = false;

    function modelize(IRouter $r): int
    {
        $ri = Router::Get($r);
        $ai = @$ri->find($this->call);
        if (!$ai)
            return STATUS::ACTION_NOT_FOUND;
        $this->expose = $ai->expose;

        // 动作依赖的模型
        $clz = $ai->clazz;

        // 检查输入参数
        $sta = Proto::CheckInputStatus($clz, $this->params);
        if ($sta != STATUS::OK)
            return $sta;

        // 填入数据到模型
        $this->model = new $clz();
        try {
            Proto::Decode($this->model, $this->params);
        } catch (\Throwable $err) {
            $this->model = null;
            Logger::Fatal($err->getMessage());
            return STATUS::MODEL_ERROR;
        }

        return STATUS::OK;
    }

    // 恢复上下文，涉及到数据的恢复，所以是异步模式
    function collect()
    {
        // 重载实现具体的业务数据收集
    }

    // 验证
    function needAuth(): bool
    {
        $mi = Proto::Get($this->model);
        return $mi->auth;
    }

    private $_dbs = null;

    /**
     * 拿到对应的数据库操作，事务结束后会自动回收
     * @param $id app.json中的数据库配置id
     * @return mixed
     */
    function db(string $id)
    {
        $fnd = Dbmss::Find($id);
        if (!$fnd)
            return null;
        $db = $fnd->pool();
        if ($this->_dbs == null)
            $this->_dbs = [$db];
        else
            $this->_dbs[] = $db;
        return $db;
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

    // 其他http参数
    public $headers;
    public $servers;
    public $gets;
    public $posts;
    public $requests;
}
