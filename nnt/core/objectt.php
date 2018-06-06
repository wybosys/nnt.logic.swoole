<?php

namespace Nnt\Core;

class ObjectT
{
    static function Get($obj, $key, $def = null)
    {
        return isset($obj[$key]) ? $obj[$key] : $def;
    }
}