<?php

namespace Nnt\Manager;

use Nnt\Store\Rdb;
use Nnt\Store\Transaction;

// 数据库操作类
class Db
{
    // 获得一组数据
    static function Query($def, $cmd, $limit = -1)
    {
        $t = new Transaction($def);
        $t->rdbproc = function (Rdb $db) use ($cmd, $t) {
            $res = $db->query($cmd);
            if ($res == null)
                return [];
            else {
                $rcds = [];
                foreach ($res as $e) {
                    $m = $t->produce($e);
                    $rcds[] = $m;
                }
                return $rcds;
            }
        };
        return $t->run();
    }

    // id 的定义格式为 <dbid> . <其他，nosql的时候为collection的名称, kv的时候为key>
    // 获得一个
    static function QueryOne($def, $cmd)
    {
        $t = new Transaction($def);
        $t->rdbproc = function (Rdb $db) use ($t, $cmd) {
            $res = $db->query($cmd);
            if (!$res)
                return null;
            else if (count($res) > 1)
                return null; //获取一个，不能有多个返回的情况
            else {
                $m = $t->produce($res[0]);
                return $m;
            }
        };
        return $t->run();
    }
}