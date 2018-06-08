<?php

namespace Nnt\Store;

use Nnt\Core\ObjectT;
use Nnt\Core\Tuple;
use Nnt\Logger\Logger;
use Nnt\Manager\Dbmss;

class Transaction
{
    function __construct($def)
    {
        if (Tuple::IsTuple($def)) {
            if (ObjectT::IsClass($def["0"])) {
                $this->clazz = $def["1"];
                if (!$this->_parseClass($def[1]))
                    return;
                $this->dbid = $def["0"];
            } else if (ObjectT::IsClass($def["1"])) {
                $this->clazz = $def["0"];
                if (!$this->_parseStorePath($def["1"]))
                    return;
            } else {
                $this->clazz = $def["0"];
                if (!$this->_parseClass($def["1"]))
                    return;
            }
        } else {
            $this->clazz = $def;
            if (!$this->_parseClass($def))
                return;
        }

        // 查找对应的数据库连接
        $db = Dbmss::Find($this->dbid);
        if ($db == null) {
            Logger::Fatal("没有找到 $this->dbid 对应的数据库");
            return;
        }

        $this->_db = $db;
        if ($db instanceof Rdb)
            $this->_rdb = $db;
        else if ($db instanceof NoSql)
            $this->_nosql = $db;
        else if ($db instanceof Kv)
            $this->_kv = $db;
    }

    protected function _parseStorePath($sp)
    {
        $ps = explode('.', $sp);
        if (count($ps) != 2)
            return false;
        $this->dbid = $ps[0];
        $this->table = $ps[1];
        return true;
    }

    protected function _parseClass($clz)
    {
        $ti = Proto::Get($clz);
        if (!$ti) {
            Logger::Fatal("$clz 不是有效的数据库模型");
            return false;
        }
        $this->dbid = $ti->id;
        $this->table = $ti->table;
        return true;
    }

    // 传入的类
    public $clazz;

    // 表名
    public $dbid;
    public $table;

    // 生成对象
    function produce($res)
    {
        if ($this->clazz) {
            $r = new $this->clazz();
            Proto::Decode($r, $res);
            return $r;
        }
        return null;
    }

    // 数据库连接

    /**
     * @var Dbms
     */
    protected $_db;

    /**
     * @var Rdb
     */
    protected $_rdb;

    protected $_nosql;
    protected $_kv;

    // 针对不同数据库的实现
    public $dbproc;
    public $rdbproc;
    public $nosqlproc;
    public $kvproc;

    function run()
    {
        $db = $this->_db->pool();
        $this->doRun($db);
        $db->repool();
    }

    protected function doRun($db)
    {
        try {
            if ($this->_rdb) {
                if ($this->rdbproc)
                    return ($this->rdbproc)($db);
                else
                    return null;
            } else if ($this->_nosql) {
                if ($this->nosqlproc)
                    return ($this->nosqlproc)($db);
                else
                    return null;
            } else if ($this->_kv) {
                if ($this->kvproc)
                    return ($this->kvproc)($db);
                else
                    return null;
            } else if ($this->_db) {
                if ($this->dbproc)
                    return ($this->dbproc)($db);
                else
                    return null;
            } else {
                Logger::Warn("DBMS没有处理此次数据请求");
                return null;
            }
        } catch (\Throwable $err) {
            Logger::Exception($err);
            return null;
        }
    }

}