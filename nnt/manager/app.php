<?php

namespace Nnt\Manager;

// 引入基础文件
use Nnt\Core\Config;

define('WORKDIRECTORY', dirname(dirname(dirname(__FILE__))));

// 自动加载需要的文件
spl_autoload_register(function ($classname) {
    // 文件、路径均为小写
    $classname = strtolower($classname);
    include_once WORKDIRECTORY . "/$classname.php";
    return true;
});

class App
{
    function __construct(string $appname)
    {
        self::$shared = $this;
        include_once WORKDIRECTORY . "/$appname/index.php";
    }

    static $shared;

    function config(Config $cfg)
    {
        $this->_cfg = $cfg;
    }

    protected $_cfg;

    function start()
    {

    }
}
