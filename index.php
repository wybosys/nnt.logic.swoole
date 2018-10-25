<?php

if (extension_loaded('xdebug')) {
    xdebug_disable();
}

// 自动加载需要的文件
spl_autoload_register(function ($classname) {
    // 文件、路径均为小写
    $classname = str_replace('\\', '/', strtolower($classname));
    $target = __DIR__ . "/$classname.php";
    if (!is_file($target)) {
        echo "没有找到类文件 $target";
        return false;
    }
    include_once $target;
    return true;
});

// 当前文件夹设置为工作目录
chdir(__DIR__);

// 加载入口文件
include_once "app/main.php";
main();
