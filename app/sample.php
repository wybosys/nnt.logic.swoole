<?php

namespace App;

use Nnt\Core\DateTime;
use Nnt\Core\IRouter;
use Nnt\Server\Rest;
use Nnt\Server\Transaction;

/**
 * @model()
 * @table("mysql", "echoo")
 */
class Echoo
{
    /**
     * @string(1, [input], "输入")
     * @colstring()
     */
    public $input;

    /**
     * @string(2, [output], "输出")
     * @colstring()
     */
    public $output;

    /**
     * @integer(3, [output], "服务器时间")
     * @colinteger()
     */
    public $time;
}

class MysqlCmd
{

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

    /**
     * @action(\Nnt\Core\Nil)
     */
    function phpinfo(Transaction $trans)
    {
        ob_start();
        phpinfo();
        $buf = ob_get_flush();
        $trans->output("text/html", $buf);
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