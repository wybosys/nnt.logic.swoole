<?php

namespace Nnt\Core;

class MapT
{
    static function Merge(... $arrs)
    {
        $ret = [];
        foreach ($arrs as $arr) {
            if (!$arr)
                continue;
            foreach ($arr as $k => $v) {
                $ret[$k] = $v;
            }
        }
        return $ret;
    }

    static function Convert(array $arr, callable $to, $skipnull = false)
    {
        $ret = [];
        if ($arr) {
            foreach ($arr as $k => $v) {
                $t = $to($v, $k);
                if (!$t && $skipnull)
                    continue;
                $ret[] = $t;
            }
        }
        return $ret;
    }
}