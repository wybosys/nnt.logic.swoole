<?php

namespace Nnt\Core;

class Tuple
{
    static function Make($_0, $_1)
    {
        return [
            "0" => $_0,
            "1" => $_1
        ];
    }

    static function IsTuple($obj)
    {
        return isset($obj["0"]) && isset($obj["1"]);
    }
}