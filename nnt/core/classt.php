<?php

namespace Nnt\Core;

class ClassT
{
    static function Instance($classname, $def = null)
    {
        $ret = null;
        try {
            $ret = new $classname();
        } catch (\Throwable $err) {
            $ret = $def;
        }
        return $ret;
    }

    /**
     * 从app.json的entryname转化成classname
     */
    static function Entry2Class($entry)
    {
        $comps = ArrayT::Convert(explode('.', $entry), function ($e) {
            return ucfirst($e);
        });
        return '\\' . implode('\\', $comps);
    }
}