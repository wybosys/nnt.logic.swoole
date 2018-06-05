<?php

namespace Nnt\Server;

class Rest extends Server implements IRouterable, IHttpServer
{
    function __construct()
    {
        $this->_routers = new Routers();
    }

    function start(callable $cb)
    {

    }

    protected $_routers;

    function routers(): Routers
    {
        return $this->_routers;
    }
}