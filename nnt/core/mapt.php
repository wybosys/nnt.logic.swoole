<?php

namespace Nnt\Core;

class MapT
{
    static function Merge(...$arrs)
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
}