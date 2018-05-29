<?php

namespace Nnt\Manager;

// 引入基础文件
use Nnt\Core\Config;

define('WORKDIRECTORY', dirname(dirname(dirname(__FILE__))));

// 自动加载需要的文件
spl_autoload_register(function (string $classname) {

});

class App
{
    function loadConfig(Config $cfg)
    {

    }

    function start()
    {

    }
}
