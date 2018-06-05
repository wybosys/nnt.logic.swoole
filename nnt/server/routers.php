<?php

namespace Nnt\Server;

use Nnt\Core\IRouter;
use Nnt\Logger\Logger;

interface IRouterable
{
    function routers(): Routers;
}

class Routers
{
    protected $_routers = [];

    function length()
    {
        return count($this->_routers);
    }

    function register(IRouter $obj)
    {
        $actnm = $obj->action();
        if (in_array($actnm, $this->_routers)) {
            Logger::Fatal("已经注册了一个同名的路由 $actnm");
            return;
        }
        $this->_routers[$actnm] = $obj;
    }

    function find($id)
    {
        return isset($this->_routers[$id]) ? $this->_routers[$id] : null;
    }
}
