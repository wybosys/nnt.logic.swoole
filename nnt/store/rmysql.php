<?php

namespace Nnt\Store;

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
        go(function () {
            $this->doOpen();
        });
    }

    protected function doOpen()
    {
        if ($this->_hdl)
            return;

        $this->_hdl = new \Swoole\Coroutine\Mysql();
        $cfg = [
            'database' => $this->scheme,
            'charset' => 'utf8',
            'timeout' => 5
        ];
        if ($this->host) {
            $cfg['host'] = $this->host;
            $cfg['port'] = $this->port;
        } else if ($this->sock) {
            $cfg['host'] = $this->host;
        }
        if ($this->user) {
            $cfg['user'] = $this->user;
            $cfg['password'] = $this->pwd;
        }
        try {
            //$this->_hdl->connect($cfg);
            Logger::Info("启动 mysql@$this->id");
        } catch (\Throwable $err) {
            Logger::Fatal("启动失败 mysql@$this->id");
        }
    }

    function query($cmd)
    {
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
}