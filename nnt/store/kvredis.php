<?php

namespace Nnt\Store;

use Nnt\Core\MultiMap;
use Nnt\Core\STATUS;
use Nnt\Core\Variant;
use Nnt\Logger\Logger;

class KvRedis extends Kv
{
    const DEFAULT_PORT = 6379;

    public $dbid;
    public $host;
    public $port;
    public $passwd;
    public $timeout = 1;
    public $retry = 0.1;
    public $cluster = false;
    public $prefix;

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        if (!$cfg->host)
            return false;
        if (isset($cfg->cluster) && $cfg->cluster)
            $this->dbid = 0;
        else
            $this->dbid = isset($cfg->dbid) ? $cfg->dbid : 0;
        if (isset($cfg->prefix))
            $this->prefix = $cfg->prefix;
        if (isset($cfg->cluster) && $cfg->cluster)
            $this->cluster = true;
        $arr = explode(':', $cfg->host);
        $this->host = $arr[0];
        $this->port = count($arr) == 2 ? (int)$arr[1] : self::DEFAULT_PORT;
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
        $ret->prefix = $this->prefix;
        $ret->cluster = $this->cluster;
        return $ret;
    }

    function open()
    {
        if (\Swoole\Coroutine::getuid() == -1) {
            Logger::Info("启动 $this->id@redis");
        }

        if ($this->cluster) {
            $hdl = new \RedisCluster(NULL, [
                $this->host . ':' . $this->port
            ]);
            if ($this->prefix)
                $hdl->setOption(\RedisCluster::OPT_PREFIX, $this->prefix);
        } else {
            $hdl = new \Redis();
            $res = $hdl->connect($this->host, $this->port, $this->timeout, null, $this->retry * 1000);
            if (!$res) {
                throw new \Exception($hdl->getLastError(), STATUS::EXCEPTION);
            }
            if ($this->prefix)
                $hdl->setOption(\Redis::OPT_PREFIX, $this->prefix);
        }

        $this->_hdl = $hdl;
    }

    function close()
    {
        if ($this->_hdl) {
            $this->_hdl->close();
            $this->_hdl = null;
        }
    }

    /**
     * @var \Redis
     */
    protected $_hdl;

    protected function testopen()
    {
        try {
            $this->_hdl->ping('alive');
        } catch (\Throwable $err) {
            Logger::Log("尝试重新连接 $this->id@redis");
            $this->close();
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

    function get(string $key)
    {
        $v = $this->_hdl->get($key);
        if ($v === false)
            return null;
        return Variant::FromString($v);
    }

    function getraw(string $key)
    {
        $v = $this->_hdl->get($key);
        if ($v === false)
            return null;
        return $v;
    }

    function setTtl(string $key, int $ttl)
    {
        if ($ttl == -1) {
            return $this->_hdl->persist($key);
        }
        return $this->_hdl->expire($key, $ttl);
    }

    function ttl(string $key, bool $inseconds = true)
    {
        if ($inseconds)
            return $this->_hdl->ttl($key);
        return $this->_hdl->pttl($key);
    }

    function set(string $key, Variant $val)
    {
        $jsstr = $val->serialize();
        return $this->_hdl->set($key, $jsstr);
    }

    function setraw(string $key, string $val)
    {
        return $this->_hdl->set($key, $val);
    }

    function getset(string $key, Variant $val)
    {
        $jsstr = $val->serialize();
        $v = $this->_hdl->getSet($key, $jsstr);
        if ($v === false)
            return null;
        return Variant::FromString($v);
    }

    function getsetraw(string $key, string $val)
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

    function lpush(string $key, $val)
    {
        return $this->_hdl->lPush($key, $val);
    }

    function rpush(string $key, $val)
    {
        return $this->_hdl->rPush($key, $val);
    }

    function append(string $key, $val)
    {
        return $this->_hdl->append($key, $val);
    }

    function bitAt(string $key, int $offset): int
    {
        return $this->_hdl->getBit($key, $offset);
    }

    function setbit(string $key, int $offset, $val): int
    {
        return $this->_hdl->setBit($key, $offset, $val);
    }

    function mapSet(string $map, string $key, $value)
    {
        return $this->_hdl->hSet($map, $key, $value);
    }

    function mapGet(string $map, string $key)
    {
        return $this->_hdl->hGet($map, $key);
    }

    function mapSize(string $map): int
    {
        return $this->_hdl->hLen($map);
    }

    function mapDelete(string $map, string $key)
    {
        return $this->_hdl->hDel($map, $key);
    }

    function mapKeys(string $map)
    {
        return $this->_hdl->hKeys($map);
    }

    function mapValues(string $map)
    {
        return $this->_hdl->hVals($map);
    }

    function mapContains(string $map, string $key): bool
    {
        return $this->_hdl->hExists($map, $key);
    }

    function orderSet(string $order, string $val, $score)
    {
        return $this->_hdl->zAdd($order, $score, $val);
    }

    function orderRange(string $order, $start, $end, $scores = null)
    {
        return $this->_hdl->zRange($order, $start, $end, $scores);
    }

    function orderDelete(string $order, string $val)
    {
        return $this->_hdl->zRem($order, $val);
    }

    function orderSize(string $order, string $start, string $end)
    {
        return $this->_hdl->zCount($order, $start, $end);
    }

    function orderGetScore(string $order, string $val)
    {
        return $this->_hdl->zScore($order, $val);
    }

    function orderGetIndex(string $order, string $val)
    {
        return $this->_hdl->zRank($order, $val);
    }

    function orderIncrBy(string $order, string $val, $delta)
    {
        return $this->_hdl->zIncrBy($order, $delta, $val);
    }

    //获取键
    function keys(string $key)
    {
        $v = $this->_hdl->keys($key);
        if ($v === false)
            return null;
        return $v;
    }

    function cacheLoad($key)
    {
        return $this->_hdl->get($key);
    }

    function cacheSave($key, $val, $ttl)
    {
        if ($ttl) {
            return $this->_hdl->setex($key, $ttl, $val);
        }
        return $this->_hdl->set($key, $val);
    }
}

global $POOLS;
$POOLS = new MultiMap(true);
