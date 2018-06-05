<?php

namespace Nnt\Server\Apidoc;

use Nnt\Core\IRouter;

class Router implements IRouter
{
    function action(): string
    {
        return "api";
    }
}