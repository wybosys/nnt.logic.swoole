<?php

namespace Nnt\Core;

class ArrayT
{
    /**
     * @param $arr array
     * @param $filter ($each, $index)
     * @return object
     */
    static function QueryObject(array $arr, callable $filter)
    {
        if ($arr) {
            for ($i = 0, $l = count($arr); $i < $l; ++$i) {
                $e = $arr[$i];
                if ($filter($e, $i))
                    return $e;
            }
        }
        return null;
    }

    static function Convert(array $arr, callable $to, $skipnull = false)
    {
        $ret = [];
        if ($arr) {
            for ($i = 0, $l = count($arr); $i < $l; ++$i) {
                $e = $arr[$i];
                $t = $to($e, $i);
                if (!$t && $skipnull)
                    continue;
                $ret[] = $t;
            }
        }
        return $ret;
    }

    /**
     * 将异步的foreach转换成等待同步操作
     * @param proc (each, idx, next)
     */
    static function ForeachSync(array $arr, callable $proc)
    {
        $i = 0;
        $l = count($arr);
        $func = function () use ($i, $l, $arr, &$func, $proc) {
            if ($i == $l)
                return;
            $proc($arr[$i], $i++, $func);
        };
        $func();
    }

    static function PushObjects(array &$arr, array $r)
    {
        if ($r) {
            foreach ($r as $e) {
                $arr[] = $e;
            }
        }
    }
}