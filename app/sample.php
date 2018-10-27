<?php

namespace App;

use Nnt\Server\Rest;

class Sample extends Rest
{

    function __construct()
    {
        parent::__construct();
        $this->routers()->register(new \App\Router\Sample());
    }
}
