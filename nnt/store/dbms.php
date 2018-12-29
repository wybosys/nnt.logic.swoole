<?php

namespace Nnt\Store;

abstract class Dbms
{
    /**
     * 唯一标记
     * @var string
     */
    public $id;

    // 配置
    function config($cfg): bool
    {
        $this->id = $cfg->id;
        return true;
    }

    // 打开连接
    abstract function open();

    // 关闭连接
    abstract function close();

    /**
     * 复制当前配置
     * @return Dbms
     */
    abstract function clone();

    // 事务处理
    function begin()
    {
        // pass
    }

    function complete()
    {
        // pass
    }

    function cancel()
    {
        // pass
    }
}
