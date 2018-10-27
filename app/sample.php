<?php

namespace App;

use Nnt\Server\Rest;
use Nnt\Server\Transaction;

class Sample extends Rest
{

    function __construct()
    {
        parent::__construct();
        $this->routers()->register(new \App\Router\Sample());
    }

    protected function instanceTransaction(): Transaction
    {
        return new Trans();
    }
}
