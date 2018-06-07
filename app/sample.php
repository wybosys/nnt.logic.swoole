<?php

namespace App;

use Nnt\Core\DateTime;
use Nnt\Core\IRouter;
use Nnt\Server\Rest;
use Nnt\Server\Transaction;

/**
 * @model()
 */
class Echoo
{
    /**
     * @string(1, [input], "输入")
     */
    public $input;

    /**
     * @string(2, [output], "输出")
     */
    public $output;

    /**
     * @integer(3, [output], "服务器时间")
     */
    public $time;
}

class RSample implements IRouter
{

    function action()
    {
        return "sample";
    }

    /**
     * @action(\App\Echoo)
     */
    function echo(Transaction $trans, Echoo $m)
    {
        $m->output = $m->input;
        $m->time = DateTime::Current();
        $trans->submit();
    }
}

class Sample extends Rest
{

    function __construct()
    {
        parent::__construct();
        $this->routers()->register(new RSample());
    }
}