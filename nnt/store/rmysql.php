<?php

namespace Nnt\Store;

use Nnt\Core\MultiMap;
use Nnt\Logger\Logger;

const DEFAULT_PORT = 3306;

class RMysql extends Rdb
{
    // 主机名
    public $host;
    public $port;

    // 使用sock文件来连接
    public $sock;

    // 用户名、密码
    public $user;
    public $pwd;
    public $scheme;

    function clone()
    {
        $ret = new RMysql();
        $ret->id = $this->id;
        $ret->host = $this->host;
        $ret->port = $this->port;
        $ret->sock = $this->sock;
        $ret->user = $this->user;
        $ret->pwd = $this->pwd;
        $ret->scheme = $this->scheme;
        return $ret;
    }

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        $this->user = $cfg->user;
        $this->pwd = $cfg->pwd;
        $this->scheme = $cfg->scheme;
        $this->host = $this->sock = null;
        if (strpos($cfg->host, 'unix://') !== false) {
            $this->sock = $cfg->host;
        } else {
            $p = explode(':', $cfg->host);
            if (count($p) == 1) {
                $this->host = $cfg->host;
                $this->port = DEFAULT_PORT;
            } else {
                $this->host = p[0];
                $this->port = (int)p[1];
            }
        }
        return true;
    }

    /**
     * @var \Swoole\Coroutine\Mysql
     */
    protected $_hdl;

    function open()
    {
        // 只有运行于协程中才能打开
        // 一般除了初始化时，其他使用数据库的时机均位于协程之中，所以没有运行于协程时同样认为是成功
        if (\Swoole\Coroutine::getuid() == -1) {
            Logger::Info("启动 $this->id@mysql");
            return;
        }

        $hdl = new \Swoole\Coroutine\Mysql();

        $cfg = [
            'database' => $this->scheme,
            'charset' => 'utf8'
        ];
        if ($this->host) {
            $cfg['host'] = $this->host;
            $cfg['port'] = $this->port;
        } else if ($this->sock) {
            $cfg['host'] = $this->sock;
        }
        if ($this->user) {
            $cfg['user'] = $this->user;
            $cfg['password'] = $this->pwd;
        }

        try {
            $hdl->connect($cfg);
        } catch (\Throwable $err) {
            Logger::Exception($err);
            $hdl = null;
        }

        $this->_hdl = $hdl;
    }

    function query($cmd)
    {
        $cmd = $this->_hdl->escape($cmd);
        return $this->_hdl->query($cmd);
    }

    function begin()
    {
        $this->_hdl->begin();
    }

    function complete()
    {
        $this->_hdl->commit();
    }

    function cancel()
    {
        $this->_hdl->rollback();
    }

    function pool()
    {
        global $POOLS;
        $h = $POOLS->pop($this->id);
        if (!$h) {
            $h = $this->clone();
            $h->open();
            $POOLS->push($this->id, $h);
        }
        return $h;
    }

    function repool()
    {
        global $POOLS;
        $POOLS->push($this->id, $this);
    }
}

global $POOLS;
$POOLS = new MultiMap(true);