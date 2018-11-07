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

    // 框架中约定所有类均位于名域之中
    static function IsClass($obj): bool
    {
        if (!is_string($obj))
            return false;
        return strpos('\\', $obj) != false;
    }
}