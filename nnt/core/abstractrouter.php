<?php

namespace Nnt\Core;

abstract class AbstractRouter implements IRouter
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
