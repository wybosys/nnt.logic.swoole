<?php

namespace App;

use Nnt\Server\Transaction;
use Nnt\Store\KvRedis;
use Nnt\Store\RMysql;

class Trans extends Transaction
{
    function auth(): bool
    {
        return false;
    }

    function sessionId(): string
    {
        return null;
    }

    private $_kv;

    function kv(): KvRedis
    {
        if (!$this->_kv) {
            $this->_kv = $this->db("kv");
        }
        return $this->_kv;
    }

    private $_mysql;

    function mysql(): RMysql
    {
        if (!$this->_mysql) {
            $this->_mysql = $this->db("mysql");
        }
        return $this->_mysql;
    }
}
