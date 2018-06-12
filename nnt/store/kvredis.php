<?php

namespace Nnt\Store;

use Nnt\Core\MultiMap;
use Nnt\Core\STATUS;
use Nnt\Core\Variant;
const DEFAULT_PORT = 6379;

class KvRedis extends Kv
{
    public $dbid;
    public $host;
    public $port;
    public $passwd;
    public $timeout = 1;
    public $retry = 0.1;

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        if (!$cfg->host)
            return false;
        if ($cfg->cluster)
            $this->dbid = 0;
        else
            $this->dbid = isset($cfg->dbid) ? $cfg->dbid : 0;
        $arr = explode(':', $cfg->host);
        $this->host = $arr[0];
        $this->port = count($arr) == 2 ? (int)$arr[1] : DEFAULT_PORT;
        $this->passwd = @$cfg->password;
        if (isset($cfg->timeout))
            $this->timeout = $cfg->timeout;
        if (isset($cfg->retry))
            $this->retry = $cfg->retry;
        return true;
    }

    function clone()
    {
        $ret = new KvRedis();
        $ret->dbid = $this->dbid;
        $ret->host = $this->host;
        $ret->port = $this->port;
        $ret->passwd = $this->passwd;
        $ret->timeout = $this->timeout;
        $ret->retry = $this->retry;
        return $ret;
    }

    function open()
    {
        if (\Swoole\Coroutine::getuid() == -1) {
            Logger::Info("启动 $this->id@redis");
            return;
        }

        $hdl = new \Swoole\Coroutine\Redis();
        $res = $hdl->connect($this->host, $this->port, $this->timeout, null, $this->retry * 1000);
        if (!$res) {
            throw new \Exception($hdl->getLastError(), STATUS::EXCEPTION);
            return;
        }

        $this->_hdl = $hdl;
    }

    /**
     * @var \Swoole\Coroutine\Redis
     */
    protected $_hdl;

    protected function testopen()
    {
        try {
            $this->_hdl->ping();
        } catch (\Throwable $err) {
            Logger::Log("尝试重新连接 $this->id@redis");
            $this->open();
        }
    }

    function pool()
    {
        global $POOLS;
        $h = $POOLS->pop($this->id);
        if (!$h) {
            $h = $this->clone();
            $h->open();
            $POOLS->push($this->id, $h);
        } else {
            $h->testopen();
        }
        return $h;
    }

    function repool()
    {
        global $POOLS;
        $POOLS->push($this->id, $this);
    }

    function get(string $key): Variant
    {
        $v = $this->_hdl->get($key);
        if ($v === false)
            return null;
        return Variant::FromString($v);
    }

    function getraw(string $key): string
    {
        $v = $this->_hdl->get($key);
        if ($v === false)
            return null;
        return $v;
    }

    function set(string $key, Variant $val): bool
    {
        $jsstr = $val->serialize();
        return $this->_hdl->set($key, $jsstr);
    }

    function getset(string $key, Variant $val): Variant
    {
        $jsstr = $val->serialize();
        $v = $this->_hdl->getSet($key, $jsstr);
        if ($v === false)
            return null;
        return Variant::FromString($v);
    }

    function getsetraw(string $key, string $val): string
    {
        $v = $this->_hdl->getSet($key, $val);
        if ($v === false)
            return null;
        return $v;
    }

    function del(string $key): DbExecuteStat
    {
        $ret = $this->_hdl->del($key);
        $r = new DbExecuteStat();
        $r->remove = $ret;
        return $r;
    }

    // kv数据库通常没有自增函数，所以需要各个业务类自己实现
    function autoinc(string $key, $delta)
    {
        if ($delta == 1) {
            $ret = $this->_hdl->incr($key);
        } else {
            $ret = $this->_hdl->incrBy($key, $delta);
        }
        return $ret;
    }

    // 增加
    function inc(string $key, $delta)
    {
        if ($delta > 0) {
            if ($delta == 1) {
                $ret = $this->_hdl->incr($key);
            } else {
                $ret = $this->_hdl->incrBy($key, $delta);
            }
        } else {
            if ($delta == -1) {
                $ret = $this->_hdl->decr($key);
            } else {
                $ret = $this->_hdl->decrBy($key, -$delta);
            }
        }
        return $ret;
    }
}

global $POOLS;
$POOLS = new MultiMap(true);
