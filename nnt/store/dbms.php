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

    // 事务处理
    function begin()
    {
    }

    function complete()
    {
    }

    function cancel()
    {
    }
}