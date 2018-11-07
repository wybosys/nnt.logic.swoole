<?php

namespace Nnt\Server;

abstract class Server
{
    /**
     * @var 服务器的配置id
     */
    public $id;

    /**
     * 配置服务
     * @param $cfg
     * @return bool
     */
    function config($cfg): bool
    {
        if (!isset($cfg->id))
            return false;
        $this->id = $cfg->id;
        return true;
    }

    // 启动服务
    function start()
    {
        $this->onStart();
    }

    protected function onStart()
    {
    }
}