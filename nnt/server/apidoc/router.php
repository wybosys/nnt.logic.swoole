<?php

namespace Nnt\Server\Apidoc;

use Nnt\Core\IRouter;
use Nnt\Server\Transaction;

class Router implements IRouter
{
    function action(): string
    {
        return "api";
    }

    /**
     * @action(\Nnt\Core\Nil, [], "文档")
     */
    function doc(Transaction $trans)
    {
        $trans->submit();
    }
}