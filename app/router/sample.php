<?php

namespace App\Router;

use App\Model\Cache;
use App\Model\Echoo;
use App\Model\Info;
use App\Model\MysqlCmd;
use App\Model\RedisCmd;
use App\Trans;
use Nnt\Core\AbstractRouter;
use Nnt\Core\DateTime;
use Nnt\Server\Transaction;

class Sample extends AbstractRouter
{

    function action()
    {
        return "sample";
    }

    /**
     * @action(\App\Model\Echoo)
     */
    function echo(Trans $trans, Echoo $m)
    {
        $m->output = $m->input;
        $m->time = DateTime::Current();
        $m->info = new Info();

        $trans->submit();
    }

    /**
     * @action(\App\Model\Cache, [cache_10])
     */
    function cache(Trans $trans, Cache $m)
    {
        echo "不经过缓存";
        $m->value = $m->key . DateTime::Current();
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
        $buf = explode("\n", $buf);
        $buf = implode("<br>", $buf);
        $trans->output("text/html", $buf);
    }

    /**
     * @action(\App\Model\MysqlCmd)
     */
    function mysql(Trans $trans, MysqlCmd $m)
    {
        $db = $trans->mysql();
        $m->result = $db->query($m->sql);
        $trans->submit();
    }

    /**
     * @action(\App\Model\RedisCmd)
     */
    function redis(Trans $trans, RedisCmd $m)
    {
        $db = $trans->kv();
        if ($m->value) {
            $db->setraw($m->key, $m->value);
        } else {
            $m->value = $db->getraw($m->key);
        }
        $trans->submit();
    }
}
