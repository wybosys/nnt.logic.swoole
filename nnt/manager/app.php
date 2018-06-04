<?php

namespace Nnt\Manager;

use Nnt\Config\AppNodes;

// 当前工作目录
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
    function __construct()
    {
        self::$shared = $this;
    }

    static $shared;

    /**
     * @return AppNodes
     */
    static function LoadConfig(string $appcfg = "~/app.json", string $devcfg = "~/devops.json")
    {

    }
}
