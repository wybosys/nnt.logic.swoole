<?php

namespace Nnt\Core;

class ArrayT
{

    /**
     * @param $arr array
     * @param $filter ($each, $index)
     * @return object
     */
    static function QueryObject($arr, $filter)
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

    static function Convert($arr, $to, $skipnull = false)
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

}