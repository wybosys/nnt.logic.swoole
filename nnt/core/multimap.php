<?php

namespace Nnt\Core;

class MultiMap
{
    private $_obj = [];
    private $_lock;

    function __construct($threadsafe = false)
    {
        if ($threadsafe)
            $this->_lock = new \Swoole\Lock(SWOOLE_MUTEX);
    }

    function set($key, $obj): MultiMap
    {
        $this->lock();
        $arr = @$this->_obj[$key];
        if ($arr === null) {
            $arr = [];
            $this->_obj[$key] = $arr;
        }
        $arr[] = $obj;
        $this->unlock();
        return $this;
    }

    function push($key, $obj): MultiMap
    {
        $this->lock();
        $ret = $this->set($key, $obj);
        $this->unlock();
        return $ret;
    }

    function pop($key)
    {
        $this->lock();
        $arr = @$this->_obj[$key];
        if (!$arr)
            return null;
        $ret = array_pop($arr);
        $this->unlock();
        return $ret;
    }

    protected function lock()
    {
        $this->_lock && $this->_lock->lock();
    }

    protected function unlock()
    {
        $this->_lock && $this->_lock->unlock();
    }
}