<?php

namespace Nnt\Server;

use Nnt\Logger\Logger;

class Logic extends Server
{
    /**
     * @var string 服务器的地址
     */
    public $host;

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        if (!isset($cfg->host))
            return false;
        $this->host = $cfg->host;
        return true;
    }

    function start()
    {
        Logger::Info("启动 $this->id@logic");
    }

    function stop()
    {
        // pass
    }
}
