<?php

namespace Nnt\Core;

class DateTime
{
    static function Now(): int
    {
        return microtime(true);
    }

    static function Current(): int
    {
        return time();
    }
}