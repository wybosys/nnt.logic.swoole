<?php

defined('PROJECT_SRC') || define('PROJECT_SRC', getcwd());

// 按照logic定义的规则，自动加载位于命名空间中的类
spl_autoload_register(function ($classname) {
    // 文件、路径均为小写
    $path = str_replace('\\', '/', strtolower($classname));
    $target = PROJECT_SRC . "/$path.php";
    if (!is_file($target)) {
        // 再尝试一次使用类名加载
        $ps = explode('\\', $classname);
        $target = PROJECT_SRC;
        for ($i = 0, $l = count($ps); $i < $l - 1; ++$i) {
            $target .= '/' . strtolower($ps[$i]);
        }
        $target .= '/' . $ps[$l - 1] . ".php";
        if (!is_file($target)) {
            echo "没有找到类文件 $target\n";
            return false;
        }
    }
    include_once $target;
    return true;
});
