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

/**
 * @model()
 */
class MysqlCmd
{
    /**
     * @string(1, [input], "sql")
     */
    public $sql;

    /**
     * @array(2, json, [output], "返回数据")
     */
    public $result;
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

    /**
     * @action(\App\MysqlCmd)
     */
    function mysql(Transaction $trans, MysqlCmd $m)
    {
        $db = $trans->db("mysql");
        $m->result = $db->query($m->sql);
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