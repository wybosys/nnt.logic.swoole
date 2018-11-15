<?php

namespace Nnt\Core;

abstract class AbstractRouter
{
    /**
     * router的标记
     * @return string
     */
    abstract function action();

    /**
     * 配置
     * @return bool
     */
    function config($cfg): bool
    {
        return true;
    }
}
