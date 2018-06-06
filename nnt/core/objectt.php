<?php

namespace Nnt\Core;

class ObjectT
{
    static function Get($obj, $key, $def = null)
    {
        return isset($obj[$key]) ? $obj[$key] : $def;
    }

    static function ToObject($obj)
    {
        if (!$obj)
            return null;
        if (is_array($obj)) {
            $r = [];
            foreach ($obj as $k => $e) {
                $r[$k] = self::ToObject($e);
            };
            return $r;
        }
        return $obj;
    }

}