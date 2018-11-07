<?php

namespace Nnt\Core;

class DateTime
{
    const MINUTE = 60;
    const MINUTE_5 = 300;
    const MINUTE_15 = 900;
    const MINUTE_30 = 1800;
    const HOUR = 3600;
    const HOUR_2 = 7200;
    const HOUR_6 = 21600;
    const HOUR_12 = 43200;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2592000;
    const YEAR = 31104000;

    static function Now(): int
    {
        return microtime(true);
    }

    static function Current(): int
    {
        return time();
    }
}